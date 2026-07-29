#!/usr/bin/env bash
set -Eeuo pipefail
ROOT="/run/media/derek/main/chisimba-revival/framework"
FILE="$ROOT/app/core_modules/security/classes/auth_database_class_inc.php"
REPORT="/home/derek/Downloads/killme.txt"
: > "$REPORT"
exec > >(tee -a "$REPORT") 2>&1
grep -Fq "Temporary caller-migration scaffold." "$FILE" || {
    printf 'FAIL: tracked V8 scaffold marker not found\n' >&2
    exit 1
}
printf 'Removal is intentionally blocked until every userprincipal consumer has migrated.\n'
printf 'Checklist: %s\n' "$ROOT/docs/migrations/remove-v8-userprincipal-scaffold.md"
exit 2
