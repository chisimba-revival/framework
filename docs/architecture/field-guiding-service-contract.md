# Field guiding service contract

## Status

Accepted architectural direction for the Chisimba Revival.

This document defines the contract between the Chisimba framework and the
field guiding microservice. It is a companion to `api-gateway.md`, which
defines the Chisimba-side gateway those Chisimba endpoints are served
through. Where this document and older milestone notes disagree, the
Constitution and the repository `AGENTS.md` take precedence.

The microservice is a separate deployment, but it does not become a second
source of identity. Authentication is Chisimba's, and the microservice is a
resource server that validates what Chisimba issued. This is what keeps
"login is not authorisation" true on both sides of the boundary.

### Amendments

**Conditional optionality on a sighting's identification, 2026-10-04.** Rule 21
was added, and `species_code` and `count` became optional until verification.
The original logbook table stated no requiredness for any field while the
example model marked both as required, so a client generated from it could not
represent a sighting recorded without an identification. The mobile field log
design raised this against the contract rather than assuming it, and the
correction belongs here: the ambiguity was in the contract. The example model
was also corrected to declare `abstract class`, without which it does not
compile under freezed 3 or 4, and was made a faithful projection of its own
table.

## Purpose

This is a teaching application. A trainee is learning to be a field guide, and
every record they create is an occasion to practise and to be corrected. The
system exists to produce competent guides, and the field records are evidence
of that learning rather than the outcome in themselves. A database of
observations that nobody learned from has failed at its job, however complete
it is.

That reframing has consequences that run through this whole contract, and they
are collected in "Teaching the work" below. The short version is that the
system's most valuable output is not a record but a correction, and that a
mentor's written feedback is part of the data model rather than a courtesy
attached to it.

The mobile application is used in the field, in places with little or no
connectivity, and it is also used alongside the web portal. That combination
produces requirements the framework cannot meet well: a write-heavy offline
client, media capture in the field, geospatial data at volume, and a sign-off
workflow that spans several users over time.

Splitting the work is therefore not a matter of taste. Chisimba already owns
identity, groups, permissions and contexts, and already has a mature
authentication stack. The field data has none of those properties and would
compromise them if added to the framework's transactional MySQL schema. The
microservice owns field data and speaks HTTP; Chisimba owns identity and
speaks the gateway contract.

The division is deliberately narrow. Chisimba does not store outings, sightings
or sign-offs, and the microservice does not store credentials, groups or
permissions. Each side is authoritative only for what it owns.

## Teaching the work

This section states what the application owes the people learning from it. It
is placed before the domain boundaries because those boundaries are the easier
part to get right, and because a contract that gets the storage right and the
teaching wrong has not succeeded.

A trainee is not filling in a form. They are making judgements about animals,
routes, terrain and their own judgement under observation, and the system is
the thing that tells them whether those judgements were any good. That is a
pedagogical relationship, and it carries obligations that a pure data model
does not have.

### Feedback is the primary output

The most valuable record in this system is not an observation. It is a
correction, because a correction is the only moment in the whole cycle where a
trainee's understanding can actually change.

A sighting is recorded as a claim. A verification is the answer to that claim,
and an answer without a reason teaches nothing. So the correction of a species
code is not an administrative overwrite; it is the lesson, and it carries an
explanation with it. A mentor who silently changes `LEOP` to `LION` has marked
the work and taught nothing, and the trainee is left with a record that looks
correct and a gap where their reasoning should have been.

The verification notes are therefore not an optional comment field. They are
the substance of the teaching, and the client presents them beside the
original claim rather than in place of it. Both are kept, always, so the
trainee can see what they thought, what it actually was, and why.

Rejection is the same idea in harsher form. A rejected sighting is a teaching
moment that failed to be taken, and the system records it as a claim that was
made and examined, not as an absence. Nothing is deleted because a trainee was
wrong; that is the single most destructive thing this system could do.

`needs_review` is deliberately a distinct state from `rejected`. It records a
mentor who examined the work and was not willing to commit to a judgement. That
is honest, it models good professional practice, and it teaches a trainee that
uncertainty has a legitimate place in a field record rather than being a
failure to be hidden.

The distinction is also functional, not only presentational. `needs_review`
asks the trainee for more information; `rejected` states that the claim is
wrong and closes it. A trainee shown one undifferentiated "not approved"
state cannot tell whether their part of the work is to supply more detail or to
stop, and a record that leaves them unable to tell has stalled rather than
progressed. The state is therefore part of what the service communicates, not
only a value the client chooses how to colour.

### Assessment is formative, and the decision is a person's

A sign-off measures competence. It is not a score to be minimised and not a
badge to be cleared once.

Formative does not mean there are no criteria. The encounter thresholds are
real, published in advance, and they are what a trail sign-off is assessed
against. What formative rules out is the system enforcing them: readiness is
computed and reported, and a mentor decides. The system never refuses to let a
trainee submit, never withholds a record, and never declares a competency
unmet on its own authority. A calculation is evidence for a person, not a
verdict.

This distinction matters because a system that enforces a threshold teaches a
trainee to manage the number rather than to notice the animal. A mentor who
reads the readiness report and then talks the trainee through what they
actually saw is doing something the count cannot do, and a trainee whose
submission is blocked at nine of ten encounters has been taught only that the
form is a lock.

The workflow permits revision, and that is the pedagogically important part:
`changes_requested` returns work to the trainee with a reason, and the trainee
revises and resubmits. A programme that allowed only approve or reject would
teach trainees to avoid submitting anything uncertain, which is the opposite
of what a field guide needs.

Ratings run `not_observed`, `developing`, `competent`, `proficient`, `expert`
and evidence is mandatory even at `not_observed`, recording why the competency
could not be judged. An unobserved competency is information about the outing,
not a zero, and the system says so rather than letting a blank read as a
failure.

Competency progresses through levels rather than being cleared once. A trainee
at `competent` in navigation is not thereby `proficient`, and the record shows
which, so that the next drive can be aimed at the gap. This is why assessment
is retained per outing and per competency rather than collapsed into a single
standing grade per trainee.

### The trainee sees their own development

Progress is visible to the trainee, not only to the mentor. Hours walked, first
and second rifle time, and encounter tallies are shown to the person
accumulating them, because a tally a student cannot see is a tally that cannot
motivate. These are computed from accepted records and never stored as running
counters, so a trainee cannot be shown a total that a later correction has
invalidated.

The tally is a teaching instrument rather than a scoreboard. Progress toward a
threshold is shown, so that a trainee who has four of five rhinoceros
encounters can see what is outstanding. A threshold that appears only at the
moment it is failed is a punishment, and it teaches nothing about what to do
next.

Progress toward a threshold is presented as a list of outstanding
requirements, not as a progress bar or a percentage. A bar invites reading
four-fifths as four-fifths of a grade, which is the interpretation the design is
trying to prevent, and it invites colouring a shortfall red, which is a
judgement the trainee cannot act on. The list names the requirement, states how
many have been met, and stops there. It does not convert the remaining count
into a proportion of the trainee's worth.

### Reflection is part of the record

A trainee records what they learned from a drive or trail alongside what they
saw. This is a separate field from the sightings for a reason: the sightings
are what was observed, and the reflection is what it taught. Collapsing them
loses the distinction between the world and the student's account of it.

A reflection cannot be verified, because it is not a claim about the world.
It is retained because it is how a trainee later demonstrates to themselves how
much their judgement has moved.

### The mentor's whole-exercise view is a teaching requirement

A mentor or administrator reads every outing, route and sighting in a context,
not only the records a particular trainee authored. Grading competence means
seeing the whole of a trainee's work, and assessing one identification means
seeing the surrounding sightings that trainee could not see, because those
often contain the comparison the mentor is asking the trainee to make.

This is not an exception granted to a role. It matches how Chisimba already
treats assignment submissions, which remain visible to the submitting student,
authorised instructors and administrators. The programme inherits that
relationship rather than inventing a narrower one for field data.

The boundary that remains is the context. A mentor reads contexts they hold a
role in, not every context on the installation, so one reserve's routes and
encounters stay separable from another's.

### Nothing about difficulty is implied by the interface

The system records what was encountered and assesses what was demonstrated. It
does not present a sighting as trivial because it was small, and it does not
present a dangerous-animal encounter as an achievement to be collected. The
`any sighting counts` rule in the trail log applies to tallying, not to worth.

Where the client controls presentation, it does not surface counts of dangerous
animals as a score to beat. The thresholds exist to certify competence, and a
client that renders them as a game would misrepresent what the programme is
for.

This is specified so that it can be checked rather than merely intended. A
client does not display points, levels, badges, stars, ranks or leaderboards. It
does not display time-to-complete, distance-per-hour or encounters-per-hour in
any comparison between trainees. It does not order trainees by tally, and it
does not colour a tally red for being low. Thresholds appear as a list of what
remains outstanding, because a requirement list can be acted on and a progress
bar can only be felt about.

## Domain boundaries

### Chisimba

Chisimba owns:

- user identity, credentials, MFA and session establishment;
- groups, group hierarchy and membership;
- permission and context-role grants;
- context membership, and therefore which reserves and teams a user belongs
  to;
- the audit trail for every identity and permission change;
- token issuance for every machine client.

Chisimba does not own outing state, sightings, trail logs, media binaries,
competency assessments or sign-off decisions.

One consumer of this service is a Chisimba module, `trailsprogramme`, which
renders the web interface to the same records and stores none of them. Its
architecture, and the back-channel token exchange it needs to act as a signed-in
user without holding a browser token, are recorded in
`trails-programme-adr.md`. It introduces no new trust relationship: it presents
a token the gateway already issues for that user, and this service validates it
exactly as it validates any other.

The encounter thresholds that determine a trail sign-off are Chisimba
configuration rather than field data, are snapshotted into the submitted record
at submission so that a later change cannot alter what an earlier submission
was assessed against, and are described in that same record.

### Field guiding service

The microservice owns:

- drives, hikes and camps, their lifecycle and their participants;
- trail logs, waypoints and observations;
- wildlife sightings and their verification state;
- competency assessments and mentor sign-offs;
- media metadata, and pre-signed upload and download URLs;
- the species and competency reference data the mobile client caches;
- the change log that offline clients synchronise against.

The microservice never writes to Chisimba tables and never holds a Chisimba
credential. It holds only a cached projection of identity, sufficient to render
a name next to a sign-off.

## Non-negotiable rules

1. The microservice never receives, stores or transmits a Chisimba password.
   It accepts only signed, expiring bearer tokens.
2. A user's identifier in the microservice is the Chisimba user id, unchanged.
   No local surrogate keys, and no re-mapping.
3. Every microservice request is authorised against a projection of Chisimba
   scopes carried in the token, not against a locally edited ACL.
4. Chisimba data is replicated into the microservice one way only. A field
   record can reference a Chisimba user, but a field record can never modify
   one.
5. Media binaries do not pass through either service. Clients upload and
   download directly against object storage using short-lived pre-signed URLs.
6. The server is the sole authority on field data. Conflicts are decided by
   record revision, never by comparing timestamps. Where a client and the
   server disagree, the server's recorded state wins, the client's version is
   returned alongside it and preserved for review, and the client decides what
   to keep.
7. Every mutation is idempotent under client retry, keyed by operation
   identifier rather than entity identifier. A retried request must never
   create a second outing, sighting or sign-off, and retrying a start must not
   be mistaken for retrying the outing's creation.
8. No endpoint returns a Chisimba credential hash, group definition or
   permission row, including in error responses.
9. Deletion of field data is a soft delete with a retained audit trail. Hard
   deletion is an explicit administrative action, not an API default.
10. The field service validates Chisimba-issued access tokens, locally, against
    the published JWKS document, and never calls back to Chisimba to do so. The
    dependency runs one way only. Chisimba is not a resource server for field
    data and does not accept tokens minted by the field service.
11. Every record carries its owning `context_code`. Access requires both the
    scope and a grant in that context, and neither implies the other. A record
    with no readable context is not readable.
12. Revocation is checked per request against a denylist fed by the provisioning
    webhook, bounded by the access token lifetime. The identity projection is
    a display cache and is never an authorisation input.
13. `captured_at` and `recorded_at` are both retained and are never
    interchangeable. Neither is used to arbitrate a conflict.
14. Ending an outing does not close it to late observations, sightings or
    media. A sealed outing does, and unsealing is an explicit, audited action.
15. A correction that changes a species code or a count requires a reason, and
    the original recorded values are retained permanently. A silent overwrite
    cannot be represented as a completed review.
16. Nothing is deleted because a trainee was wrong. A rejected claim is
    retained as examined, and `needs_review` is recorded distinctly from
    `rejected` so that a mentor's uncertainty is not reported as a failure.
17. A sign-off may be revised and resubmitted after `changes_requested`.
    Approve-and-reject-only assessment is not offered.
18. A competency assessment is retained per outing and per competency and is
    never collapsed into a single standing grade, and `not_observed` is
    recorded as an observation about the outing rather than as a zero. An
    outing of any kind can carry one: a walk teaches what a drive does not.
19. Readiness against a threshold is computed and reported to a mentor. It
    never blocks a submission, withholds a record, or concludes a competency on
    the system's own authority. Criteria inform a person's decision; they do
    not replace it.
20. Nothing is presented as a score. No points, levels, badges, ranks,
    leaderboards, completion times, or per-hour comparisons between trainees.
    Outstanding requirements are listed rather than rendered as a progress bar
    or a percentage.
21. A sighting may be recorded without an identification and without a count.
    `species_code` and `count` are required only when `status` is `verified`;
    both may be absent in `pending`, `needs_review` and `rejected`. A sighting
    with no species can still be verified **as observed**: a mentor may confirm
    that something was there without confirming what it was. Supplying a
    species code or a count on a sighting that was verified without one is a
    correction, and is subject to rule 15 exactly like any other.

## Dependency direction

```text
mobile client
      |
      +-- token exchange, profile, context ------> Chisimba gateway
      |
      +-- outings, trails, sightings, sign-offs ---> field service
      |
      +-- media bytes ---------------------------> object storage
                                                  (pre-signed)
```

```text
Chisimba
      |
      +-- provisioning webhook (signed) --------> field service
```

The webhook is the only inbound call the field service accepts from Chisimba.
It carries no credential and cannot, because Chisimba has none to give. It is
authoritative only for identity, never for field data.

The mobile client holds two independent conversations. Neither service is a
proxy for the other, and a failure in one does not block the other.

Chisimba additionally reads field data from the microservice for its mentor
views. That is a third conversation and it is read-only, for the reasons given
under *Chisimba as a reader of field data*.

Base map tiles for the mentor's map view are served from object storage or a
tile server, not from either service. Tiles are a large, cacheable, public
artefact and putting them on the request path of a service that also holds
observation records would let a rendering concern compete with a write. The
mobile client caches tiles the same way and degrades to its vector layer when
they are absent. A field
user with no connectivity can still complete an outing, and a signed-in user with
no field data can still read their profile.

## Identity bridge

### Token validation

Chisimba signs RS256 access tokens and publishes public keys at
`/.well-known/jwks.json`. The microservice fetches that document and caches
it. The cache is refreshed on an unknown key id, so key rotation does not
require a coordinated restart, and on a periodic interval regardless, so a
removed key eventually disappears from the cache.

Validation is strict. The audience must equal the configured microservice
audience, the issuer must be a configured trusted issuer, the algorithm must
be RS256 with no `none` acceptance, and expiry is checked with a small
tolerance. A token failing any check is rejected without revealing which check
failed.

The microservice does not call back to Chisimba to validate a token. Callback
validation would put Chisimba on the critical path of every field request,
which defeats the split.

### Claim mapping

The token carries the identity the microservice needs, so that the common
request path requires no Chisimba call.

- `sub` is the Chisimba user id and becomes the microservice user id.
- `scope` becomes the microservice permission set.
- `ctx` is the active context. It selects the context a new record is written
  into, and nothing else.
- `ctxs` is the bounded set of contexts the caller holds a grant in. It is
  what read access is checked against.
- `roles` distinguishes guide, trainee and mentor.
- `epoch` is the caller's identity epoch, used for revocation.
- `ver` is the token's own unique id.

`ctx` and `ctxs` are separate on purpose. Using the active context as the data
boundary would hide a guide's northern-reserve drives while they happen to be
working in the southern one, and would make a field user's visible history
depend on which context their phone last logged into. Access is a property of
the grant set; the active context is only a default for new records.

Context codes are short and their number is small, so carrying the grant set in
the token is affordable and keeps the request path free of callbacks. The set is
capped and a caller exceeding the cap is refused at token issue rather than
silently truncated, because a truncated grant set looks identical to a smaller
one and would deny legitimate access with no explanation.

Groups and permission rows are not carried in the token. They are too large and
too volatile. Where a request needs a decision the scopes do not cover, the
microservice asks Chisimba through a gateway route rather than reading
Chisimba tables.

### Context ownership and isolation

Every record carries its own `context_code`, written when the record is created
from the caller's active context. Ownership is a property of the record, not a
property of the token, and it is never inferred at read time from whoever is
asking. A record whose `context_code` is absent, null or not in the caller's
grant set is not readable, not writable and not countable, and that check is
applied in the query rather than in application code after the rows have
already been read.

Isolation is enforced in three places, and all three are required. Row-level
filtering by `context_code` on every query, including counts and feeds, because
a filtered-in-application leak is still a leak. Row-level security in the
database as a second line, so a forgotten predicate does not become a
disclosure. And a composite index led by `context_code`, so isolation is not
paid for with a full table scan.

Scope and context are checked independently. Holding the `sightings:write`
scope is worthless without a grant in the record's context, and holding a
context grant is worthless without the scope. Neither implies the other.

Writes do not accept a caller-supplied `context_code`. The record is written
into the caller's active context, and a caller that wants to write into a
different context has to switch to it first, which Chisimba already governs.
Accepting the field from the request would let a caller file a record wherever
it suited them, and would defeat the ownership the record depends on.

Trainee visibility follows the outing, not the sighting. A trainee reads the
sightings of outings they are recorded on, in the outing's context. That is
narrower and simpler than granting per-sighting access, and it matches how a
logbook is actually read.

A route is not a separate entity. It is an attribute of the outing, so the
question "who may see this route" resolves to "who may see this outing", and the
outing's context, guide and trainee list already answer that. A route with no
outing has no owner and no meaning, and nothing here authorises one.

Co-participants on the same outing may read the shared outing record, including
the route. A trainee who walked it has a stronger claim to the line than anyone
else on that outing, so withholding it from them would be both surprising and
pointless. Mentor-only notes and verification state are not part of that shared
record.

A trainee reads only the outings they are recorded on. This is the one genuine
asymmetry in the model, and it exists because a trainee has no basis on which to
judge someone else's identification or hours, whereas the teaching staff do.

Mentors and administrators are the teaching staff, and both read the whole
exercise. A mentor or an administrator in a context reads every outing in that
context, every route drawn on them, every sighting recorded against them, and
the verification and sign-off state attached to each. Grading a trainee's
competence requires seeing the exercise as a whole rather than only the records
that trainee authored, because the judgements being made are judgements about
the whole of their work.

This is also what Chisimba already does elsewhere. Assignment submissions are
visible to the submitting student, to authorised instructors and to
administrators, and the same relationship applies here rather than a narrower
one invented for field data.

The boundary that remains is the context, not the role. A mentor or
administrator reads the contexts they hold a role in and not others, because a
reserve's guides and its dangerous-animal encounter locations stay separable
between reserves and between cohorts. Access is resolved through the caller's
grants, never from a role name present in the request.

### Identity projection

The microservice maintains a local projection of identity: user id, display
name, active context, roles, and projection timestamp. It exists so that a
sign-off can be rendered months later without a live Chisimba call.

The projection is a cache, and it is **not** an authorisation input. Refreshing
it cannot invalidate a token that has already been accepted, and this design
does not claim that it does. A stale projection may render an out-of-date
name; it never grants or withdraws access. Revocation is a separate mechanism,
described next, and conflating the two is how a deactivated user ends up with
an empty display name and full access.

### Revocation

Authorisation normally comes from the token claims alone, because that is what
makes the split viable: no Chisimba call on the request path. That leaves a
real gap, which this section closes rather than glosses over. A token issued
before deactivation remains cryptographically valid until it expires, so a
plain claim-only model would keep a deactivated user inside the reserve until
that expiry.

Revocation is therefore a per-request check against a local denylist, fed by
the provisioning webhook. It is cheap, it is local, and it does not put
Chisimba back on the request path.

Each denylist entry records the user id, the time of revocation, and the
identity epoch in force at that moment. Chisimba carries an epoch per user and
bumps it whenever access materially changes. A request is refused when either
the token's epoch is lower than the recorded epoch, or the token was issued
before the recorded revocation time. Both checks are needed: the epoch catches
a newly issued token, and the timestamp catches a token minted before epochs
existed.

The exposure window is bounded and stated rather than hoped for. Access tokens
are short-lived, and a denylist entry can be discarded once no token issued
before it can still be valid. The field service therefore reports deactivation
as effective within one access-token lifetime, and that figure is configured,
measured and published rather than assumed. A deployment needing immediate
revocation shortens token lifetime; it does not add a callback.

Reactivation clears the entry and lets normal authorisation resume. Because a
bumped epoch invalidates every older token, a user who is deactivated and
reactivated must be issued a fresh token; an old one is not accepted merely
because the denylist entry expired.

Role reduction that is not a deactivation, such as losing mentor status, is
handled the same way through the epoch, because a claim-only model would
otherwise keep the old role for the remainder of the token's life.

Revocation applies to the token, not to the projection, and the two
mechanisms are documented separately so that neither is mistaken for the
other.

## Chisimba-side endpoints

These are served through the gateway described in `api-gateway.md`, and are
registered as routes rather than as a bespoke controller.

### Token exchange

`POST /api/v1/auth/token`

Credentials are verified through the existing native authentication stack,
including abuse evaluation and MFA policy. On success an access token and a
refresh token are returned. When policy requires MFA, a challenge is returned
instead of a token, and there is no route that converts a challenge directly
into a token without satisfying it.

The exchange is rate limited more tightly than general traffic, because it is
the credential-bearing route.

### Token refresh

`POST /api/v1/auth/refresh`

Accepts a refresh token and a device identifier, and returns a new access
token. Refresh tokens rotate on every use. Presenting an already-rotated
refresh token revokes the whole family for that user and device, on the
assumption that reuse means the token was captured.

### Profile and context

`GET /api/v1/user/profile`

Returns the caller's identity, active context, context grant set, roles,
effective scopes and identity epoch. It is the one call the mobile client makes
on every successful start, and the response is cacheable for the access token's
lifetime.

The grant set is included because the client needs it to decide what the user
can see and which context to default to. It is present for display and
navigation; it does not authorise anything, because the token it arrives with
already did, and a client-supplied context is never trusted server-side.

### Competency and species reference

The species and competency catalogues are small, slow-changing, and needed
offline. They are served from the microservice rather than from Chisimba,
because they belong with the field data and must be bundled into the offline
cache without a second round trip.

### Implementation status of the gateway surface

The gateway currently exposes `GET` and `POST /api/v1/auth/login` and
`GET /api/v1/auth/me`. The names specified above are `POST /api/v1/auth/token`,
`POST /api/v1/auth/refresh` and `GET /api/v1/user/profile`. The gateway is to
be brought into line with this contract rather than the contract relaxed to
match the implementation, because the contract names are the ones a third
party reading the document will build against.

`POST /api/v1/auth/refresh` is not implemented at all, and until it is the
mobile client holds a refresh token that nothing can use: a stored session
silently stops working at access-token expiry rather than refreshing. The
whole-family revocation on replay, and the device identifier the refresh
accepts, are part of that endpoint and are not optional simplifications.

A mobile client written against the current gateway works today. It must not
be read as evidence that the gateway is correct.

## Chisimba as a reader of field data

Chisimba presents field data to mentors: the maps, the trail logs and the
sightings accumulated by trainees across an exercise, and the reviews those
reviews then produce. It does so as a **reader**.

Chisimba does not store outings, trail logs, sightings, waypoints or sign-offs,
and the microservice is the only writer of any of them. Where Chisimba needs
field data it calls the microservice's API and renders the response. Where a
mentor submits a review, that review is a command sent to the microservice,
which owns the sign-off and applies the revision and retention rules to it.

This is the boundary that makes the rest of the design hold. If the
microservice wrote field data into MDB2 tables then two processes would write
one body of records, there would be no sole authority to resolve a revision
conflict against, and rule 4's one-way replication would be abandoned. A
read-only Chisimba costs a network hop per page and preserves every other
guarantee.

A projection into a Chisimba reporting schema was considered and rejected for
the same reason: it is a second copy, and a second copy drifts. Reading the
API is boring and keeps one authority.

The consequence to accept deliberately: a Chisimba mentor view is unavailable
when the microservice is unavailable. That is a display outage, not a loss of
records, and the mobile clients are unaffected by it in either direction.

## Microservice-side endpoints

The service is specified as OpenAPI 3.1. The shapes below are the contract;
the full document is the normative source and is generated alongside this
architecture.

### Outings

An outing is one guided activity in a reserve. There are three kinds, and they
are different records with different required fields rather than one record with
some fields that happen to be absent:

| `kind`  | what it is | required beyond the shared fields |
|---------|------------|-----------------------------------|
| `drive` | a game drive in a vehicle | `duration_hours`, `guest_count` |
| `hike`  | a walking trail on foot | `rifle_role`, `walk_length_km` |
| `camp`  | a camp, stationary | none, and it must carry no route |

A hike additionally records `hours_walked`, a description of the walk and the
lessons learned. A camp records a site name and any facilities. `rifle_role` is
one of `first`, `second` or `neither`, and `neither` is a real answer for a
participant who was on neither rifle — distinct from leaving the field blank.

The requirement that these be separate records is load-bearing. A single table
with a `kind` column and nullable per-kind columns would make a hike with no
rifle role a valid row that is simply missing a field the programme requires, and
the omission would be discovered at assessment rather than at entry. Every other
required field in this contract works the same way.

So the shape is one `outing` table holding what all three share — identity,
context, participants, times, route, notes, lifecycle — and a per-kind detail
table holding what is genuinely required for that kind. An outing of kind `hike`
has a `hike_detail` row; an outing of kind `drive` has a `drive_detail` row.
A sighting, trail log or media record names exactly one outing, so it has one
parent and there is no polymorphic reference to resolve.

A camp has no route because a camp does not move, and `route` is left null for
it rather than carrying an empty line. Sightings may be recorded against a camp:
animals at a camp are what a guide sees, and there is no technical reason for
that encounter to be the one kind that cannot be kept.

```text
GET    /api/v1/outings                list, filter by kind, status, guide, trainee, date
POST   /api/v1/outings                create, guide only
GET    /api/v1/outings/{id}           read
PATCH  /api/v1/outings/{id}           update, planned outings only
POST   /api/v1/outings/{id}/start     move planned -> active
POST   /api/v1/outings/{id}/end       move active -> completed
POST   /api/v1/outings/{id}/cancel    move planned or active -> cancelled
```

Cancellation is a first-class operation rather than a delete. An outing that ran
for two hours and was abandoned is part of the record: its sightings, trail
logs and media stay, and the outing is marked cancelled with a reason and the
time. Deleting it would destroy real observations, and leaving it `active`
would leave the guide unable to start another outing.

Outing state transitions are enforced server-side. An outing cannot be started
twice, and a completed outing cannot be re-opened except through the seal
described below. Illegal transitions are a 409 with the current state in the
body, so a client that queued an offline action can reconcile rather than
guess.

The `GET /api/v1/drives` paths are withdrawn rather than aliased. An alias would
have to guess a kind for a path that no longer carries one, and a client built
against an alias would be reading a drive out of something that might be a camp.

### Trails

```text
GET    /api/v1/outings/{id}/trails    list
POST   /api/v1/outings/{id}/trails    append a trail log
```

A trail log carries ordered waypoints and any observations made along the
route. Appends are additive; a trail log is not rewritten, because the
sequence of observations is itself the record. This is the one entity where
ordering is the content, so a revision conflict on a waypoint list is resolved
by appending rather than by refusing.

### Sightings

```text
GET    /api/v1/outings/{id}/sightings  list, filter by status and species
POST   /api/v1/outings/{id}/sightings  record a sighting
PATCH  /api/v1/sightings/{id}         verify, reject, or correct, mentor or guide only
```

Verification is a distinct permission from creation, and it is the moment the
system teaches rather than records. A trainee records a sighting as a claim; a
mentor or guide answers it. So a verification that corrects a species code or a
count **requires** an explanation, and the original recorded values are
retained beside the corrected ones permanently. A client that shows only the
corrected value has erased the trainee's reasoning along with their mistake,
and there is nothing left to learn from.

`needs_review` exists so a mentor can say honestly that a claim was examined
without committing to a verdict. `rejected` means examined and found wanting,
and it is recorded as an examined claim rather than as an absence, because a
trainee who was wrong and told why has learned something a trainee whose entry
simply vanished has not.

The notes are teaching content, not an audit field. That has one consequence
worth stating here: the correction is refused without a reason, so that a
silent overwrite cannot be represented as a completed review.

A verification carries its own `operation_id` and is refused outright against a
stale revision, because it is an authority decision rather than an edit.

Creation accepts a sighting with no `species_code` and no `count`, because rule
21 makes both optional until verification. Nothing about recording a sighting
requires having identified it, and requiring it at capture would push the
trainee to guess rather than to record. The response echoes the sighting with
whatever the server retained, so a client can show what was actually saved
rather than what was typed.

Verification of a sighting with no species records the sighting as observed
without an identification. That outcome is refused as though it were a
half-finished record: it is a complete claim, that something was there, and it
carries `verified_at` and a verification note as any other decision does.

### Sign-offs

```text
GET    /api/v1/trainees/{id}/signoffs     list
POST   /api/v1/signoffs                   create as draft, mentor only
PATCH  /api/v1/signoffs/{id}              edit draft or changes-requested
POST   /api/v1/signoffs/{id}/submit       draft -> submitted
PATCH  /api/v1/signoffs/{id}/review       approve, reject, or request changes
```

A sign-off measures competence, and it is formative in how it reaches a
decision. The system is not measuring a trainee to rank them; it is
establishing whether they can guide safely and independently yet, so that the
gaps can be named and the next drive can be aimed at them. The published
thresholds are the criteria, and readiness against them is reported to the
mentor, but the approval is the mentor's act and not the system's.

A sign-off records a mentor's assessment of named competencies against a
specific outing, with evidence for each. Which kind of outing is up to the
assessment: a walk is a legitimate thing to be signed off on, and refusing
that would quietly make the drive the only activity that can teach.

The workflow is `draft`, `submitted`, `changes_requested`, `approved`,
`rejected` or `expired`. Submission is a separate operation rather than a
field set at creation, because the moment of submission is what starts the
review clock and it is the moment a mentor commits to looking at it. Editing is
permitted only while a sign-off is `draft` or `changes_requested`, which is
what makes "request changes" an actionable state rather than a dead end: the
trainee reopens it, revises, and resubmits, and the whole cycle is visible.

That revision cycle is a deliberate pedagogical choice, not a convenience. A
scheme allowing only approve or reject would teach a trainee to withhold
anything they were unsure of, which is precisely the wrong instinct in someone
who will one day make calls about dangerous animals.

A rating is never a standing grade. Assessments are retained per outing and per
competency, so a trainee `competent` in navigation is visibly not yet
`proficient` in it, and the record shows which gap the next outing should
address. Collapsing this into one grade per trainee would discard the only
information that makes the history useful.

A submitted sign-off that is not reviewed within the configured window expires
rather than remaining indefinitely actionable, so that competency records do
not silently become stale. Expiry is applied by a server-side sweep and is
reported as a state change, not inferred by the client from a date. It is
offered back to the mentor rather than closed against the trainee, since the
trainee did not fail to be assessed; they failed to be assessed in time, and
the two must not read the same way on the record.

Approving a sign-off seals the outing it refers to.

### Media

```text
POST   /api/v1/media/upload             initialise, returns a pre-signed upload
POST   /api/v1/media/{id}/confirm       confirm the object arrived
GET    /api/v1/media/{id}               metadata and a pre-signed download
DELETE /api/v1/media/{id}               soft delete
```

Binaries never transit the service. The client requests an upload, receives a
pre-signed URL scoped to one object key, and uploads directly. The service
records metadata and marks the object pending until the client confirms.

Confirmation verifies that the object is actually present at the storage
layer before the record becomes visible. Without it, an upload that failed
silently leaves a media record that renders as a broken image in a field
report, and the guide has no way to tell that from a deleted one.

### Reference data

```text
GET    /api/v1/species            list and search
GET    /api/v1/species/{code}     one species
GET    /api/v1/competencies       list by category and level
```

These are bundled into the offline cache and refreshed opportunistically.

The species catalogue is reference data rather than field data. It carries no
`context_code` and no row-level security, because a lion is a lion in every
reserve and there is nothing about it to isolate. A per-context catalogue would
also mean a device caching one copy per context, which is the opposite of what
the offline bundle is for.

`species_code` is four uppercase letters and is **not** a foreign key to that
catalogue. A sighting naming a code this installation has not been told about is
accepted and stored, because refusing it would mean losing a real observation to
keep the catalogue tidy. The consequence is stated rather than left to be
discovered: a mistyped code lands, and lands unsearchable. `LEOP` and `LEOP ` are
different codes and only one of them is a leopard.

### Synchronisation

```text
POST   /api/v1/sync/pull         changes since a cursor
POST   /api/v1/sync/push         a batch of client changes
```

The contract is specified in full under synchronisation below.

### Health

`GET /health` is unauthenticated and reports liveness and readiness only. It
returns no version of internal state, no dependency detail and no counts.

## Provisioning

Chisimba emits a signed webhook to the microservice when identity changes
materially to field access: user created, user updated, user deactivated,
user deleted, or role changed.

The webhook carries an event type, a timestamp, and the identity fields the
microservice needs. It is signed with an HMAC over the raw body, and the
microservice verifies the signature before parsing, using a constant-time
comparison. Replay is prevented by rejecting timestamps outside a short
window.

Webhook delivery is at-least-once, so handlers are idempotent. Delivery
failure does not fail the Chisimba operation that triggered it; the identity
change is committed in Chisimba and the projection self-heals on its next
refresh, with an alert raised if it does not.

The webhook carries no credential material. It cannot, because Chisimba has
none to give.

A revocation event additionally carries the identity epoch in force at that
moment, which is what lets the receiving denylist reject tokens the user may
still be holding. A webhook that omitted the epoch could only ever stop future
tokens, which is not the same as stopping the ones already issued.

The epoch is bumped by Chisimba before the webhook is sent, never after, so
that a token issued concurrently with a deactivation cannot be minted from the
pre-revocation epoch and slip through the gap between the two.

## Media handling

Upload is a three-step flow: initialise, upload to object storage, confirm.

Initialise returns a pre-signed URL valid for a short window and scoped to a
single object key, together with a media id. The client uploads directly. The
client confirms, and only then is the media considered attached and visible to
other participants.

Uploads that are initialised but never confirmed expire and are swept. A media
record whose object is missing is reported as unavailable rather than as a
broken image, so the client can distinguish the two cases.

Downloads are pre-signed and short-lived, so a shared link does not become a
permanent public URL for field photography.

Size and type are validated at initialise, and the service enforces a maximum
that the client is told about in advance rather than discovering by rejection.

## Synchronisation

### Model

The client is offline-first. It writes locally, shows the result immediately,
and reconciles later. The protocol therefore has to describe partial success
honestly rather than presenting a batch as atomic.

Each client change carries:

- an `operation_id`, generated on the device, which is its retry identity;
- an entity type and an entity id generated on the device;
- an operation, expressed as an intent such as create, update, delete, start,
  end or verify, rather than as a whole-entity replacement;
- the `base_revision` the client edited against, for updates;
- `captured_at`, the device's record of when the observation happened;
- optionally `depends_on`, the `operation_id` of a prerequisite in the same
  batch;
- the payload.

The operation is an intent rather than a replacement because an outing's end
carries an end time and a summary, not the entire outing as the client last saw
it. Replacing the entity would overwrite fields the client never intended to
touch, which is a conflict dressed up as a successful write.

The client records the entity id locally, so a record created offline is
referenced by the same id before and after it reaches the server.

Pull takes a cursor and returns a page of changes since it, plus the next
cursor, whether more remains, and the server time. The cursor is opaque to the
client, so the implementation can change without a client release. It also
returns tombstones, so deletions propagate.

Push takes a bounded batch and returns one result per submitted operation, each
either applied with the new revision, applied as a no-op because the operation
id was already processed, deferred because a declared prerequisite has not
settled, or refused with a stable error code and, for a revision conflict, the
current server state. A batch is not all-or-nothing: one malformed sighting
does not discard the four valid ones alongside it.

Every change in the feed carries `revision` and `recorded_at`, so a client can
tell what it has and has not applied and resume without guessing.

### Idempotency

Retry identity is an **operation identifier**, not an entity identifier. This
distinction is load-bearing: creating an outing, starting that same outing and
ending it are three different operations against one entity, and a client that
retried "the outing" after a timeout would collapse them into one. Each
operation therefore carries a client-generated `operation_id`, unique within
the calling client, and the server records the outcome against that id.

Replaying an `operation_id` returns the originally recorded outcome and does
not apply the operation twice. The server retains the outcome, including a
rejection, so a client that retries a refused operation gets the same refusal
rather than a fresh evaluation that might differ. Retained outcomes expire
after a window that comfortably exceeds any realistic retry period, and a retry
arriving after that window is refused as unknown rather than silently
reapplied.

Idempotency is scoped to the calling client, so two clients cannot collide on
an `operation_id`, and an `operation_id` is never accepted as proof of identity
or ownership.

Operations may declare a dependency on a prior operation, by `operation_id`.
A dependent operation whose prerequisite has not been applied is not failed
immediately; it is held and re-evaluated once the prerequisite's outcome is
known, up to a bounded wait. This is what allows a queued batch containing
create-then-start-then-end, or sighting-then-media, to be submitted in one
offline burst without the server guessing at ordering.

### Conflict resolution

Conflicts are detected by **record revision**, never by comparing timestamps.

Every record carries a `revision`, an integer owned by the server and
incremented on every accepted mutation. A client submits the `base_revision`
it read before editing. The server compares that against the current revision
and has exactly three outcomes:

- the revisions match, so the mutation applies and the revision increments;
- the entity does not exist, so a create is treated as such;
- the revisions differ, so this is a genuine conflict and the server refuses.

A refusal returns the current server state, its revision, and the client's own
submitted values side by side. The client decides what happens, because only it
has the field context: a guide may keep their note and take the corrected
count, a trainee may be told the record was already verified. The server never
merges automatically, and it never discards the client's edit silently. Losing
a field observation without trace is the one outcome this design exists to
prevent.

**Capture time and receipt time are separate and neither decides a conflict.**

`captured_at` is when the observation happened, recorded by the device.
`recorded_at` is when the server accepted it. They diverge constantly, and
that divergence is the whole reason timestamps cannot arbitrate: a device that
has been offline for three days submits an edit captured this morning, and a
device with a fast clock submits stale data that arrives last. Using arrival
order to decide a conflict would let a slow, stale upload silently overwrite
fresher work.

The two timestamps therefore serve display, ordering and reporting only. The
revision decides the outcome. This is the same revision-based concurrency the
`knowledgemap` module already uses, and it inherits that behaviour rather than
inventing a second mechanism.

Verification is never resolved this way at all. Verification is an authority
decision, so a client submitting against a stale revision is refused
unconditionally, whether it thought it was recording an observation or
recording a verification.

Media attachments accumulate rather than overwrite, because an attachment is
an addition to a record rather than an edit of one.

### Deletion

Deletion is a soft delete that leaves a tombstone carrying the entity type,
the entity id, the context, the revision at deletion, and the deletion time.
Tombstones appear in the change feed so that clients remove records they still
hold, and they are retained for at least as long as a client may hold a
cursor, because a client that misses a tombstone will keep showing a record
that no longer exists.

A deletion submitted against a stale revision is refused and surfaced for
confirmation, rather than being applied to a record that has since changed.

### Late arrival on a completed outing

Ending an outing does not close it to new observations. An outing that finished on
Saturday is routinely still receiving trail logs, sightings and media on
Monday, because the device that recorded them had no connectivity at the time.
Refusing those would destroy exactly the field data the system exists to
capture.

What ends on completion is the outing's **own** fields and its **lifecycle**.
Planned times, route, notes and participants are editable only while the outing
is `planned`; `start_time` and `end_time` are each written once and never
rewritten. Child records are append-only and remain acceptable for the whole
of a configured grace window after completion.

Once the grace window closes, further child records are still accepted rather
than refused, but are flagged as late arrivals with their true `recorded_at`,
so that a completed outing can be understood. This is a deliberate preference
for keeping data over preserving an imagined invariant.

A signed-off outing is different. Once a sign-off is approved the outing is
sealed: no edits, no new children, no verification changes. Unsealing is an
explicit mentor or administrator action, recorded in the audit log, because it
invalidates the competency judgement that the seal exists to protect.

### Pagination and cursor recovery

Pull is paginated. A response carries a page of changes, an opaque `next_cursor`
and a flag saying whether more remains, plus a `server_time` the client should
record. The cursor is opaque so the implementation can change without a client
release, and the client must persist a new cursor only after the page it came
with has been applied. Persisting the cursor first loses changes on a crash
between the two.

Cursors are retained for a configured window. A cursor older than that window,
or one that cannot be interpreted, is answered with a distinct "resync
required" status rather than an empty page. An empty page would be
indistinguishable from "nothing changed" and the client would silently stop
receiving updates for data it has never seen, which is the worst possible
failure mode for an offline client. A resync tells the client to discard its
change cursors and pull the full accessible scope again.

Full resync is bounded by the caller's context grants rather than by the total
data set, so it stays proportional to what the caller could legitimately read.

### Recovery

Two failure modes need an explicit answer rather than an emergent one.

**A client that crashes mid-push** retries the whole batch. Operation
identifiers make this safe: every operation that already settled returns its
recorded outcome instead of being applied twice, and only the unsettled ones
are attempted. Recovery therefore needs no record of which operations
succeeded, which is the point of keying idempotency on operations rather than
on batches. A client that crashed *while reading* the response retries and
reconstructs the same outcome from the operation records.

**A server restored from backup** can present a revision lower than one a
client has already seen, because the change log rolled back with it. A naive
revision check would then accept a stale edit as current, which is precisely
the failure the revision was introduced to prevent.

Revisions are therefore monotonic per record and the current value is only
advanced, never lowered, so a rolled-back row reads as "changed" to every
client holding an equal revision and every edit is refused rather than
silently misapplied. Restoring a record whose revision the service can no
longer vouch for moves it to a conflict state on next access, which a client
resolves by re-reading, rather than silently treating the older value as
current. The service detects this at startup by comparing each record's
revision against the highest revision it has observed for that record, and
raises an operational alert rather than waiting for a user to hit it.

### Media in synchronisation

Media is synchronised through the pre-signed flow rather than through the
change feed, because a binary cannot be reconciled by timestamp. The change
feed carries the media record so the client learns it exists; the bytes move
separately and the record is only usable once confirmed.

## Data model reference

Identifiers are UUIDs, timestamps are RFC 3339 in UTC, and coordinates are
GeoJSON, which keeps longitude and latitude ordering explicit and unambiguous.

Every record carries the same three technical fields, and every table below
repeats them for that reason rather than leaving them to convention:

- `context_code`, the owning context, which is never inferred at read time;
- `revision`, the server-owned optimistic concurrency token described above;
- `captured_at` and `recorded_at`, the observation time and the receipt time,
  which are distinct and neither of which arbitrates a conflict.

### What was cut, and why

The initial model is deliberately smaller than a full field data system. A
field is present because a guide's logbook is required to record it, or
because a technical rule in this document depends on it. A field that would
be nice to have later is deferred rather than specced, because an unused
optional field in a contract is a field every client has to guess about and
every future version has to keep.

Removed from the first version, with the reason:

- `vehicle_id`. Not part of the training record. Vehicle allocation belongs to
  operations, and nothing in the competency or sighting workflow reads it.
- `planned_route`. A planned route can be added when routing is actually
  planned; until then the actual route is the record.
- `duration_minutes`, `distance_km`, `sightings_count`, `trails_count`. These
  were stored and labelled computed, which means two sources of truth that can
  disagree. They are derived at read time from times, route geometry and
  counts.
- `habitat_type` on a sighting. A habitat survey is a different exercise from
  a sighting log, and mixing the two makes both harder to use.
- The seven-field `weather` object. A logbook records conditions, temperature
  and wind. Humidity, precipitation rate and cloud percentage are instrument
  readings, not observations, and their collection is a separate decision about
  whether the app is reading sensors at all.
- The EXIF `metadata` object on media. The logbook needs the capture time, the
  position at capture and the device. Full camera detail is available in the
  file and does not need duplicating in the database.

The recorded-value shadow fields on sightings are kept, because correcting a
species or count without preserving the original would make a disputed
sighting unarguable, which is the opposite of what a mentoring record is for.

### Outing

The shared table. What each kind requires beyond it is in its own detail table,
and a detail row is what makes the outing that kind rather than a claim to be.

| Field | Type | Logbook requirement |
|---|---|---|
| `id` | uuid | |
| `context_code` | string | which reserve the outing belongs to |
| `kind` | enum | `drive`, `hike`, `camp` |
| `guide_id` | uuid | who led it |
| `trainee_ids` | uuid[] | who was being assessed |
| `status` | enum | `planned`, `active`, `completed`, `cancelled` |
| `planned_start_time` | timestamp | when the outing was booked for |
| `start_time` | timestamp | when it actually began, written once |
| `end_time` | timestamp | when it actually ended, written once |
| `route` | LineString | the route taken; null for a camp, which does not move |
| `weather` | string | conditions, short free text plus temperature |
| `notes` | string | the guide's own account, max 2000 |
| `cancelled_reason` | string | present only when cancelled |
| `sealed_at` | timestamp | present once a sign-off has been approved |

`planned_start_time` is the only planned value kept. An outing is booked for a
start, and the actual start is what the record is judged on; a planned finish
adds nothing once the real one is recorded.

`kind` is fixed at creation and is not editable afterwards. It is not a label:
changing a drive to a hike would mean acquiring the rifle role and walk length
that a drive never had, and there is no version of that change which is a
correction rather than a fabrication.

### Outing detail

One row per outing, in the table for its kind, keyed by `outing_id`. Exactly one
detail table applies, and the presence of its row is what the outing's kind
means in practice.

`drive_detail`

| Field | Type | Requirement |
|---|---|---|
| `outing_id` | uuid | |
| `duration_hours` | number | how long the drive ran |
| `guest_count` | integer | how many guests, not merely that there were some |

Both are nullable in storage, and that is deliberate rather than an oversight.
Outings recorded before this contract distinguished the kinds recorded neither
value, and the alternative to a nullable column would have been to invent a
duration — and an invented `0.0` is indistinguishable from a drive that really
did last no time. A *new* drive is refused without them. Read the absence as
"not recorded", never as zero.

`hike_detail`

| Field | Type | Requirement |
|---|---|---|
| `outing_id` | uuid | |
| `rifle_role` | enum | `first`, `second` or `neither` |
| `walk_length_km` | number | how far the walk went |
| `hours_walked` | number | this session, feeding the running tally |
| `description` | string | the walk in the recorder's own words |
| `lessons_learned` | string | what the trainee takes from it |

`rifle_role` is not null and the blank is not a value. "Neither" is an answer
somebody gives deliberately about themselves, and collapsing it into an absent
field would lose the difference between a participant who was not on either
rifle and a record nobody filled in.

`camp_detail`

| Field | Type | Requirement |
|---|---|---|
| `outing_id` | uuid | |
| `site_name` | string | the camp's name or description of the site |
| `facilities` | string | what is there, short free text |

A camp carries no route. Sending one is refused rather than ignored, so that a
client which has drawn a line for a stationary activity finds out at the point of
entry instead of storing a route that describes nothing.

### Trail log

| Field | Type | Logbook requirement |
|---|---|---|
| `id` | uuid | |
| `context_code` | string | |
| `outing_id` | uuid | the outing it was recorded on |
| `waypoints` | array | the route as it was actually walked or driven, ordered |
| `observations` | array | what was noted along the way, in order |
| `lessons_learned` | string | what the trainee takes from the session, their own words |
| `hours_walked` | number | this session, feeding the running tally |
| `rifle_role` | enum | `first`, `second` or `neither` |
| `created_by` | uuid | who was recording |

A waypoint carries `sequence`, a Point, `captured_at`, and optionally altitude,
speed and accuracy. Accuracy is carried rather than discarded because a
guide's confidence in a position is part of the record, and a fix rounded to
the grid is not evidence of the same thing as a fix taken under tree cover.

An observation carries a type of `spoor`, `vocalization`, `visual`, `scent` or
`other`, a `captured_at`, a location, an optional description, optional media
references and an optional preliminary species hint. The hint is explicitly
provisional: it is what the trainee thought it was before a mentor corrected
it, and it is retained when the correction lands.

`lessons_learned` is separate from `observations` because they are different
kinds of statement. An observation is a claim about the world; a lesson is the
trainee's account of what that claim taught them. Folding one into the other
would lose the distinction between what was seen and what it meant.

It is the trainee's own reflection and cannot be verified, because it is not a
claim to be checked. It is not optional either. A session that recorded
everything and reflected on nothing has captured data without teaching anyone
anything, and the field is where that habit has to start.

`hours_walked` and `rifle_role` feed the running tallies, which are computed
from accepted records and never stored as counters. First-rifle, second-rifle
and participant hours accumulate separately, because a trainee's hours are not
interchangeable between roles: time spent walking in first rifle is evidence of
something different from time spent walking as a participant, and a single
combined total would overstate both.

### Wildlife sighting

| Field | Type | Logbook requirement |
|---|---|---|
| `id` | uuid | |
| `context_code` | string | |
| `outing_id` | uuid | the outing it was recorded on |
| `species_code` | string | four uppercase letters, optional until verified |
| `count` | integer | how many, not merely that it was seen, optional until verified |
| `location` | Point | where |
| `location_accuracy_m` | number | how precisely |
| `distance_m` | integer | observer to animal |
| `bearing_deg` | integer | direction of the animal from the observer |
| `behaviour` | enum | what it was doing |
| `age_sex_class` | enum | who was seen, where separable |
| `notes` | string | free text, max 2000 |
| `status` | enum | `pending`, `verified`, `rejected`, `needs_review` |
| `verified_by` | uuid | who decided |
| `verified_at` | timestamp | when they decided |
| `verification_notes` | string | why, max 1000 |
| `recorded_species_code` | string | original, retained on correction |
| `recorded_count` | integer | original, retained on correction |
| `late_arrival` | boolean | recorded after the completion grace window |

`distance_m` and `bearing_deg` are the two fields most likely to look like
over-specification and are the two a field protocol most needs. Recorded
together they let a later reviewer tell a genuinely new sighting from a
second sighting of the same animals logged twice from a different seat, which
is the single most common error in a sighting log. They are optional because a
close sighting is often better recorded without a distance guess.

`behaviour` is an enum because a behaviour tally across a drive is a
training exercise in itself, and free text would not support it.
`age_sex_class` is kept because a mentor assessing identification skill needs
to know whether the trainee separated a bull from a cow, not merely that an
animal was seen.

`needs_review` is kept as distinct from `pending`: pending means nobody has
looked, needs review means somebody competent looked and was not willing to
decide. Collapsing them loses the record of a mentor's uncertainty.

`species_code` and `count` are optional until the sighting is verified, and
this is the one place in the schema where optionality is conditional on
another field rather than fixed. A trainee in a vehicle sees something they
cannot name; the only honest record of that is a sighting with no species and
nothing invented to fill the field. Forcing a guess at capture trains a guess,
and a logbook full of confident wrong species is worth less to a mentor than
one full of honest blanks.

A mentor may still verify such a sighting **as observed**: the claim being
confirmed is that something was there, not what it was. This is a real
decision with a real audit trail, not a placeholder, and it is recorded with
`verified_at` and a verification note like any other. It is why `verified` does
not imply a species is known.

An absent `count` is likewise not a zero. A zero asserts that the animal was
looked for and there were none, which is a different statement from not having
counted. This is the same distinction rule 18 draws between `not_observed` and a
zero, and it is applied for the same reason: an absent observation is not a
negative one. A client that renders an absent count as `0` is asserting
something the trainee did not.

Supplying a species code or a count on a sighting that was verified without one
is a correction, so rule 15 applies unchanged: it requires a reason, and
`recorded_species_code` and `recorded_count` retain whatever was there before,
including nothing.

### Sign-off

| Field | Type | Logbook requirement |
|---|---|---|
| `id` | uuid | |
| `context_code` | string | |
| `trainee_id` | uuid | who is being assessed |
| `mentor_id` | uuid | who is assessing |
| `outing_id` | uuid | the evidence this rests on |
| `competencies` | array | one to twenty assessments |
| `overall_comment` | string | the mentor's summary, max 2000 |
| `status` | enum | `draft`, `submitted`, `changes_requested`, `approved`, `rejected`, `expired` |
| `submitted_at` | timestamp | starts the review clock |
| `reviewed_at` | timestamp | when the decision was made |
| `expires_at` | timestamp | derived from submission |

A competency assessment carries a code, a rating of `not_observed`,
`developing`, `competent`, `proficient` or `expert`, and free-text evidence
drawn from the drive.

The evidence field is mandatory even when the rating is `not_observed`, where
it records why the competency could not be judged. A sign-off that can record
a rating without evidence is a self-assessment, and the whole point of the
record is that someone else can read the reasoning.

### Media

| Field | Type | Logbook requirement |
|---|---|---|
| `id` | uuid | |
| `context_code` | string | |
| `outing_id` | uuid | the outing it belongs to |
| `type` | enum | `photo`, `video`, `audio` |
| `sighting_id`, `trail_log_id` | uuid | nullable, exactly one applies |
| `filename`, `mime_type` | string | what was captured |
| `size_bytes` | integer | bounded at initialise |
| `captured_at` | timestamp | from the file, falling back to the device clock |
| `state` | enum | `pending`, `confirmed`, `unavailable` |
| `uploaded_at` | timestamp | when the object actually arrived |

`state` distinguishes a confirmed object from one that was initialised but
never arrived, which is what lets a client tell an unsynced photo from a
missing one. `captured_at` comes from the file where available, because the
device clock on a phone that has been out of coverage can be wrong in a way
that quietly corrupts every sighting taken that day.

### Client model generation

The contract is the source for the Flutter client's models. Schemas carry
`x-dart-class`, `x-dart-freezed` and `x-dart-json-serializable` extensions, and
the client generates immutable models with `build_runner` rather than hand
writing them, so a contract change surfaces as a compile error instead of a
runtime mismatch.

```dart
@freezed
abstract class WildlifeSighting with _$WildlifeSighting {
  const factory WildlifeSighting({
    required String id,
    required String contextCode,
    required String outingId,
    required String outingKind,
    required SightingStatus status,
    required GeoJsonPoint location,
    required DateTime capturedAt,
    required DateTime recordedAt,
    required int revision,
    required String createdBy,
    String? speciesCode,
    int? count,
    double? locationAccuracyM,
    int? distanceM,
    int? bearingDeg,
    SightingBehaviour? behaviour,
    AgeSexClass? ageSexClass,
    String? notes,
    String? verifiedBy,
    DateTime? verifiedAt,
    String? verificationNotes,
    String? recordedSpeciesCode,
    int? recordedCount,
    @Default(false) bool lateArrival,
  }) = _WildlifeSighting;

  factory WildlifeSighting.fromJson(Map<String, dynamic> json) =>
      _$WildlifeSightingFromJson(json);
}
```

The class must be declared `abstract`. freezed 3 and 4 generate a mixin whose
members are abstract, so a plain `class` declaration fails to compile with
`non_abstract_class_inherits_abstract_member`. This is a freezed requirement
rather than a style choice, and it applies to every generated model in the
client.

`speciesCode` and `count` are nullable here because rule 21 makes them
optional until verification. That optionality is not expressible in a freezed
model, so a client that generated `required String speciesCode` from this table
would fail to compile the moment a trainee recorded something they could not
identify, which is the single most common thing that happens in a vehicle. The
conditional requirement belongs to the server, and a client that needs to
check it before submission does so against `status` explicitly.

The model carries the table's fields plus four that are protocol rather than
field data and so do not appear in it: `capturedAt` and `recordedAt` are
required by rule 13 and are never interchangeable, `revision` is the sole input
to conflict resolution under rule 6, and `createdBy` names the author.

`verifiedAt` is a timestamp, not a status. `status` and `verifiedAt` are
distinct: a sighting can be `rejected`, which still carries a `verifiedAt`
because a decision was made at a time. Collapsing them into one field loses
the audit trail, which is the one thing a disputed sighting most needs.

## Client architecture implications

The contract is shaped by what the client can actually do offline, so these
consequences are part of the contract rather than an afterthought.

- The client stores outings, trail logs and sightings locally with their client
  ids, and treats the server as a replica to reconcile with.
- Queued changes are persisted before they are attempted, so an app kill does
  not lose a field observation. Each is stored with its `operation_id` and, for
  an edit, the `base_revision` it was made against.
- The client keeps the rejected version of a conflict rather than discarding
  it, so a guide can see what their offline edit said and why it did not apply.
  Silently dropping it is indistinguishable from losing the observation.
- The client writes a new cursor only after the page it arrived with has been
  applied. Persisting it first loses changes if the process dies in between.
- A resync-required response is a full re-pull of the caller's accessible
  scope, and the client handles it as a normal path rather than an error,
  because a long-offline device will eventually receive one.
- Media is uploaded opportunistically and separately from record sync, since a
  large binary on a poor connection must not block a small record.
- Reference data is cached aggressively and refreshed in the background,
  because a guide needs species names with no connectivity at all.
- The client shows capture time as the time of the observation and, where it
  has one, the device's own record of when it recorded it, because the two
  diverge and conflating them makes an offline entry look late when it is not.

## Versioning

Chisimba routes are versioned in the path, and a breaking change requires a
new version. Adding an optional field, adding a route, or relaxing a
constraint is not breaking. Removing a field, renaming one, narrowing a type,
or changing a state machine is breaking, and the old version is kept until no
client depends on it.

The microservice follows the same policy and additionally guarantees that a
response never removes a field within a version. Deprecation is communicated
in a response header and in the specification, with a stated removal version.

Field data is long-lived. A sighting recorded today may still be read in five
years, so the record is treated as append-preferring: corrections are recorded
as corrections, and the original values are retained rather than overwritten.

## Deployment

The microservice is a container with PostgreSQL and PostGIS for the geospatial
indexing the sighting queries need, and S3-compatible storage for media. It is
stateless, so it scales horizontally without session affinity.

Chisimba remains on its existing Apache and PHP deployment and gains no new
runtime dependency for this integration. The gateway additions are ordinary
framework services.

The two systems are deployed and versioned independently, and neither blocks a
release of the other.

## Delivery sequence

1. Publish the Chisimba signing keys and the gateway token routes, and verify
   that a third party can validate a token using only the JWKS document.
2. Build the microservice's identity bridge: JWKS fetch, cache, rotation
   handling, and strict claim validation with no Chisimba callback. Validate
   the `ctx`, `ctxs` and `epoch` claims explicitly, including refusal when the
   grant set is empty.
3. Build the revocation denylist and the epoch check, then verify the two
   claims this section depends on: that a deactivated user's existing token is
   refused, and that a token issued concurrently with the deactivation is also
   refused. Test the concurrency case deliberately rather than trusting the
   ordering.
4. Provision the identity projection, and confirm that its staleness affects
   display only. Confirm separately that it is not consulted for access, so
   that the two mechanisms cannot be confused for one another later.
5. Implement outings with the cancellation operation and the server-side state
   transitions, including the illegal transition responses and the seal that a
   sign-off later applies.
6. Implement trail logs and sightings, with client-generated identifiers from
   the start so that idempotency is not retrofitted, and with `context_code`
   enforced at the query, by row-level security, and in the index.
7. Implement the media flow end to end, including the confirm step that
   verifies the object arrived, and the sweep of abandoned uploads.
8. Implement pull and push, with partial success, operation-level idempotency,
   declared dependencies, tombstones, pagination, and the resync-required
   response for an expired cursor. Test against a client that is killed
   mid-batch.
9. Implement revision-based conflict resolution, including the rule that
   verification never resolves by revision alone, and that late children on a
   completed outing are accepted rather than refused.
10. Implement sign-offs and the competency catalogue, including the submission
    operation, the changes-requested state and the seal on approval.
11. Add the offline client against these contracts, then harden the sync
    engine under real connectivity failure rather than simulated failure.

Steps 3 and 4 are deliberately separate. Building the projection without the
denylist produces a system that displays a stale name for a user who still has
access, and step 4 alone produces one that quietly reads authorisation out of a
cache. Both failures look like working software until the first deactivation.
