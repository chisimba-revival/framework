# Opt-in form draft recovery

`resources/formdrafts.js` provides `ChisimbaFormDrafts.attach(form, options)`.
It does not change authentication, permissions, CSRF or save semantics.

Consumers supply a stable `key` containing the installation path, authenticated
user ID, scope and edited resource/action; an explicit allowlist of text field
names; and translated `messages` (kept, unavailable, restored, conflict, restore,
discard). Never include passwords, credentials, form tokens, payment details or
file inputs. Do not opt all forms into storage indiscriminately.

The adapter persists changes in tab-local sessionStorage, restores a draft when
the server baseline matches, and asks before replacing a changed server baseline.
It warns on navigation with dirty work. An optional `onRestore` callback reveals
the restored editor. Storage failure keeps the form usable and tells the user to
keep the page open or copy their work. Closing the tab removes the recovery copy;
this is not durable server autosave or an encrypted vault on a shared computer.

Use `snapshot()` before a save and `saved(snapshot)` only after a confirmed
successful response. Newer edits remain dirty. After a confirmed create and form
reset call `reset()`. Never clear on submission, a redirect to login, a timeout,
an invalid response, or a permission/validation rejection. A changed account or
scope must not restore another account/scope's data.

A consumer must prevent full-page destructive error navigation, preserve CSRF,
check the submitting identity, and distinguish rejected writes from uncertain
writes. Never automatically replay uncertain mutations. Long-running or paid
operations need their own durable job/status and idempotency contracts.

Current consumer: Kanban 0.121. Other editors are not covered simply because this
asset exists. Rich editors and dynamic block builders need dedicated adapters
which synchronise the editor model; serialising a stale backing textarea is unsafe.

Regression: `tests/forms/draft_recovery_browser_test.cjs`, with Playwright available
on NODE_PATH. Tests use a synthetic origin, no application data or credentials.
