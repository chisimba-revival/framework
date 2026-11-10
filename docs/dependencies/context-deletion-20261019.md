# Course deletion repair — 2026-10-19

The old flow deleted the course before deleting its groups, ignored the failed
parent-group deletion result, and never called messaging/announcement cleanup.
It could report success while retaining records, or throw after losing the course.

`contextdeletion` now checks current owner/site-administrator authority and the
storage engines, locks the course row, calls the owning module cleanup methods,
removes canonical course groups through GroupService, then deletes the course
last. Checked dbTable transactions/savepoints preserve the enclosing transaction.
Non-InnoDB tables and unknown/shared group relationships fail before completion;
the entire database operation rolls back. No opportunistic schema conversion.

Announcement cleanup removes the course association and deletes a context-only
announcement/receipt only if no other course uses it. Site announcements and
other courses remain intact. Conversation cleanup includes posts, replies, bans,
read and delivery positions. Channel creation and chat announcement publishing
lock/check the parent course to avoid recreating content after deletion.

Normal web deletion and explicit orphan repair require POST plus a scope/mode-bound
CSRF token. Repair is site-admin-only and refuses existing courses. It uses the
same confirmation form with `action=delete&contextcode=CODE&repair=1`; it is not
an automatic sweep. No authentication/session bypass or blanket orphan deletion.

Search documents are removed after a successful outermost commit, best-effort.
As with existing course creation, transaction-owning callers must perform their
index maintenance after committing. Failed index cleanup is logged; database
success is not misreported as rollback. Search storage is not transactional.

This bounded fix covers the existing core deletion responsibilities plus the
current conversations and announcements modules. It is not a claim that every
legacy module's course-owned data or uploaded files have been audited for erasure.
Do not use course deletion as a comprehensive personal-data erasure mechanism.

Validation:
- PHP lint and git whitespace checks.
- Context orchestration and group-family behavioural tests: denial, missing scope,
  shared relationships, failure rollback, successful cleanup, and protected groups.
- Installed local PDO test (`CHISIMBA_CONTEXT_CLEANUP_TEST=1 php
  packages/messaging/tests/context_cleanup_pdo.php`, from runtime root): nested and
  outer rollback, all conversation tables, shared announcement preservation,
  course-only receipt removal, anonymous repair denial, absent-course creation.
- Existing announcements contract, messaging policy and eight chat client tests.
- Local Module Catalogue registration; normal signed-in browser confirmations for
  the three exact disposable scopes; Help opens and Escape restores focus.
- Read-only verification: all three courses, group families, channels and
  announcement associations gone. No changes to Chisimba.com.

Versions: context 2.066, contextadmin 1.435, contextgroups 1.087, groupadmin 3.182,
messaging 2.007, announcements 0.416. Deploy framework and module changes together.
The local runtime has existing displaycontext dynamic-property deprecations;
these were present before this fix. The initial CSRF-purpose formatting error
found during browser validation was corrected before successful cleanup.
