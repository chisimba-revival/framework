# Native Context settings — 16 October 2026

Context 2.061 and ContextAdmin 1.431 share `context/resources/settings.js` for
conditional admission fields and delivery-format guidance. Both edit forms work
without jQuery. Switching away from separately paid access hides and disables
admission inputs; switching back retains the choice. Guidance uses textContent,
handles missing/malformed optional data, and initialises on either side of DOM readiness.
The creation wizard's separate code-availability checker still uses jQuery and
is the next complete caller to migrate. Normal core jQuery loading remains enabled.

The older Context `updatecontext` route now enforces current persisted administrator
or course-author permission, the active course scope, POST and a purpose-separated
CSRF token. Expiry returns the unsaved form with a fresh token, without replaying
the write. Draft title output is escaped; selections, about text, section/email
choices and the selected accessible image are retained. This does not change the
separate ContextAdmin author-management policy.

Smoke tests exposed an existing section-checkbox bug: boolean false meant “leave
unchanged” to dbcontext. Both settings controllers now pass integer 0/1, so sections
can be disabled again. Malformed key=value language registrations in Context were
converted to catalogue TEXT entries. A stale source test expected an obsolete
navigation-field read; it now checks the current validated submitted-navigation path.

PHP 8.5 warnings found on these journeys were corrected: declared Context activity
service, form dependencies, ContextAdmin dependencies, navigation dependency and
step-menu ID; removed obsolete getObject reference assignment; removed ineffective
optional defaults before a required private-method argument (all callers supply all
three arguments). Navigation is 1.051. No schema changes.

Validation: PHP lint/diff checks; native JS initial state, five policies, retained
choice, safe guidance and readiness tests; settings access policy tests (anonymous,
current/revoked admin and author, foreign/invalid scope, POST and CSRF); existing
settings/section/control-panel/admission/format-help contracts. Real PHP 8.5/PDO
HTTP checks on an isolated clone pass GET/CSRF/foreign-scope rejection, escaped
draft recovery, fresh-token save and format persistence. Original fixture settings
were restored through the save route, then exact nullable values through dbTable.
Chrome tested both edit forms with automatic jQuery/UI suppressed: admission
visibility/disabled state and format guidance pass; no jQuery scripts or browser
warning/error entries. After repairs, repeated requests added no PHP warnings.

Update Context, ContextAdmin and Navigation using the normal catalogue path.
Production has not been deployed. Disposable database, web container, relay and
credentials are removed after testing. Private HTTP fixture scripts remain under
`work/no-jquery-rehearsal-20261016` in the workspace.
