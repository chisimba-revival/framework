# Field guiding microservice — design

The implementation design for the service described in
[field-guiding-service-contract.md](field-guiding-service-contract.md). The
contract says what the service must do and, more importantly, what it must
never do. This document says how, and records why each structural decision was
made rather than merely chosen.

Read the contract first. This document assumes its 21 rules and its vocabulary.

## What this service is

A stateless HTTP service that owns field data — drives, trail logs, sightings,
sign-offs, media records — and validates Chisimba bearer tokens locally. It
holds no credentials and no password ever reaches it.

It is one of three deployables. The boundary between them is the single most
consequential decision in the system, so it is worth stating exactly:

| Deployable | Owns | Never does |
|---|---|---|
| Chisimba (PHP/MDB2) | identity, groups, permissions, the mentor's own UI | store a drive, a sighting or a sign-off |
| Field service (this) | all field data, media *records*, reference catalogues | store or verify a credential, decide who may see what from its own tables |
| Object storage (S3) | media bytes | anything else |

A review is a **command sent to this service**, not a row written into Chisimba.
Chisimba renders what this service returns. That is the only arrangement in
which rule 6 has a subject: if two processes could write one body of records
there would be no sole authority to resolve a revision conflict against.

## Technology, and why each piece is here

**PostgreSQL 16 + PostGIS.** Chisimba runs on MariaDB through MDB2 and stays
there. The field data has a different shape: geometry that must be queried
spatially ("what did we see within 200 m of this path"), revision columns that
must be compare-and-swap, row-level security as a second line of defence, and
`jsonb` payloads for operation results. PostGIS gives geometry with a real
index; MDB2 gives none of it, and the contract requires the isolation to be
enforced in three places (§Isolation).

**Redis 7.** Three unrelated jobs that happen to need the same properties:
low-latency keyed expiry, atomic counters, and blocking queues. The revocation
denylist is bounded by access-token lifetime (§Revocation), rate limiting is a
token bucket, and media confirmation and webhook delivery are queues. All three
are *derived or transient* state — losing Redis costs a re-fetch or a delayed
job, never a record. That is the test a thing must pass to belong here.

**S3-compatible object storage.** Media bytes never pass through this service
(rule 5). The service holds pre-signed URLs and the media *record*.

**Stateless containers.** No local disk, no sticky sessions. Horizontal scaling
is a load balancer decision, and a rolling deploy drops no request.

## Data model

### The isolation columns

Every field record carries `context_code` and `revision`. Both are NOT NULL with
no default, because a default is how a record ends up in a context nobody chose.

```sql
create table sighting (
  id                uuid primary key,
  context_code      text        not null,
  drive_id          uuid        not null references drive(id),
  revision          bigint      not null default 0,

  species_code      text,
  count             integer,
  location          geometry(Point, 4326) not null,
  location_accuracy_m double precision,
  distance_m        integer,
  bearing_deg       integer,
  behaviour         behaviour,
  age_sex_class     age_sex_class,
  notes             text,

  status            sighting_status not null default 'pending',
  verified_by       text,
  verified_at       timestamptz,
  verification_notes text,

  recorded_species_code text,
  recorded_count       integer,
  correction_reason    text,

  late_arrival      boolean     not null default false,
  captured_at       timestamptz not null,
  recorded_at       timestamptz not null,
  created_by        text        not null,

  deleted_at        timestamptz,
  tombstone_revision bigint,

  constraint count_positive      check (count is null or count > 0),
  constraint correction_has_reason check (
    (recorded_species_code is null and recorded_count is null)
    or correction_reason is not null
  )
);
```

Four constraints worth their reasoning:

**`count > 0`, and count may be null.** The contract is explicit that "an absent
`count` is likewise not a zero. A zero asserts that the animal was looked for
and there were none, which is a different statement from not having counted."
A zero is a claim about a search that happened. The database refuses to let a
client make that claim by accident.

**`correction_has_reason`.** Rule 15: a correction changing species code or
count requires a reason, and "a silent overwrite cannot be represented as a
completed review." Encoding it as a table constraint rather than application
validation means there is no code path — present or future, ours or a
migration's — that can produce a correction without one.

**`location_accuracy_m` is a real column, not derived.** The contract keeps it
because "a guide's confidence in a position is part of the record." A position
without its accuracy is a claim the reader cannot weigh.

**`verified_at` is separate from `status`.** A `rejected` sighting still carries
a `verified_at`, because somebody looked and declined. Collapsing them loses the
audit trail. This is why the column exists beside `status` rather than inside it.

**`tombstone_revision`, not a reused `revision`.** A tombstone for an entity the
client has never seen has no row to carry a revision. Rule 9 wants the deletion
retained with the revision at which it happened; when there is no row, the
tombstone is the only place it can live.

### The composite index is led by `context_code`

```sql
create index sighting_context_drive_revision
  on sighting (context_code, drive_id, revision);
create index sighting_location_gix on sighting using gist (location);
```

Every list and every count is `where context_code = any($grants)`. If that
predicate is not the leading column of some index, every query is a scan, and a
scan that forgets the predicate returns another reserve's records. The index is
the third of the three isolation layers (§Isolation) and it is the one that
fails quietly rather than loudly, so it is specified here.

### Waypoints are append-only

```sql
create table trail_waypoint (
  trail_log_id uuid    not null references trail_log(id),
  ordinal      integer not null,
  point        geometry(Point, 4326) not null,
  captured_at  timestamptz not null,
  accuracy_m   double precision,
  note         text,
  primary key (trail_log_id, ordinal)
);
```

`ordinal` is part of the primary key, so a waypoint's identity *is* its position
in the walk. There is no surrogate id to renumber and no `updated_at`, because a
row that could be edited in place would contradict the rule the contract calls
out by name: "Appends are additive; a trail log is not rewritten, because the
sequence of observations is itself the record. This is the one entity where
ordering is the content."

The consequence has to be enforced in the write path, not just the schema: a
waypoint correction is **another row**, not an update. A guide who stops at the
same place twice has two waypoints at the same coordinates and two ordinals,
because the second stop is a real event even though the position repeats.

### The change feed carries the row, not just its identity

```sql
create table change_feed (
  seq          bigserial primary key,
  context_code text        not null,
  entity_type  text        not null,
  entity_id    uuid        not null,
  revision     bigint      not null,
  changed_at   timestamptz not null default now(),
  body         jsonb       not null
);
create index change_feed_context_seq on change_feed (context_code, seq);
```

A pull is `where context_code = any($grants) and seq > $cursor order by seq`.
Two alternatives were considered and rejected.

*Identity only* — the feed names what changed and the client fetches it. This
turns one page into N round trips over a connection that is already marginal,
which is the situation the whole system exists for.

*Live join* — the feed joins the entity tables at read time. This is wrong in a
way that only shows up under load: a page is then not a consistent snapshot. A
client can receive revision 4 and revision 5 of the same entity in one page and
apply them in the wrong order, or receive a row that was deleted between the feed
read and the join. Serialising the row at change time costs storage and buys a
page that is coherent as of a single cursor.

Retention is **30 days**, and it is what makes `resync_required` necessary rather
than theoretical.

### Derived counts are never stored

`duration_minutes`, `distance_km`, `sightings_count` and `trails_count` are
computed in the `drive_summary` view. The contract cuts them for a reason worth
repeating: they are "two sources of truth that can disagree." A stored counter
that disagrees with the rows it counts is indistinguishable from a correct one,
and the disagreement is always in the direction of flattering the trainee.

`distance_km` comes from `ST_Length(ST_MakeLine(waypoints))` on the PostGIS
geometry rather than from summing per-segment distances in application code, so
the number the client shows is the number the database computed.

## Isolation: `context_code` enforced three times

Rule 11 says access requires a scope **and** a grant in that context, and that
neither implies the other. Three layers, all required:

**1 — the query layer.** Every repository function takes a `Caller` value
derived from the verified token and adds `context_code = any($caller.grants)`.
The parameter is not optional and there is no overload without it. A function
that cannot be called without a caller cannot forget one.

**2 — row-level security.** A belt to the query layer's braces:

```sql
alter table sighting enable row level security;
alter table sighting force row level security;

create policy sighting_in_granted_context on sighting
  using (context_code = any (string_to_array(current_setting('app.context_grants'), ',')));
```

The application sets `app.context_grants` from the **verified token** at the
start of each transaction. Three consequences worth stating, because this is a
security control and each of them is a way it can be quietly defeated:

**`set local`, never `set`.** A plain `set` persists for the life of the
connection, so a pooled connection hands the previous request's grants to the
next one and a guide reads another reserve's drives. `set local` reverts at
commit *and* at rollback, which makes a pooled or reused connection safe without
the pool having to know this policy exists. This is the whole reason for that
form of the statement and it is worth being pedantic about, because `set` and
`set local` look interchangeable and only one of them is correct here.

**The table owner is subject to the policy too.** That is what `force row level
security` is for. Without it the owner bypasses its own policies, so a migration
or a manual `psql` session reads across every context without any error. It does
**not** protect against a superuser or a `pg_dump`, both of which bypass RLS
outright; that is accepted, and the reason it is acceptable is that this database
holds field data and no credential.

**An unset setting must fail, not default.** `current_setting` with no default
raises rather than returning empty, so a code path that forgets to set the grants
errors instead of reading everything. If that behaviour were ever "fixed" by
adding a `missing_ok` default, the policy would compare against an empty array
and deny all reads — still safe, but as a total outage rather than a loud bug.
The erroring behaviour is the better of the two failures and should be left
alone.

**3 — the index.** §The composite index.

### Writes take the context from the token, never the request

`ctx` is the active context and `ctxs` is the grant set. They are separate on
purpose: using the active context as the read boundary "would hide a guide's
northern-reserve drives while they happen to be working in the southern one."

So an insert path physically cannot be handed a caller-supplied context. The
write helper reads `caller.activeContext` and the request body has no field to
put a context in. A client that tries to write into a context it was not granted
does not get a validation error — it writes into the context the token names,
which is the correct outcome and requires no error path at all.

## Authentication and revocation

### Tokens are validated locally

RS256 only. `none` is not accepted, the algorithm is checked against an allowlist
rather than read from the header, audience must equal the configured service
audience, and the issuer must be trusted. JWKS is cached and refreshed on an
unknown `kid` and periodically thereafter.

A rejection does not say which check failed. "Your token is invalid" and "your
token is signed by someone we do not trust" tell an attacker which half to fix,
and the client cannot act on the difference.

### The identity projection is for display and nothing else

`identity_projection(user_id, username, fullname, roles, updated_at)` is
refreshed by the provisioning webhook. It exists so a drive list can show a
guide's name without seven round trips.

**It is never an authorisation input.** Rule 12: it is a display cache. The
service does not consult it to decide whether a request is permitted, because a
cache that answers "may this user read this?" quietly reads authorisation out of
a cache, and that failure looks like working software right up until the first
deactivation. Authorisation comes from the token and the denylist, both of which
are current by construction.

The contract's delivery sequence deliberately splits the projection (step 4)
from the denylist (step 3) for the same reason: building the projection first
"displays a stale name for a user who still has access," and the projection
alone "quietly reads authorisation out of a cache."

### Revocation needs two checks, not one

Redis, one key per user:

```
deny:{user_id}  →  { revoked_at: <epoch seconds>, epoch: <int> }   TTL = access token lifetime
```

A request is refused when **either** the token's `epoch` is lower than the
recorded epoch, **or** the token was issued before the recorded revocation time.

Both checks are necessary and they catch different failures. The epoch catches a
user whose roles were *reduced* — they still hold a validly signed token, it
simply asserts authority they no longer have. The timestamp catches a user
deactivated and reactivated: the epoch may be unchanged, so only "issued before
revocation" refuses it. A deactivated-then-reactivated user must be issued a
fresh token, and this is what enforces it.

The key's TTL is the exposure window and it is **configured, measured and
published**, not assumed — the contract's phrase, and the reason the TTL is a
deployment parameter rather than a constant in the code.

## Drives: a state machine, on the server

`planned → active → completed`, with `cancelled` reachable from `planned` or
`active`. Cancellation is a first-class operation and never a delete: "a drive
that ran for two hours and was abandoned is part of the record."

`sealed_at` is set when a sign-off is approved and is the only thing that closes
a drive to late observations. Ending a drive does not (rule 14). Unsealing is
explicit, audited, and separate.

An illegal transition is **409 with the current state in the body**, not a 400.
An offline client holding a stale view needs to reconcile against what is true,
and "cannot go active from completed, it is completed" is a sentence it can act
on where "invalid transition" is not.

`start_time` and `end_time` are written once and never rewritten. `planned_start_time`
is the only planned value kept, because it is the only one somebody can later be
compared against.

## The sync engine

### Pull

`POST /api/v1/sync/pull` with the cursor and the caller's grants.

A cursor older than the feed retention, or one this service cannot interpret, is
answered with **status `resync_required`** — never an empty page. The reason is
the client's, and it is worth restating because it is the sharpest failure in
the whole system: an empty page is indistinguishable from "nothing changed," so
the client "would silently stop receiving updates for data it has never seen,
which is the worst possible failure mode for an offline client."

A resync is a full re-pull of the caller's accessible scope. It is a normal
path: the client discards its cursors and pulls again, which is safe because
changes apply by revision and re-delivering what the client already holds is a
no-op rather than a second copy.

### Push: operations are intents

Each entry carries `operation_id`, entity type, client-generated entity id, an
operation **kind**, `base_revision`, `captured_at`, optional `depends_on`, and a
payload. The kind is one of `create`, `update`, `delete`, `start`, `end`,
`cancel`, `verify`, `reject`, `correct`, `submit`, `review`.

There is no "replace the entity" kind, and that absence is the design. The
contract's reason: a drive's end "carries an end time and a summary, not the
entire drive as the client last saw it. Replacing the entity would overwrite
fields the client never intended to touch, which is a conflict dressed up as a
successful write."

Each kind has its own handler with its own allowed field set. `verify` writes
`status`, `verified_by`, `verified_at` and `verification_notes`. It cannot write
`location`, because there is no path by which it could.

### Idempotency, and the part that is easy to get wrong

```sql
create table operation_outcome (
  caller_user_id  uuid        not null,
  operation_id    uuid        not null,
  entity_type     text        not null,
  entity_id       uuid        not null,
  kind            text        not null,
  outcome         text        not null,
  result          jsonb,
  recorded_at     timestamptz not null default now(),
  primary key (caller_user_id, operation_id)
);
```

Keyed by **operation id, never entity id** (rule 7), and scoped by
`caller_user_id` because "an `operation_id` is never proof of identity." A
guessing client must not be able to replay somebody else's operation.

**The outcome row is written in the same transaction as the entity change.** This
is the part that is easy to get wrong and it is worth stating flatly: if the
outcome is committed first and the entity write then fails, the client's retry
returns a recorded success for work that never happened, and the observation is
lost with no error anywhere. One transaction, or the idempotency guarantee is
worth nothing.

A replay returns the **originally recorded outcome including a rejection**. A
client that retries a verification which was refused must be told it was refused,
not handed a second chance by the retry itself.

Outcomes are retained for **7 days**. A retry after that is refused as
`unknown_operation` rather than silently reapplied — because silently reapplying
an operation whose original outcome has been forgotten is how a duplicate gets
written by a client that gave up and retried a week later.

### Conflicts

`revision` matches → apply. Entity absent → treat as create. They differ →
**refuse**, returning the server's current state, its revision, and the client's
submitted values side by side.

The server never merges and never discards the client's edit. Both sides are
stored and the client decides; a mentor or trainee looking at this later needs to
see what was attempted, not what survived.

Verification is the exception and is refused **unconditionally** against a stale
revision, with no conflict payload — verification is never resolved by revision.

### Dependencies

An operation whose `depends_on` has not been satisfied is **held and
re-evaluated up to a bounded wait**, not failed. Failing it would mean a drive's
trail log could never be appended to on a device that recorded the drive's start
after the trail.

The bound is a timeout, and on expiry the outcome is an explicit
`dependency_unsatisfied` rather than a retry forever. The client's queue already
keeps deferred work, and a dependency that has not been written *yet* is normal
— the drive a sighting belongs to may be started on another screen later — so
anything that treats an absent dependency as an error produces false alarms and
holds valid work.

## Media: three steps, and never a proxy

```
POST   /api/v1/media/upload    → media row (state=pending) + pre-signed PUT + media id
POST   /api/v1/media/{id}/confirm → HEAD the object; state=confirmed
GET    /api/v1/media/{id}      → metadata + pre-signed GET
```

`state` is `pending | confirmed | unavailable`, so a client can tell unsynced
from missing. Without the confirm step "an upload that failed silently leaves a
media record that renders as a broken image."

Bytes never traverse this service (rule 5). It issues URLs and keeps records.

Abandoned `pending` rows are swept after 24 hours and marked `unavailable`
rather than deleted, so the record of an attempted upload survives even when the
bytes do not.

`captured_at` comes from the file's EXIF where present, falling back to the
device clock — "because the device clock on a phone that has been out of
coverage can be wrong in a way that quietly corrupts every sighting taken that
day." Media does not carry an EXIF `metadata` object; the contract cuts it.

## Redis keyspace

Three prefixes, three lifetimes, no shared keys:

| Prefix | Contents | TTL | Losing it costs |
|---|---|---|---|
| `deny:{user}` | revocation epoch + time | access-token lifetime | up to one token lifetime of stale access |
| `rl:{user}:{route}` | token bucket | window | a burst of requests |
| `q:media`, `q:webhook` | job queues | none | delayed jobs, retried |

Nothing a Redis key holds is ever the only copy of anything. That is the whole
admission test.

The credential-bearing route is rate limited more tightly than general traffic,
per the contract, because it is the one route worth attacking.

## Redis is not the only thing that fails

If Redis is unavailable the service **fails closed on revocation** — a denylist
that cannot be read cannot be said to have been consulted, and rule 3 and 12 are
not negotiable. Reads that need no authorisation decision may proceed; writes
do not. Rate limiting degrades open, which is a availability-versus-abuse
trade and is the correct side of it.

## Reference data

`species` and `competency` catalogues are served here, not by Chisimba, "because
they belong with the field data and must be bundled into the offline cache
without a second round trip."

Read-only over HTTP, seeded by migration, cached aggressively by clients and
refreshed in the background.

### Two places this design deliberately contradicts the contract's letter

Both are recorded rather than silently adopted, because the contract is the
document a third party builds against and a quiet divergence is how two
implementations stop agreeing.

**`species_code` is not constrained to four letters.** The contract's prose says
"four uppercase letters". The design's own reference data uses `HIPPO` (five)
and `VOX` (three). A `char_length = 4` constraint rejects the project's own
elephant and would lose the record. The column is `text` with a check permitting
3–5 uppercase characters, and **the contract's wording should be corrected**.

**`behaviour` and `age_sex_class` are native enums.** The contract declares them
enums without enumerating the values, and gives a real reason: a behaviour tally
across a drive "is a training exercise in itself, and free text would not
support it." Adding a value is `ALTER TYPE ... ADD VALUE`, which is additive and
non-breaking under the contract's own versioning rule.

The consequence is that a client sending a value this service has not heard of
gets a 422 rather than a silent blank. That is the contract's intent — validation
is the point — but it is a real operational coupling, and the client currently
stores these as free text. The two must be reconciled when the service's list is
fixed; until then a client-side value the service rejects is a record the client
holds and the service has refused, which is the safe direction but not a
comfortable one.

## Deployment

Four containers added to the existing compose stack: `field-service`,
`field-db` (PostgreSQL + PostGIS), `field-redis`, and an S3-compatible store.
Chisimba, MariaDB, nginx and PHP are untouched — "Chisimba stays on Apache/PHP
and gains no new runtime dependency."

`GET /health` is unauthenticated and reports liveness and readiness only. It
must never report on the database or Redis contents, because an unauthenticated
endpoint that describes internal state is an information disclosure with a health
check's level of trust.

Migrations run as a separate step before the new version takes traffic, never on
startup: an expand-only migration applied by every instance at once is how a
rolling deploy deadlocks.

## What is not designed here

Media transcoding, tile hosting, mentor map rendering, and the reporting views
are out of scope. Sign-off review workflow beyond the state machine is described
by the contract and not designed here.

**Nothing in this document is built.** It is a design. The first thing to build is
the token validation middleware and the RLS policies, because everything else is
unsafe to write until those exist and can be tested against them.