# Working on Chisimba 26 — framework

This is the repository-wide guide for coding assistants and contributors. Read it
before changing code. Use `AGENTS.md` with this exact capitalisation. More specific
instructions in a subdirectory apply to that subtree. Explicit instructions from
the person commissioning the work take precedence over this guide.

## Purpose and evidence

Chisimba is a modular PHP framework, not only an LMS. The revival retains useful
architecture while modernising implementation, accessibility and security. Build
maintainable capabilities through shared Chisimba services, not temporary feature
layers that will need to be dismantled later.

The root README contains historical tutorials, including PHP 5-era requirements
and obsolete practices. Do not treat those examples as the current runtime or
security contract. Inspect current code, tests and the actual installation.
Dated audit reports describe the state at that date; they are not proof of what
is installed or deployed today.

The broader workspace may contain `../chisimba-info/chisimba-constitution.md`.
Read it when available: enduring principles take precedence over old milestone
notes. This guide includes the central rules so a standalone clone is usable.
Do not require another contributor to reproduce Derek's filesystem paths.

## Repository and runtime map

- `app/classes/core/`: engine, base objects, controller and framework infrastructure.
- `app/core_modules/`: shared framework modules and services.
- `app/skins/chisimba-reborn/`: maintained modern skin and shared UI primitives.
- `app/skins/chisimba-reborn/canvases/`: branding and permitted canvas variations.
- `tests/` and individual core modules' `tests/`: focused regression checks.
- `tools/`: operational tools; read their prerequisites before running them.
- The separate `chisimba-revival/modules` repository contains application modules;
  installations commonly expose it as `packages/`.
- An optional sibling `dev-environment` repository assembles local runtimes.
  Source, assembled runtime and container-mounted overlays can differ.

Before editing, inspect the branch, upstream, working-tree changes, relevant
module registration, PHP version and runtime mounts. Trace the actual caller.
Do not assume this checkout is `main`, the active runtime source, or the same
version as another computer. Preserve local changes and existing databases.
Work in source repositories; reflect any diagnostic runtime repair back into
source and assembly configuration. Do not blindly reset, rebuild or reinstall.

## Architecture and code conventions

- Modules use Chisimba abstractions through `getObject`, `newObject` and existing
  composition boundaries. Find a shared service before adding another implementation.
- Keep controllers responsible for request handling, services for domain rules,
  repositories for persistence, and templates for presentation.
- Follow the framework's `init()` lifecycle, class-loading conventions and entry
  point guard. Classes commonly live in `classes/<name>_class_inc.php` and extend
  `ChisimbaObject`, `controller` or `dbTable` as appropriate.
- Keep changes readable, bounded and documented. Preserve existing licences and
  attribution; document contracts and non-obvious decisions, not every assignment.
- Prefer native HTML/CSS/JavaScript and progressive enhancement. Do not introduce
  direct ExtJS dependencies or recreate ExtJS behind a new name. Migrate complete
  callers, including their obsolete assets and tests. Compatibility bridges need
  a stated removal path.
- Fix warnings and deprecations at their cause. Do not hide them with global error
  suppression. Preserve encoding and data contracts; a warning fix is not permission
  for an unrelated data-format migration. Identify vendor changes explicitly.

## Skin primitives are a project rule

**The skin renders the canvas. Modules supply semantic content and behaviour.**
Read [the skin overview](app/skins/chisimba-reborn/README.md) and
[the canvas contract](app/skins/chisimba-reborn/CANVAS_CONTRACT.md) before UI work.

- Reuse existing skin layout, form, card, button, notice and spacing primitives.
  Examples include `chisimba-form-card`, `chisimba-form-card--wide`,
  `chisimba-form-field` and `chisimba-form-actions`; inspect their current contract.
- Shared presentation fixes belong in the root skin. Canvases provide identity,
  tokens, logos and explicitly permitted layout variations. Do not copy skin
  components into a canvas or fork a branded skin to fix one page.
- If a needed primitive is missing, add the smallest reusable semantic primitive
  to the shared design system. Avoid module-specific copies, inline magic spacing,
  arbitrary brand colours and CSS specificity patches that mask the underlying issue.
- Use `getObject('iconservice', 'ui')` for icons. Actions use consistent icon buttons;
  icon-only controls need accessible names. Do not mix text deletion links with
  neighbouring action buttons, or introduce external icon/CDN dependencies.
- Keep adjacent controls the same height, use the shared action gap, and align
  search, Help and actions coherently. Use horizontal space without breaking
  narrow layouts. Reuse the established wide-main/narrow-sidebar layout when suitable.
- Respect semantic headings, labels, keyboard use, visible shared focus states,
  contrast, status messages and reduced motion. Dialogs and Help must support
  Escape and sensible focus return. Drag-and-drop needs a keyboard alternative.
- Preserve drafts, selection and scroll position where practical. Use Ajax for
  incremental actions when it improves the workflow; still enforce server-side
  permissions, CSRF, errors and concurrency. Prevent repeated long-running submissions
  and show a real busy state, not an idle spinner that resembles activity.

## Language and contextual Help

Register user-facing strings in `register.conf` and resolve them through the
language service. Use British English source text. Resolve configurable terms
with the systext-aware `code2Txt` path, including `[-author-]`, `[-authors-]`,
`[-readonly-]`, `[-readonlys-]`, `[-context-]`, `[-contexts-]`, and organisation
terms. Other domains also have abstractions, such as blog/post and category labels;
inspect the existing mappings. Display labels must not become permission identifiers.
Capitalise substituted terms when they begin a heading or sentence.

New or materially changed user workflows include contextual Help through the
shared `help` module: steps, access, save/publish behaviour and recovery. Verify
both the Help entry point and the full guide, including Escape. Do not add an
unrelated help system or large space-consuming help panel to each module.

## Security, services and data

- Reuse canonical `security` services (identity, users, credentials, authentication,
  permissions and native web composition) and `groupadmin` services. Trace their
  active callers; old LiveUser-era examples and scaffold notes may be outdated.
  Do not write authentication, user or group tables around these services.
- Login is not authorisation. Check current persisted ownership, role and scope
  for every read/write, API, download, preview, count, feed and reusable block.
  Fail closed for unknown resources; never trust submitted scope/owner identifiers.
- Mutations require the established method and CSRF contract. Token expiry must
  not destroy typed work. Do not solve expiry by disabling CSRF or replaying
  uncertain writes. Preserve drafts and return actionable errors.
- Public published content and private course/assessment material have different
  policies. Assignment submissions remain limited to the submitting student,
  authorised instructors and administrators. Public course status does not make
  every uploaded assessment file public. Test actual file bytes and derivatives.
- File access is coordinated application, filesystem and server work. When present
  on your branch, read `tools/file-storage/README.md`; its deployment notes are
  historical. Verify current routing, aliases and permissions before migration.
  Symlinks alone are not access control. Never republish private media to fix a URL.
- Escape at output boundaries and use the shared rich-text sanitiser where needed.
  Use canonical database connections, safe quoting/binding and checked errors.
  Use transactions and revision checks for work that must not be partially saved.
- Reuse `classification` for scoped tags/categories, `ui` for components/icons,
  `htmlelements` for editor boundaries, and `ai` for provider integration. Some
  services live in the separate modules repository; inspect dependencies first.
- Keep secrets, personal data, private prompts, database dumps and credentials
  out of source control and diagnostic output. AI, email and payment tests use
  fixtures/test modes unless the user has authorised the specific external action.

## Installation and validation

Use Module Catalogue's normal registration/update path, including installation
hooks, language items and declared dependencies. Increment relevant module versions
when required. Schema upgrades must be guarded, repeatable and preserve existing
data. Do not create/alter tables opportunistically during ordinary page requests.
Some services require explicit post-install scripts: read their current guidance.

There is no assumed universal test runner. Inspect the relevant test harness and
its prerequisites; some scripts need an installed database, environment opt-ins,
fixtures or sibling repositories. Start with `php -l` on changed PHP and
`git diff --check`, then focused behavioural/security tests and browser checks
for the changed workflow. Example entry points, where present on your branch:

- `php app/core_modules/modulecatalogue/tests/update_dependencies_test.php`
- `php tests/fileaccess/policy-test.php`
- `php app/core_modules/classification/tests/service_test.php`

Check runtime logs for new warnings/deprecations. For shared UI changes inspect
representative consumers, desktop/narrow screens and keyboard interactions. For
permissions exercise anonymous, outsider, member, instructor, administrator and
revoked-access cases as applicable. Use disposable fixtures and clean them up;
never test destructive behaviour against a person's active work.

Before handing off, report changed behaviour, tests actually run, remaining
limitations, coordinated repository/schema changes and the branch/commit. Keep
commits scoped. A local update is not authorisation to deploy to production;
use the user's authorised target and scope. Do not merge unrelated development
work just to publish a small fix or documentation change.
