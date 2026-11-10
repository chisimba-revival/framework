# Navigation category ownership (toolbar 1.818)

Site category names and root positions live in `tbl_toolbar_categories`.
External and internal module links refer to a stable `group_key`. Their positions
only order children; ungrouped links use their position at the root. The fixed
Learning menu retains its own placement and authentication rules.

Create/rename/reorder a category separately from adding a link. A blank link
order appends within the selected category (or Learning menu). Category and link
positions accept 0–999. Explicit equal positions have deterministic tie-breaking.
Empty categories remain editable but do not render. Delete requires no remaining
site links, including links hidden by audience, permissions or module availability.

Install/update through Module Catalogue, including installation hooks. The new
category table is declared in register.conf. The post-install hook seeds each
legacy category once from the earliest link in the old global ordering. Immutable
sort anchors preserve ties with top-level links. Hidden links participate in this
migration so category order is consistent across audiences. Registration seeds
new module headings but never overwrites existing category names or positions.
No runtime DDL is performed. Apply the schema/catalogue update before reopening
normal navigation requests during a release.

The external-link `group_label` column is retained solely for rollback to a
pre-1.818 release; the renderer uses the category record. Remove this legacy
column once pre-1.818 rollback support is retired. Reverting code alone restores
old child-derived ordering and may display old labels after category renames;
a full rollback should restore the pre-upgrade database backup as well.

All administration uses the existing administrator, POST and CSRF boundaries.
Link additions/edits and category mutations share the database-scoped
`toolbar-navigation` lock. Persistence uses dbTable exact prepared APIs. Empty
strings must not be converted to NULL for ungrouped links.

Local integration check (fixtures roll back):

    CHISIMBA_NAV_TEST=local php core_modules/toolbar/tests/external_navigation_database_test.php /path/to/ch

Companion controller/behaviour tests cover authentication, permissions, CSRF
expiry/replay, drafts, destination validation, Learning placement and rendering.
