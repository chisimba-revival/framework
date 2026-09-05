#!/usr/bin/env bash
set -euo pipefail

APP_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"

if rg -n --glob '*.php' \
    "action.{0,40}logoff|logoff.{0,40}security" \
    "$APP_ROOT/core_modules/toolbar" \
    "$APP_ROOT/core_modules/skin" \
    "$APP_ROOT/core_modules/security"; then
    printf 'ERROR: A legacy GET/logoff producer exists in an active logout owner.\n' >&2
    exit 1
fi

rg -q "method=\"post\"" \
    "$APP_ROOT/core_modules/toolbar/classes/toolbarsecuritycontext_class_inc.php"
rg -q "native_auth_logout" \
    "$APP_ROOT/core_modules/toolbar/classes/toolbarsecuritycontext_class_inc.php"
rg -q "issueForSession('native_auth_logout')" \
    "$APP_ROOT/core_modules/toolbar/classes/toolbarsecuritycontext_class_inc.php"
rg -q "MAX_TOKENS_PER_CONTEXT" \
    "$APP_ROOT/core_modules/security/classes/nativeauth/csrftokenservice.php"

printf 'PASS: canonical logout producers and concurrent CSRF tokens verified.\n'
