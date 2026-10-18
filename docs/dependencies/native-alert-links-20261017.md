# Native alert links — 17 October 2026

HTML Elements 0.625 migrates alertbox::show() away from Facebox. Local fragment
links open native dialogs; remote/relative pages and images follow ordinary
navigation instead of being fetched and injected into the current document.
This is an intentional interaction change: remote pages no longer open inside
the alert overlay. Their existing server-side access rules continue to apply.
Executable URL schemes and literal controls/whitespace are rejected. Link text
remains caller-owned HTML, as in the previous helper contract.

The dialog moves and restores the original content (no copied IDs or discarded
input state), restores hidden/display state, supports Escape and Close, and
returns focus to its trigger. Fragments inside existing forms are not moved,
so controls are not detached from their owning form. Shared skin rules size the
preview within the viewport. Modified clicks retain normal browser behaviour.

The ALERTBOX and OGG filters no longer acquire jQuery through show(). Corrected
their declared property names for PHP 8 and removed the ALERTBOX filter's duplicate
render call. OGG still routes to the existing stream page; this is not a stream
module revival or security certification.

Facebox is NOT removed: alertbox::putJs() remains the compatibility entry point
for Context's old Poll popup, and Canvas loads Facebox directly for its chooser.
Those callers need their own migration. No legacy library files were deleted.

Checks passed: PHP regression for rejected schemes, remote-link behaviour and
local fragment rendering; syntax and whitespace checks; Chrome fixture without
jQuery using the shared skin, keyboard opening, Escape, Close, restored focus,
retained input after reopening and remote navigation. Installed local catalogue
update and translated preview label checked with warnings promoted to exceptions.
The form-ancestor safeguard was added after the browser run and syntax checked.
This is bounded helper verification, not a full-site no-jQuery certification.

Evidence: workspace work/native-alert-20261017/retained-draft.png. No production
deployment. Global jQuery loading remains enabled. Next: review old canvas
chooser and Poll before removing the remaining Facebox loader/assets.

Follow-up: [shared cleanup checkpoint](shared-native-cleanup-20261017.md) records
Facebox retirement, subsequent caller migrations and the remaining jQuery scope.
