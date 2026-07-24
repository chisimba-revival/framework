# Identity, Groups and Permissions Architecture

## Status

Accepted architectural direction for the Chisimba Revival.

## Purpose

Chisimba historically accumulated overlapping models, direct table access,
LiveUser calls, UI-specific JSON helpers and multiple mutation paths. The
modernised system must provide one authoritative implementation for each
domain operation while preserving proven authorization behaviour during the
migration.

## Domain boundaries

### Identity

The identity domain owns user records, lifecycle and account state.

Canonical service boundary:

- `security::userservice`

Legacy implementations may remain behind this service during migration, but
controllers, APIs and new interfaces must not create competing user-write
paths.

### Groups and membership

The groups domain owns groups, hierarchy and direct membership.

Canonical service boundary:

- `groupadmin::groupservice`

Current storage is delegated to `groupadminmodel`. The service itself must not
contain UI-specific JSON contracts or ExtJS assumptions.

### Permissions and authorization

The permissions domain owns ACL assignment and authorization decisions.

Canonical service boundary:

- `permissions::permissionservice`

Existing `permissions_model`, `permissions_acl`, `perms` and the Milestone 11
administrator compatibility bridge must converge behind this boundary before
their implementations are replaced.

## Non-negotiable rules

1. Every mutation passes through exactly one canonical domain service method.
2. Multiple interfaces may exist, but they must call the same underlying
   service.
3. Controllers and templates do not write identity, group, membership or ACL
   tables directly.
4. Transport adapters do not own domain logic.
5. New mutations are POST-only, CSRF-protected, permission-checked, validated
   and transactional where more than one record changes.
6. Read and write contracts are neutral PHP records, not ExtJS payloads.
7. Legacy callers are migrated incrementally through compatibility adapters.
8. LiveUser replacement occurs behind the service boundaries, not through a
   second parallel implementation.
9. New interfaces are semantic, accessible and localized.
10. Deprecated paths are removed once no caller depends on them.

## Current dependency direction

```text
native or legacy interface
          |
          v
controller / transport adapter
          |
          v
canonical domain service
          |
          v
legacy model or replacement repository
```

Dependencies must not point upward. Storage implementations must not depend on
controllers, templates or transport formats.

## Migration sequence

1. Establish read-only service boundaries.
2. Move native read interfaces onto them.
3. Add tests and verify response contracts.
4. Add one guarded mutation at a time.
5. Route legacy mutations through the same service.
6. Remove duplicate direct-write paths.
7. Replace LiveUser internals behind the stable services.
