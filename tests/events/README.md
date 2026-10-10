# Native event-dispatch checks

The engine owns one `ChisimbaEventDispatcher`. Modules retain their existing
`eventDispatcher->addObserver()` / `post()` calls and notification getters.
This service is synchronous and request-local; it does not deliver queued jobs.

## Contract

Named observers run before global observers. Named pending events are replayed
when an observer for that name registers; global registration does not replay
other named queues. Re-registration repeats matching pending deliveries. Callback
identity remains class plus method for object-method callbacks, including across
two instances of the same class. This prevents changing legacy activity counts.
Sender filters use exact, case-insensitive class names, not inheritance checks.
Cancellation stops subsequent delivery and replay; exceptions propagate. An empty
event name retains the historical two-bucket delivery behaviour.

Native additions are closure/invokable callback identity, immediate rejection of
non-callables, safe removal during dispatch and independent engine state. Sender
objects are retained by object identity, not by reference to a caller's variable.
PEAR singleton, nested dispatchers, custom notification classes, public internal
fields and reference-return APIs are not part of the supported native contract.
The inspected application callers do not use these APIs. External extensions
must be checked before deployment; do not add a global PEAR alias to hide a gap.

## Repeatable checks

From the framework checkout:

```sh
php tests/events/dispatcher_contract_test.php --legacy
php tests/events/dispatcher_contract_test.php
php tests/events/native_dispatcher_test.php
php tests/events/activitystream_contract_test.php ../modules --legacy
php tests/events/activitystream_contract_test.php ../modules
php tests/nativeauth/liveuser_boot_removal_contract_test.php
```

The first test runs identical assertions against the retained PEAR oracle and the
native service. The activity test executes the actual activitydb/activityops
methods from the sibling modules repository, with storage, user, feed and hub
seams. It verifies row contents and feed/publish arguments without database writes
or outbound network calls. Its `fixture-feed` stderr line is the existing feed
consumer's diagnostic, not an error.

In a configured **local** runtime, explicitly opt in:

```sh
CHISIMBA_EVENT_SMOKE=1 php /path/to/framework/tests/events/runtime_smoke.php /path/to/configured/ch
```

For a container with only app classes/modules mounted, feed the test via stdin:

```sh
docker exec -i -e CHISIMBA_EVENT_SMOKE=1 LOCAL_WEB_CONTAINER php /dev/stdin /var/www/html/ch < tests/events/runtime_smoke.php
```

This bootstraps the real engine, checks shared dispatcher identity, language
lookup, a constant SELECT and synthetic event delivery, and rejects any loaded
PEAR Event class/file. Normal bootstrap may create session/cache/log entries;
no application records are explicitly changed.

## Local validation, 10 October 2026

- 29 differential assertions pass on PEAR and native implementations.
- Nine additional native assertions pass, covering instance isolation, closures,
  invalid callbacks, removal during delivery, reentrant posts and PEAR independence.
- Five actual activity consumer assertions pass on each implementation.
- Twelve focused authentication/session suites pass: boot removal, login wiring,
  web boundary, session namespace/service, session resumption, permission service,
  web composition, login CSRF, logout renewal, authenticated form lifetime and
  remembered-login resumption.
- Actual PHP 8.5 local container passes runtime smoke; no PEAR Event files/classes
  are loaded by that check.
- Chrome checks before/after: users, groups, Module Catalogue, class administration,
  discussion administration and question banks. Headings match, authenticated
  navigation remains present, and no PHP error markers appear. Console check
  after these routes returned no warnings/errors.
- Further Chrome checks: home/site administration, administration search, group
  navigation and asynchronous Guest membership loading, and file-manager rendering.
- Anonymous certificate-verified HTTP: login returns 200 and a CSRF field; groups,
  users and catalogue return the login boundary.
- PHP log comparison, 09:10–09:22 baseline versus post-cutover from 09:22: no new
  message types and no native event errors. Existing legacy warnings remain
  (278 distinct baseline types, 174 post-change types at the comparison point).
  These counts reflect different page sets, not a claim that warnings were fixed.
- Changed/new PHP passes lint; framework diff passes whitespace checks.

Activitystreamer is not registered in the local installation. Its actual record
persistence and external hub delivery are therefore not live browser-tested;
consumer fixtures cover the mapping without installing it or sending messages.
The in-app browser rejected the local certificate; browser checks used the
already trusted Chrome session, without bypassing a certificate warning.
No fresh credential login/logout was performed in that user's browser session.
No production deployment, schema migration or vendor deletion was performed.

## Release and rollback

Branch: `release/consolidation-20260930`; changes are uncommitted alongside
pre-existing user work. Ship engine plus both native class files together.
No module-version or schema update is needed for this core implementation change.
Verify supported sites and third-party extensions before removing vendor files.
The temporary vendor retention/removal gate is in the PEAR dependency register.

Rollback only the event-dispatch engine hunk to its previous direct
`getPearResource('Event/Dispatcher.php')` load and `Event_Dispatcher::getInstance()`
assignment. The vendor files are still present. Do not reset unrelated working
changes; retain/revert the matching boot contract test with the implementation.
