#!/usr/bin/env bash

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
source "${SCRIPT_DIR}/lib.sh"

require_docker

# The pro sibling is a required family member: compose bind-mounts it, and a
# missing path would surface as a confusing Docker error later.
if [ ! -f "${REPO_ROOT}/../fluent-support-pro/fluent-support-pro.php" ]; then
    die "fluent-support-pro checkout not found next to fluent-support. The lab mounts both."
fi

printf 'Validating Compose config...\n'
compose config --quiet

printf 'Starting disposable lab...\n'
compose up --detach db wordpress

LAB_PORT="$(wordpress_port)"
LAB_URL="http://127.0.0.1:${LAB_PORT}"
wait_for_url "$LAB_URL"

if ! wp_cli core is-installed >/dev/null 2>&1; then
    printf 'Installing WordPress...\n'
    wp_cli core install \
        --url="$LAB_URL" \
        --title="Fluent Support Test Lab" \
        --admin_user="${FS_LAB_ADMIN_USER:-fsadmin}" \
        --admin_password="${FS_LAB_ADMIN_PASSWORD:-fluent-support-lab-only}" \
        --admin_email="${FS_LAB_ADMIN_EMAIL:-fsadmin@example.test}" \
        --skip-email
fi

wp_cli option update home "$LAB_URL" >/dev/null
wp_cli option update siteurl "$LAB_URL" >/dev/null
wp_cli option update blog_public 0 >/dev/null
wp_cli rewrite structure '/%postname%/' --hard >/dev/null

# Hostile-environment axes on by default (Methodology rule 8):
#  - strict SQL modes are set at the MariaDB server level in compose.yaml
#  - non-UTC timezone: at gmt_offset=0 site-local and UTC are byte-identical,
#    silently voiding every assertion that distinguishes them.
wp_cli option update gmt_offset 6 >/dev/null

printf 'Activating Fluent Support (free, then pro)...\n'
wp_cli plugin activate fluent-support >/dev/null
wp_cli plugin activate fluent-support-pro >/dev/null

printf 'Seeding lab fixtures...\n'
wp_cli eval-file wp-content/plugins/fluent-support/tests/lab/seed.php

printf '\nLab ready: %s\n' "$LAB_URL"
printf 'Admin: %s/wp-admin/ (%s)\n' "$LAB_URL" "${FS_LAB_ADMIN_USER:-fsadmin}"
