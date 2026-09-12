# Shared content classification

Chisimba 26 service foundation, 12 September 2026. Author: Derek Keats.

## Responsibility

`classificationservice` owns categories, tags and their associations. It does not own a blog editor, course, public content visibility, rendering or imported WordPress records. Categories form a tree; tags are flat. Labels and slugs are separate from stable term IDs. A name change preserves the slug unless an explicit replacement is supplied. Duplicate names (case-insensitive) and duplicate slugs within a vocabulary are rejected. Imports must resolve collisions explicitly, rather than silently merging unrelated categories.

Vocabulary identity is `(scope_type, scope_id, kind)`. Supported scope types are site (`site`), personal (user ID), and context (context code); kind is `category` or `tag`. A course catalogue provider may classify courses in the site vocabulary. Classifications of private content inside a course use its context vocabulary. Cross-scope associations are rejected; personal tags do not become site-wide tags accidentally.

Use language codes `mod_classification_category`, `mod_classification_categories`, `mod_classification_tag`, `mod_classification_tags` through `language->code2Txt`. The category strings use `[-category-]` and `[-categories-]`. Systext defaults remain category/categories for all system types; administrators may configure programme/programmes or other wording. Existing configured wording is preserved. Capitalise the resolved first word for headings/labels, as with other Chisimba systext terms. Never change internal kind values when display terminology changes.

## Installation

1. Deploy framework classification and systext changes together. Install classification normally through modulecatalogue. It has no public controller or end-user menu.
2. Run `php core_modules/classification/scripts/configure-service.php /path/to/chisimba/app` after registration. This establishes the required unique ID constraints and defines `chisimba/classification/manage`; it grants nobody additional rights. The legacy table installer does not honour unique-index declarations, so this explicit installation step is essential. The service refuses writes until those constraints and InnoDB storage are present.
3. Refresh language registration normally. Systext supplies missing defaults for existing sites, preserving custom mappings and refreshing old session caches. Fresh-install defaults are included.
4. Grant site classification management only to the intended administrative group. Admins manage all valid scopes; personal owners manage their own vocabulary; course admins manage their context vocabulary. Content-editing permission alone permits choosing categories and creating tags, not restructuring shared categories.

No request creates or alters a database table. Existing `tagging` tables and consumers remain untouched; do not route these new associations through its unfiltered legacy tag-cloud endpoints.

## Owning-module contract

Trusted composition registers a provider object, once per owning module:

```php
$classification = $this->getObject('classificationservice', 'classification');
$classification->registerProvider('simpleblog', $this->getObject('classificationprovider', 'simpleblog'));
```

The provider method `classificationAccess($itemId)` must return `null` for a missing record, or an array containing `scope_type`, `scope_id`, boolean `read`, and boolean `edit`. It must derive these from the persisted record and canonical access policy, not submitted scope fields. SimpleBlog's adapter is included. Unregistered providers fail closed. Discovery is deliberately explicit: a request must never name an arbitrary PHP provider class.

Module controllers remain responsible for POST/CSRF and their complete write transaction. Save a new record first, then classify it within the same transaction. The service uses savepoints inside an existing transaction and must not commit the caller's content save. On deletion, clear both kinds while the record still exists and the provider can authorise it, then delete the content in the same transaction. No callback is run during schema installation.

APIs:

- `saveTerm(type, scope, kind, name, slug='', parent='', id='')`: manager-only create/rename/reparent; cycles and cross-vocabulary parents rejected.
- `deleteTerm(type, scope, kind, id)`: manager-only; refuses terms still assigned or having children.
- `managementTerms(type, scope, kind)`: complete vocabulary for a manager.
- `choices(module, item, kind)`: available terms for an authorised item editor.
- `assign(module, item, kind, ids)`: atomically replace one classification kind with existing IDs; empty array clears it.
- `tag(module, item, names)`: atomically create/reuse named tags and replace the item's tag associations; input is an array, so commas inside imported labels are not ambiguous.
- `forItem(module, item, kind, editing=false)`: attached terms, after current read permission (or edit permission for the editor/preview).
- `browse(type, scope, kind, termId, page=1, size=20)`: exact-term content references only, filtered through each registered provider. It does not expose raw counts, private item IDs, titles or candidate cursors. Parent categories do not implicitly include descendants. Ordering is stable identity order; owning-module presentation may provide date/title ordering when it queries its own authorised content.

The service keeps data unescaped; render labels with the normal HTML escaping boundary. There is no public all-terms cloud: even a label may reveal private information. Public filters should be built from permitted content. A future combined search must register all participating providers and retain their visibility checks.

## Integration boundary for the next blog interface

This change intentionally does not extend the current raw editor. Its existing comma-separated `post_tags` remains authoritative until the interface and migration are implemented together. The SimpleBlog adapter is ready, but existing posts have not been reclassified and the old tag UI has not silently acquired a second source of truth. Next: migrate existing tags explicitly, wire save/delete/filter paths as a complete caller, add category management/selection and contextual Help, then test the redesigned publishing journey. Courses can adopt the same contract independently.

The WordPress importer remains separate. Preserve source term IDs/slugs and category hierarchy in an import mapping; preserve terms containing commas; report duplicate-name/slug conflicts for resolution. Do not import raw WordPress roles or bypass the destination module's permissions.

## Verification

- `php core_modules/classification/tests/service_test.php`: hierarchy/cycle protection, stable identity and slugs, case duplicates, atomic failure, cross-scope denial, manager/editor separation, draft privacy and immediate permission revocation.
- `php core_modules/classification/tests/database_test.php /path/to/chisimba/app`: installed local MariaDB storage, three-level category persistence/cycle rejection, nested transaction/savepoint rollback, tag creation/round-trip, exact-term browse, private visibility, clear associations, outer rollback, and language/systext resolution. Disposable data is rolled back.
- PHP syntax and whitespace checks for changed files.

No new user interface is introduced in this service-only change. UI, accessibility and contextual Help verification belong to the upcoming complete blog integration. No production deployment has been performed.
