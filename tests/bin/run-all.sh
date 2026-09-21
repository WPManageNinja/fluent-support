#!/usr/bin/env bash
#
# Suite runner. Static tier runs on the host (no WordPress needed); every
# WordPress-dependent tier runs inside the Docker lab via WP-CLI.
#
#   tests/bin/run-all.sh              # everything
#   tests/bin/run-all.sh static      # static tier only
#   tests/bin/run-all.sh smoke       # REST smoke only (lab must be up)

set -uo pipefail

TESTS_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
REPO_ROOT="$(cd "${TESTS_DIR}/.." && pwd)"
ONLY="${1:-all}"

PASS=0
FAIL=0
declare -a FAILED_TIERS=()

banner() { printf '\n=== %s ===\n' "$1"; }

record() {
    # $1 = tier name, $2 = exit code
    if [ "$2" -eq 0 ]; then
        PASS=$((PASS + 1))
        printf -- '-- %s: PASS\n' "$1"
    else
        FAIL=$((FAIL + 1))
        FAILED_TIERS+=("$1")
        printf -- '-- %s: FAIL (exit %s)\n' "$1" "$2"
    fi
}

run_static() {
    banner "static: php -l (core + siblings)"
    local lint_fail=0 dir out
    for dir in "$REPO_ROOT/app" "$REPO_ROOT/boot" "$REPO_ROOT/database" \
               "$REPO_ROOT/../fluent-support-pro/app" "$REPO_ROOT/../fluent-support-pro/boot"; do
        if [ ! -d "$dir" ]; then
            printf 'SKIP — %s not present\n' "$dir"
            continue
        fi
        while IFS= read -r -d '' file; do
            out="$(php -l "$file" 2>&1)" || true
            if printf '%s' "$out" | command grep -qE '^(Parse|Fatal) error'; then
                printf '%s\n' "$out"
                lint_fail=1
            fi
        done < <(find "$dir" -name '*.php' -not -path '*/vendor/*' -print0)
    done
    record "php-lint" "$lint_fail"

    banner "static: raw-sql-prefix lint"
    php "$TESTS_DIR/lint/raw-sql-prefix.php"
    record "raw-sql-prefix" "$?"

    banner "static: route-coverage lint"
    php "$TESTS_DIR/lint/route-coverage.php"
    record "route-coverage" "$?"
}

run_lab_suite() {
    # $1 = tier name, $2 = container-path php file
    banner "lab: $1"
    if [ ! -f "$TESTS_DIR/${2#wp-content/plugins/fluent-support/tests/}" ]; then
        printf 'SKIP — %s not built yet\n' "$2"
        return 0
    fi
    bash "$TESTS_DIR/bin/lab" wp eval-file "$2"
    record "$1" "$?"
}

case "$ONLY" in
    all|static) run_static ;;&
    all|smoke)
        run_lab_suite "rest-smoke" "wp-content/plugins/fluent-support/tests/bin/run-smoke.php"
        ;;&
    all|permissions)
        run_lab_suite "permissions" "wp-content/plugins/fluent-support/tests/bin/run-permissions.php"
        ;;&
    all|integration)
        run_lab_suite "integration" "wp-content/plugins/fluent-support/tests/bin/run-integration.php"
        ;;&
    all|static|smoke|permissions|integration) ;;
    *)
        echo "Usage: run-all.sh [all|static|smoke|permissions|integration]" >&2
        exit 2
        ;;
esac

banner "summary"
printf 'Tiers passed: %d, failed: %d\n' "$PASS" "$FAIL"
if [ "$FAIL" -gt 0 ]; then
    printf 'Failed tiers: %s\n' "${FAILED_TIERS[*]}"
    exit 1
fi
