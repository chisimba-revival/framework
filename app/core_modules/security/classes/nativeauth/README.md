# Native authentication scaffold

This directory contains **non-active** Milestone 8 contracts and a service skeleton.

It does not replace LiveUser, alter service registration, change login routing, or
modify the current session. Its purpose is to define a testable boundary around
a future Chisimba-owned authentication and authorisation implementation.

Activation is prohibited until:

1. current LiveUser behaviour has been captured by comparison tests;
2. repository adapters have been implemented against the confirmed schema;
3. password compatibility and migration have been independently reviewed;
4. session, logout, timeout, and privilege behaviour match the legacy path;
5. a default-off feature flag and immediate rollback path exist.
