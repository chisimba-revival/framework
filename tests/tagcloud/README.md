# Native tag-cloud migration

The utilities tagcloud service now renders a semantic list without loading
PEAR HTML_TagCloud. Ship its class, utilities register.conf (2.019), and the
shared chisimba-reborn stylesheet together. No database/schema changes are needed;
normal Module Catalogue updates can record the new module version.

## Preserved and changed contracts

Preserved: buildCloud(array), incremental addElement(), the FAQ biuldAll()
spelling, exampletags(), alphabetical link ordering and caller-provided targets.
Repeated builds are stable; buildCloud continues to append elements as before.
Use newObject or clearElements() for a separate cloud. The timestamp parameter
remains accepted but does not fade colours. buildAll() is the canonical spelling.

Intentional changes: blank labels are omitted, labels/attributes are escaped,
invalid UTF-8 uses replacement characters, and unsafe/empty URLs render plain text.
Relative, protocol-relative and HTTP(S) links are supported; control characters,
spaces, backslashes and other schemes are rejected. Empty clouds return an empty
string instead of a hard-coded English message. Negative, invalid and non-finite
weights become zero. Equal weights receive a neutral size; five bounded size
bands use square-root scaling. Shared skin tokens replace per-instance inline CSS.

The old public `tags` PEAR object is gone. No inspected first-party consumer used
it. External extensions reaching into that object need migration before release;
there is no HTML_TagCloud compatibility alias. Permission filtering, counts and
underlying tag storage remain unchanged and owned by callers. This renderer must
only receive data the caller is authorised to display.

Vendor HTML/TagCloud.php is retained temporarily for the differential assertion
and rollback, not loaded by normal native rendering. Remove it from source and
assembled outputs after supported-site/external-extension verification; replace
the oracle assertion with a licensed fixture or fixed expected output then.

## Re-run

From framework:

```sh
php tests/tagcloud/native_tagcloud_test.php
php tests/tagcloud/caller_contract_test.php ../modules
CHISIMBA_TAG_SMOKE=1 php tests/tagcloud/runtime_smoke.php /path/to/configured/local/ch
```

Container alternative:

```sh
docker exec -i -e CHISIMBA_TAG_SMOKE=1 LOCAL_WEB_CONTAINER php /dev/stdin /var/www/html/ch < tests/tagcloud/runtime_smoke.php
```

The runtime check creates synthetic in-memory clouds through the actual engine
and verifies isolation and absence of the PEAR class. It writes no application
records; normal bootstrap can write sessions/logs/cache. The caller test executes
FAQ's real getTagCloud() method and the file-manager template with fixture data.
The vendor comparison uses equal timestamps because varying timestamps expose
an existing PHP 8 fractional colour-index deprecation in the PEAR implementation.
Native tests run with warnings converted to exceptions, without suppression.

## Evidence — 10 October 2026

- 36 native assertions pass, covering empty/Unicode/invalid labels, URL filtering,
  weight bands, equal/invalid weights, ordering, repetition and valid PEAR link
  equivalence; three real FAQ/file-manager caller assertions pass.
- PHP 8.5 local-container factory smoke passes with no HTML_TagCloud loaded.
- Before/after browser comparison of Module Catalogue preserves all 152 non-empty
  labels and destinations in identical order; one blank link is removed.
- Chrome desktop: wrapping styles load, keyboard Tab advances to the next tag
  with a visible outline, and clicking abstraction opens its tag-search route.
- At 390px viewport the cloud is 246px wide and has no horizontal overflow.
  The catalogue page itself overflows in its search controls; those controls were
  not changed by this migration. The viewport override was reset after testing.
- Browser smoke: file-manager Tag Cloud, Site Administration, group administration,
  discussion administration and question banks render authenticated with no PHP
  error markers. Console warning/error check was empty.
- Skin ownership, stylesheet cache-version and EcoTraining canvas contracts pass.
- Native event regression, LiveUser boot-removal, native web boundary and
  authenticated-form-lifetime checks remain green.
- Local PHP log comparison before/after 09:35 reports zero new warning types and
  zero tagcloud_class_inc.php error types. Existing unrelated warnings remain.
- Lint and git diff --check pass.

No production deployment, catalogue update action, external messages or fixture
records were created. FAQ/old blog/digitallibrary were not live browser-tested;
FAQ's caller was tested with fixtures. Utilities metadata remains a normal pending
catalogue update locally. Other callers are covered by the shared method contract,
not a claim that all optional historical modules are enabled and working.

Branch: release/consolidation-20260930; uncommitted changes coexist with the prior
event migration and unrelated user work. Roll back only the tagcloud class,
utilities version/description and appended tag-cloud skin rules; retain or revert
the matching tests/docs. Do not reset the checkout or revert the event migration.
