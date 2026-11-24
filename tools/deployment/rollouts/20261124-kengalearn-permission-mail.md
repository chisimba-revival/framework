# KengaLearn contributor permission requests — 24 November 2026

Grassguide 0.014 extends the private NC directory with a contributor/count selector,
a shared-skin image dialog, private email addresses, personalised opening paragraphs,
editable standard requests, exact saved-message previews and explicit queue actions.
The existing communication service and configured verified sender deliver messages;
no new mail transport or worker was introduced. Saved contacts and correspondence
remain accessible after images are archived or replaced. CSV and the public app
feed exclude contact addresses and correspondence. No new schema is required.

Framework: `bd68b30084171eb5b42afd279b3eaf3a47e24bc6`.
Modules: `e446bfd43176d9d271fc091608a6823bb3cbd0e7`.
Manifest: `b98a674957c928534db05e9f2ccb4835c2bd1b3aad17eb911e920dcc427e318a`.
Operator: `/srv/kengalearn/grassguide-permission-mail-20261124/`.
Backup: `/srv/kengalearn/backups/shared-20261124-062755/`.

Requests capture the saved recipient, subject, body, sender and covered image
fingerprints. Revision checks reject changed previews. Contact revision, shared
outbox insert and immutable request audit commit atomically. Replayed request IDs
are idempotent; a pending request blocks another queue entry. Editing a draft
cannot change an existing outbox message. Provider acceptance is distinguished
from inbox delivery and image permission. Replies stay in the sender's mailbox;
permission evidence is maintained per image through the existing rights workflow.

The complete isolated PDO suite passed, including rollback after a real shared
outbox insertion, permissions, CSRF, exact-preview queueing, duplicate and pending
submission guards, privacy and contact-history retention. No mail transport was
invoked. Browser tests verified contributor selection, image popup, Escape/focus,
private draft save, disabled queueing for unsaved edits, exact queue confirmation,
request history, contextual Help and a narrow layout without horizontal overflow.
Synthetic outbox records, preview containers and their database were removed.
Screenshot: local `work/grassguide-permission-mail-20261124/permission-workflow.jpg`.

Previous source was verified on web and worker. Full Git bundle staging, PHP lint,
normal module upgrade/replay and clone HTTP checks passed; all 194 protected tables
were unchanged. Production upgrade and HTTP checks passed with the existing 179
species and 648 images preserved. The grass worker was recreated on the new source.
The existing communications timer is active and production recipient restrictions
are unset. No production contact messages were queued or sent during this work.
External inbox delivery was deliberately not tested.

Post-deployment audit passed: all 13,660 source files match on web and worker,
with no newer shared runtime changes against the deployed pins.
