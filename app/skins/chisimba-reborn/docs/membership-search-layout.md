# Membership search layout — 9 September 2026

Search cards now place account identity, wrapping role buttons and the site-access explanation on separate rows. The previous non-wrapping flex row reserved a full width for the explanation beside the other content, crushing the username and overflowing the card. The shared skin owns this correction; course permissions and membership actions are unchanged.

Long button labels can wrap. Phone search controls reset their flex basis when stacked, preventing a 20rem-high search field. Existing stylesheet modification-time cache invalidation covers deployment of this skin-only change; no module runtime or registry changes are needed.

Local checks: inspected the actual member search page with an existing synthetic account at desktop width (896px card, scroll width equals client width), an 1100px viewport and a 390px phone iframe. Verified readable identity, wrapped actions and contained explanation; fixed the tall phone search field found during the check. Lecturer-assignment and stylesheet cache contracts passed. No membership was changed; no production deployment.
