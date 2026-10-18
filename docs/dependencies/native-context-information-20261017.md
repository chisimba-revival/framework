# Context information migration — 17 October 2026

Status: source, installed HTTP/PDO and Chrome acceptance passed. Local catalogue updated; production unchanged.

## Changes awaiting acceptance

ContextAdmin 1.433 replaces the Step 2 immediate jQuery/GET image deletion with a native removal checkbox applied by the information form. The old endpoint returns 410 and performs no mutation. Step 2 reads and saves require current persisted administrator/author authority; saves also require POST, matching session course and a purpose-separated CSRF token. Expiry renders the retained description/removal selection and a new token without writing. Accessible selected images are retained. A replacement takes precedence over removal, and failed replacement does not delete the current image first. Description writes use the shared rich-text sanitiser and checked persistence. Shared contextual Help explains saving, public featured images and recovery.

Context 2.062 validates image context identifiers, applies File Manager's read policy before copying selected images, and uses the existing contained-path resolver with secure-storage-first lookup. Uses the inherited public config property; installed acceptance caught and corrected an incompatible declaration before release.

Image and description storage are separate filesystem/database operations, not an atomic transaction. The failure notice explains potential partial completion; this change does not claim concurrent-edit or cross-storage transaction protection. The later wizard steps are outside this change's security acceptance.

## Checks passed

- PHP syntax on controller, template and image service.
- Actual controller probes: method, current authority, scope, malformed/expired token denial; no image mutation on rejected requests; draft/fresh-token recovery; replacement precedence, failure handling, sanitiser invocation and inert retired endpoint.
- Actual filesystem fixtures: authorised secure-storage image copy, byte equality, denied-file preservation, traversal rejection, valid removal and invalid context identifiers.
- Existing settings authority, image-format, course-admin entrypoint, discoverability, tiered-admission flow and rich-text sanitiser tests.

## Installed acceptance

Started the existing local containers and used an outbound-isolated disposable PDO clone with jQuery suppressed. Updated Context and ContextAdmin through the catalogue. HTTP checks confirmed inert legacy GET removal, no writes on GET save or expired token, description/removal-selection recovery with a fresh token, successful removal/description persistence, and unchanged content after ordinary-member denial. Chrome confirmed Help/Escape, deferred removal, successful advance to Step 3, no jQuery assets and no console warnings/errors. Final repeated installed checks produced an empty PHP diagnostic log after correcting the inherited-property issue. Disposable fixtures removed after acceptance.

## Priority follow-up

The later outcomes and plugin-selection save handlers still rely on the wizard session comparison and need current-authority, POST/CSRF, draft recovery and checked/scoped persistence review. This report does not certify those handlers. Treat that security work as the next priority before cosmetic helper migration.

## Remaining jQuery families from current source tracing

Global loading cannot yet be disabled. Remaining core matches include timeoutmessage and alertbox (shared notification helpers), File Manager's legacy file selection/folder preview/quota pagination, navigation pagination, tagging's Ajax helper, older canvas/dynamiccanvas paths and Stories. Some references may be inactive or comments; runtime and caller tracing is still required. CMS/CMS Admin retain their explicitly deferred legacy dependencies. This is a candidate inventory, not proof of installed use or permission to retire modules.
