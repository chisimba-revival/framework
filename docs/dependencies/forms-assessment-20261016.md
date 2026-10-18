# Legacy Forms assessment — 16 October 2026

Derek is unsure Forms will be needed. This is the old Form maker module, not
Formbuilder. Derek approved preservation as forms__PAUSED rather than rebuilding now.
The source directory has been renamed; no database content was changed.

## Findings

- Raw HTML forms authored through the old FCKeditor; stored body and script.
- Management/results routes require login but lack explicit scoped permissions.
  Create/delete/publish lack POST/CSRF protection.
- Public savedata does not establish a published, valid form or an allowed field
  schema; it captures arbitrary request fields. Parent/subrecord writes are not
  checked as one transaction, and the parent insert omits form_id.
- Interpolated form IDs/title queries and raw rendering require repair.
- The embedding filter accepts [FORM]id[/FORM] and an unfinished [FORM:...] branch
  that var_dumps input. It renders stored body without publication checks and
  inserts unescaped request-derived message/query text. Historical documentation
  also mentions [FORMS], which differs from the implemented syntax.
- The export controller calls a private sendEmail method; this is not a functioning,
  supported report-delivery path. Do not make it public as a superficial fix.
- Layout instantiates CMS Admin classes. UI uses old tabs and Boxy; layout loads
  SimpleTree even though its tree is not rendered. A full revival is substantially
  more than replacing a dialog.

## Usage evidence

Fresh read-only scans use dbTable and emit registration, counts and matching
column metadata only. They include [FORM:], [FORM], [FORMS], module=forms and
packages/forms/ (the previous grid/dialog scan covered only [FORM:]). All six production sites and local Chisimba: Forms unregistered, none of the four
legacy Forms tables present, and no matching stored columns. Receipts are in
work/forms-trace-20261016 in the parent workspace. Counts cover selected text/relevant varchar columns, not all
possible encodings or external references. Preserve any data found.

## Safe options

Pause preserves source/history and avoids rebuilding an unused product. A pause
must also make the embedding path inert without breaking unrelated content and
must not affect the distinct Formbuilder module. Full revival requires a defined
field model, native editor, canonical validation/sanitisation, access policy,
abuse controls for public submissions, safe reporting, PDO rollback/stale-write
checks, repeatable installation and installed browser tests.

## Completed pause

All original Forms files preserved byte-for-byte under forms__PAUSED; added a
revival checklist. The shared parse4form filter now performs no service loading,
request inspection, rendering or debug output and returns authored text intact.
Regression tests cover legacy tags, unrelated text/HTML, Formbuilder markers and
malicious request parameters. PHP syntax and local homepage browser smoke pass,
with no browser warnings/errors. Formbuilder source is unchanged. No production
deployment. CMS remains a separate revival project; shared Boxy stays for CMS.
