# Trails programme module: architecture decision record

## Status

Accepted. This record supersedes nothing. It resolves an ambiguity that
`field-guiding-service-contract.md` and the Trails programme requirement had
created, and it is placed above that document in the documentation hierarchy,
so where the two disagree on ownership this record governs.

## Purpose

The Trails programme teaches field guiding. A trainee learns to drive, to walk
a trail, to identify what they encounter and to judge their own judgement,
and the records exist as the evidence of that learning. The web interface this
module provides is therefore an instrument of teaching, and the decisions in
this record are made on that basis: where a design choice would improve the
data model at the cost of the trainee's ability to learn, the data model
loses.

In practical terms the module records field guiding activity: game drives and
walking trails, the routes taken, the animals encountered, what the trainee
learned from it, and a mentor's assessment against it. It gives students,
mentors and guides a way to create, read, update and delete that record, and
it places routes and encounters on a map.

Two things about the framework made this non-obvious, and both were verified
against the source rather than assumed.

The approved service contract already answers who owns the field data, and the
answer is not Chisimba. `field-guiding-service-contract.md` states that
"Chisimba does not own drive state, sightings, trail logs, media binaries,
competency assessments or sign-off decisions", and that the microservice owns
them, with Chisimba owning only identity, user, context and permissions. A
Chisimba module that stores any of those five is a second owner for the same
records, which is the duplication the constitution's reusability rule and the
framework's own module guidance both prohibit.

The framework has no map. There is no Leaflet, no OpenLayers, no MapLibre and
no OpenStreetMap tile support anywhere in the tree. The only map code that
exists is dead: `modules/oer` calls the Google Maps JavaScript API version 3
over plain HTTP with a hard-coded key, and that API was withdrawn in 2018;
`modules/qrcreator` has an OpenLayers 2 picker gated behind two modules that
are not present on this branch. The nearest living precedent is
`modules/knowledgemap`, which draws its graph with hand-built SVG over the
skin primitive `.chisimba-spatial-workspace` and has no basemap at all.

## Decisions

### D1. The module is a web client, not an owner

`trailsprogramme` stores no field data. It reads and writes through the field
microservice, and Chisimba keeps its existing role as the identity, context
and permission authority.

The module owns only programme configuration that Chisimba genuinely should
own: the encounter thresholds a reserve requires, and the reserve metadata the
map needs. These are not field data. They are the rules the programme is
defined by, they are administered by administrators rather than produced by
students, and they are Chisima's own concern under its configuration model.

This preserves the existing contract rather than replacing it, and it keeps a
single owner per record. The alternative, a module that owns its own copy of
drives and sightings, was rejected for that reason and not for the cost of the
schema.

### D2. Encounter thresholds are Chisimba configuration, snapshotted at submission

Thresholds are the number of encounters with each dangerous species a trainee
must accumulate on foot before a trail sign-off can pass. The numbers are
administrative and change between intakes, so they belong in Chisima's
configuration surface where an administrator manages them, not in the
microservice's field data.

A threshold row carries the context, the species code, the required count and
whether the encounter must have been on foot. Pass is computed live against
the trainee's tallies, so an administrator raising a threshold takes effect
immediately for the next sign-off.

When a log is submitted, the applicable threshold set is copied into the
submitted record as an immutable snapshot. Without this, raising a threshold
in 2027 would silently change what a 2026 sign-off was assessed against, and
the trainee would have no record of the standard they actually met. This is the
same reasoning the service contract applies when it retains the recorded
species code on a corrected sighting.

The snapshot is written once, at submission, and is never edited. A corrected
threshold is a new configuration effective from a stated date.

### D3. Leaflet is bundled locally, as a recorded exception

Leaflet is added to `trailsprogramme/resources/vendor/leaflet/` and served by
the site. It is not loaded from a content delivery network, because a CDN would
make the reserve-facing application depend on third-party availability at the
moment it is least likely to have it, and would disclose trainee locations to
that third party.

Bundling is a deliberate exception to the framework guidance that modules
should prefer native browser capabilities before introducing a JavaScript
library, and to the rule against direct vendor integrations. It is recorded
here because the framework requires such exceptions to be justified rather
than absorbed quietly.

The justification is that the requirement is a map. Not a grid, not a diagram,
not a form with two coordinate boxes. A trainee walking a reserve needs to see
where they are relative to water, roads and a known boundary, and to place an
encounter at a position they can recognise on the ground. The existing skin
primitive is a dotted pan-and-zoom surface with no geographic reference at all,
so it cannot satisfy the requirement; the alternatives that avoid a library
are a server-rendered static image per submission, which cannot support
marking a route, and numeric coordinate entry, which is unusable in the field
for the same reason it is unusable on paper.

Leaflet is used as a presentation and input surface only. It never decides
what is stored, it is not a source of truth, and every value it produces is
validated and re-derived server-side from the submitted coordinates. A client
that skips the map entirely and posts valid GeoJSON is accepted, because the
microservice, not Leaflet, owns the record.

Tile sourcing is a deployment decision this record does not make. Tiles must be
served from a source the operator controls or is licensed for the expected
volume, with attribution displayed. A public tile service must not be pointed
at by default without an operator decision recorded against it.

Leaflet is pinned to one minor version, its licence file ships beside it, and
the module's contract test fails if the bundled version is unpinned or the
licence text is missing. This keeps an unmaintained transitive dependency
visible rather than quietly drifting.

The tile source is constrained by a content security policy that allows
connections only to the configured tile origins, and the module adds no
third-party origin to that policy at runtime. Without this, a single stored
scripting defect anywhere in the installation could repoint the map at a
host of its choosing and observe which areas and which reserves a trainee is
looking at, which discloses the programme's locations to whoever reaches the
defect. Tile URLs are therefore never taken from a stored string unvalidated:
the stored value is an identifier resolved against configuration, and a row
edited to contain a URL rather than an identifier fails resolution and renders
no map rather than fetching it.

Tiles themselves are not integrity-pinned, because that is not a property tile
servers offer. The mitigation is the origin restriction and the operator's
choice of source, and the limitation is stated rather than implied.

The bundled Leaflet is same-origin and served by the site, so integrity
pinning is not meaningful there either; the control that matters is that the
version is pinned and the file is reviewed as source.

### D4. The browser session is exchanged for a short-lived token, server-side only

This is the mechanism the earlier gateway plan did not cover, and it is the
part of D1 that is easy to get dangerously wrong.

A Chisimba web user holds a PHP session, not a bearer token. The microservice
validates Chisimba-issued JWTs and has no way to accept a session cookie, and
it must not be given one, because a browser session is not a credential that a
backend is entitled to present on a user's behalf to a third party.

The module therefore needs a token for the signed-in user, obtained through the
gateway. The requirement is that the token never reaches the browser and is
never held by the browser, because a token in browser memory is a token a
cross-site scripting defect can read.

The design is a **back-channel exchange**. A new gateway route accepts the
existing session plus the existing CSRF token and returns a short-lived access
token minted for the same user, with the same scopes, the same context
grants, and the same identity epoch the session already carries. The module
holds that token in server memory for the duration of the request and forwards
it to the microservice as a bearer token on the user's behalf.

The token is minted per request, at the moment an outbound call needs it, and
discarded when the request ends. It is not cached, not stored in the PHP
session and not persisted. A session is written to disk, is shared across a
user's concurrent tabs and outlives the request, so a bearer token held there is
a bearer token on disk that survives the response. Minting per request costs one
local call and removes the need for a refresh, an expiry check and a purge.

The route is authenticated by session and additionally requires a CSRF token,
so it cannot be driven by a third-party site that has induced the browser to
issue the request. It is refused when the session has not completed
multi-factor authentication, because a token that outlives a session which
itself has not completed MFA would let an MFA challenge be bypassed by
exchanging the intermediate session instead of answering it. It is
rate-limited tighter than general traffic. It records the issue in the
gateway audit log, because it is the point at which a session becomes a
bearer credential.

The token's lifetime is short enough that a token minted for one request cannot
outlive the work it was minted for. A consumer that needs a burst of outbound
calls obtains one token and uses it for all of them within that request; a
consumer that needs to continue past the token's lifetime obtains another. The
lifetime is configuration rather than a constant, so an operator can shorten it
without a deployment, and the module purges any local variable as soon as the
request's outbound work is finished rather than waiting for garbage collection.

Because the token is minted and used entirely server-side, a scripting defect
in any page cannot obtain it. The token grants nothing the session did not
already grant, so this is a change of representation rather than an elevation,
and the microservice needs no new trust relationship and no delegation
protocol.

The gateway plan's existing rule that claims are assembled from the token
record and never from the request already covers the widening case. This route
adds no caller-supplied claims, and it does not issue a refresh token, because
a long-lived credential derived from a session is a second credential to
revoke and the module can re-exchange cheaply.

### D5. The interface is built for learning, not for data entry

This is a teaching application. A trainee is learning to be a field guide and
is under observation while they practise, so the interface has obligations that
a record-keeping interface does not.

**Feedback is the product.** The most valuable thing this module shows is a
correction and the reason for it. Where the service returns a corrected
species code, the module presents the trainee's original claim beside it and
the mentor's explanation beside that, never the corrected value alone. A module
that displays only the right answer marks the work and teaches nothing, and it
makes the trainee's own reasoning unrecoverable after the fact.

This is a display rule with a hard consequence: the module may not treat the
corrected value as the record to display. It has to hold both.

**Rejection is examined, not absent.** A rejected sighting is shown to the
trainee as a claim that was made, examined and found wanting, with the reason.
It is never rendered as though it were never submitted, because a trainee whose
mistake has been quietly removed has been told nothing.

The two undecided states are shown differently, and the module must not merge
them. `needs_review` asks the trainee for more information, so the interface
has to say what information is missing. `rejected` states that the claim is
wrong and is closed. A trainee who cannot tell these apart cannot tell whether
their part of the work is to supply detail or to stop, and a record that leaves
them unable to tell has stalled rather than progressed.

**Progress is visible to the trainee.** Hours walked, first and second rifle
time, and encounter tallies are shown to the person accumulating them, along
with outstanding requirements for any threshold that applies. A threshold that
appears only when it is failed is a punishment and teaches nothing about what
to do next. These are computed from accepted records and never shown from a
stored counter, because a corrected sighting must not leave a trainee being
shown a total the record no longer supports.

Outstanding requirements are listed, not rendered as a progress bar or a
percentage. Four of five drawn as a bar reads as four fifths of a grade, which
is precisely the reading the programme is meant to displace, and a bar invites
comparing one trainee with another in a way a requirements list does not.

**Readiness informs the mentor; the mentor decides.** The encounter thresholds
are real criteria and a trail sign-off is genuinely assessed against them, but
the module computes and displays readiness and stops there. It never disables
an approval because a count is short, never marks a submission incomplete, and
never prevents a trainee submitting. The reasoning is that a trainee held at
nine of ten encounters has learned only that the form is a lock, whereas a
trainee whose readiness report is discussed with a mentor has been prompted to
notice what was actually there.

**Nothing is presented as a game.** The module does not render dangerous
animal encounter counts as a score to beat, and it does not imply a sighting is
worthless because it was small. The thresholds exist to certify competence. An
interface that gamified them would misrepresent what the programme is for, in
the one place where a trainee is most likely to be persuaded of it.

Concretely, and so that it can be checked rather than intended: no points,
levels, badges, ranks or leaderboards; no completion time, distance-per-hour or
encounters-per-hour in any comparison between trainees; no ordering of trainees
by tally; and no colour applied to a count to signal that it is low.

**Reflection is captured.** The module presents `lessons_learned` as a distinct
field from the sightings, and prompts for it, because what a trainee takes
from a session is part of what the programme is recording. It is not verified
and cannot be, since it is not a claim about the world.

**The mentor's whole-exercise view is required, not permitted.** Mentors and
administrators see every drive, route and sighting in a context. Grading
competence means seeing the whole of a trainee's work, and assessing one
identification means seeing the surrounding sightings that the trainee could
not. The module does not restrict this to records the trainee authored, and it
does not require an admin to be a participant to read a context they hold a
role in.

The boundary that remains is the context, and it is enforced per request
through the framework's group and permission services rather than from a role
name in the request.

## Consequences

Chisimba gains no new runtime dependency for this integration. The module's
outbound dependency is ordinary HTTPS to the microservice, and its identity
dependency is the JWKS document the gateway already publishes.

The module cannot queue a failed write. Because it stores no field data, a
submission that fails to reach the microservice is reported to the user as
failed. A local retry queue would make Chisimba a temporary owner of field
data and would create the reconciliation problem the service contract's sync
design exists to solve properly, in the place where it was designed to live.

The module is unavailable when the microservice is unavailable. It reports
that plainly rather than rendering an empty logbook, because an empty logbook
and a failed one look identical to a user and lead to opposite conclusions.

Two tables are added, both Chisima configuration. Neither holds field data.

`tbl_trailsprogramme_thresholds` records the required encounter counts per
context and species. `tbl_trailsprogramme_reserves` records the reserve
display name, the map centre and zoom for each context, and the tile source
identifier to use. Both are created through Module Catalogue registration and
carry the module's ownership in `tbl_modules_owned_tables`.

Sign-off is a microservice operation, not a Chisima one. The guide's submit and
sign off and the mentor's review are the same field-service state machine the
mobile client drives, reached through this module rather than beside it. That
is why the edit window after submission and the mentor and guide sign-off
sequence are specified in the service contract rather than here, and this
record does not restate them.

## Alternatives considered

**A Chisimba module owning drives, sightings and sign-offs, with the
microservice dropped.** Rejected. It removes the split that the offline
client, the geospatial indexing and the media handling were designed around,
and it makes PostGIS an open question with no answer in the current stack. It
remains the right answer if the offline requirement is ever withdrawn.

**A Chisimba module fronting the microservice with a shared service account
and asserting the user in a header.** Rejected. It would require the
microservice to trust Chisima's assertion of who the user is, which is a
delegation relationship, and delegation is exactly the authority the token
model was introduced to remove. A forged or replayed header becomes an
impersonation.

**A new table in Chisimba per field entity, written synchronously as a
read model.** Rejected as a second source of truth. If it were to become
authoritative it is D1's rejected option; if it is not, it is a cache that has
to be reconciled, and the service contract already solves reconciliation
properly on the client side.

## When to revisit

Revisit D3 if a skin-owned basemap primitive is ever added to
`chisimba-reborn`, since the exception exists only because the framework has
no map. Revisit D1 if the offline requirement is withdrawn, because the split
exists to serve it.

Revisit D2 if the programme moves to a different context model, since
thresholds are keyed by context.