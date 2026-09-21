#!/usr/bin/env bash

set -euo pipefail

LAB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
COMPOSE_FILE="${LAB_DIR}/compose.yaml"
COMPOSE_PROJECT="fluent-support-test-lab"
REPO_ROOT="$(cd "${LAB_DIR}/../.." && pwd)"

load_lab_env() {
    local env_file="${LAB_DIR}/.env"
    local line

    [ -f "$env_file" ] || return 0

    while IFS= read -r line || [ -n "$line" ]; do
        line="${line%$'\r'}"
        case "$line" in
            ''|'#'*) continue ;;
        esac

        if [[ ! "$line" =~ ^FS_(LAB_[A-Z0-9_]+|STRICT_SQL)= ]]; then
            printf 'Refusing malformed lab env line: %s\n' "$line" >&2
            exit 1
        fi

        export "$line"
    done < "$env_file"
}

die() {
    printf 'ERROR: %s\n' "$*" >&2
    exit 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || die "Required command missing: $1"
}

require_docker() {
    require_command docker
    docker info >/dev/null 2>&1 || die "Docker daemon unavailable."
    docker compose version >/dev/null 2>&1 || die "Docker Compose v2 unavailable."
}

# Sandbox preflight: the lab is throwaway BY CONSTRUCTION. Refuse to proceed
# if configuration drifted toward anything that could be a real site.
guard_lab_config() {
    local db_name="${FS_LAB_DB_NAME:-fluent_support_test}"
    case "$db_name" in
        *test*) ;;
        *) die "Lab DB name '$db_name' must contain 'test'." ;;
    esac
}

compose() {
    docker compose \
        --project-name "$COMPOSE_PROJECT" \
        --project-directory "$LAB_DIR" \
        --file "$COMPOSE_FILE" \
        "$@"
}

wp_cli() {
    compose run --rm --no-deps wpcli wp "$@"
}

wordpress_port() {
    local binding

    binding="$(compose port wordpress 80 2>/dev/null | head -n 1)"
    [ -n "$binding" ] || die "WordPress port binding unavailable."
    printf '%s\n' "${binding##*:}"
}

wait_for_url() {
    local url="$1"
    local attempts="${2:-60}"
    local count=1

    require_command curl

    while [ "$count" -le "$attempts" ]; do
        if curl --fail --silent --show-error --output /dev/null "$url"; then
            return 0
        fi
        sleep 2
        count=$((count + 1))
    done

    die "Timed out waiting for $url"
}

load_lab_env
guard_lab_config
export COMPOSE_PROJECT_NAME="$COMPOSE_PROJECT"
