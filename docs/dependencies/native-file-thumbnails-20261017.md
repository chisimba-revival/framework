# Native legacy thumbnails and Workgroup follow-up

File Manager 1.113 removes the jQuery thumbnail hover helper entirely. The modern
normal File Manager browser was already native; this remaining script was loaded
by the older picker/tag-result renderer, including list views where it did nothing.

Metadata now remains visible without hover. Full Unicode filenames are retained,
checkboxes have accessible names, decorative file images have empty alt text,
and the maintained skin reduces oversized icon padding and permits card wrapping.
Both legacy shared base CSS families receive the structural metadata change;
compact layout rules remain in the maintained root skin, not branding canvases.
The existing picker restriction controls remain native and unchanged.

During testing, the tagged-file path exposed an interpolated SQL query and an
unescaped tag heading. The query now binds both owner and tag through dbTable;
the heading is escaped. Thumbnail download links use the existing record-based
File Manager delivery action rather than constructing a static storage URL.
A wrong variable in private-file metadata and missing default arguments for the
tagged-file preview caller were corrected. A CSS identifier no longer goes through
language lookup. Tagged results include the shared contextual Help guide.

## Validation

- `php app/core_modules/filemanager/tests/tagged_files_query_test.php`
- `php app/core_modules/filemanager/tests/native_picker_controls_test.php | node app/core_modules/filemanager/tests/native_picker_controls_test.cjs`
- Changed PHP lint and `git diff --check`.
- Disposable PDO fixture: two synthetic file records, owner isolation, malicious
  tag input, and existing guarded-download policy. Synthetic bytes were delivered
  to the signed-in owner; anonymous and incorrect-filename requests did not receive
  them. No production data/files were changed.
- HTTP checks: thumbnail and list output, complete filenames, accessible labels,
  guarded download URLs, no jQuery/thumbnail-script assets, anonymous and other
  owner isolation, and injection rejection.
- Chrome with automatic jQuery loading suppressed: metadata, selection/clearing,
  keyboard focus, Help/full guide/Escape and focus return, and no console errors.
- A 360px same-origin iframe viewport confirmed readable wrapping cards; the
  surrounding legacy page still has horizontal overflow. No complete mobile-shell
  redesign or screen-reader audit is claimed. Tests used text-file icons, not a
  new image/video derivative-generation exercise.
- Fixture logs had no PHP warnings/deprecations during these checks. Initial
  navigation logged an existing missing word_topic language item on the home page;
  the thumbnail-specific missing CSS-ID language lookup was eliminated.

Local catalogue update uses the normal installed-module upgrade path. Production
has not been deployed. The global jQuery loader remains enabled outside the test
fixture.

## Workgroup scope decision

A fresh read-only audit found Workgroup registered locally and on KengaLearn,
KengaPub, KengaSolutions and Heritage, but all group, membership and shared-file
tables were empty. Chisimba.com and LearnTheBirds had no registration/tables.
Other references were registration/language metadata, a tag and local log history.

The file-list endpoint accepts a supplied group ID without enforcing membership;
the adjacent upload/edit/delete actions lack explicit authorisation and CSRF.
The main page also directly instantiates the absent old Forum module. This is
broader than a fetch replacement. The user has been asked whether to modernise
file sharing or preserve Workgroup as __PAUSED. No Workgroup files or registration
have been changed pending that choice. Do not treat this audit as a full revival
or as permission to disable the global jQuery loader.
