# Context creation canonical-service validation

Validated on 2026-08-01 against the preserved fresh-install database.

## Preserved outcomes

- `lollypop101` is the pre-repair control: creation completed, but its Lecturers membership stored malformed `perm_user_id = 0`.
- `lollypop102` is the guarded failure control: the Context row rolled back, while group IDs 9–12 and hierarchy rows exposed the former LiveUser transaction escape.
- `lollypop103` is the post-repair proof: all four creation steps completed with one Context row, four canonical groups (IDs 13–16), correct hierarchy, creator membership (`perm_user_id = 2`, `group_id = 14`), five expected contextual grants, no duplicate membership or malformed/duplicate permission identities, and post-commit search indexing.

## Proven contract

Context creation obtains the creator's logical `userid`, resolves the canonical permission identity, and writes hierarchy and membership through canonical `dbTable`-backed services on the transaction-owning database path. The active Context-creation path no longer uses LiveUser hierarchy or membership writes.

The historical `lollypop101` and `lollypop102` rows were intentionally preserved as evidence and were not repaired or deleted.
