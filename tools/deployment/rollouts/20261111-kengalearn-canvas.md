# KengaLearn canvas and course cards — 11 November 2026

User-authorised target: KengaLearn only, following local review of the canvas
and My Courses cards. Other production sites intentionally remain on the
previous common bundle until a wider rollout is authorised. This is an explicit
staged rollout of shared development source, not site-specific source.

Candidate common source:
- framework `b13495351f68ff77d4f961e8a0ae5aede5340465`
- modules `08a17062cbfe180d9162ec94057f931a8f5f2953`
- manifest `67d5dbcce1cfc88793aa56b0bb933fbb67fa64dd92490df462a24e6008157807`

The complete Git-object bundle contains 13,548 shared files. ContextAdmin moves
to 1.436; no schema, account, course content, payment or URL migration is planned.
Production settings and content remain mounted separately. The local filtered
preview database and synthetic accounts are not part of the release.

Operator/evidence directory: `/srv/kengalearn/canvas-release-20261111`.
Initial rehearsal upgraded successfully but rejected a stale installed-module
baseline (the prior fleet rollout had installed ShortURL). The read-only live
catalogue confirmed ShortURL was the sole difference; rehearsal was repeated
with the current baseline before any production switch.
