#!/usr/bin/env bash
#
# Destroy the lab including all data volumes. Always safe: the lab is
# throwaway by construction and setup.sh rebuilds it from nothing.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
# shellcheck source=lib.sh
source "${SCRIPT_DIR}/lib.sh"

require_docker

compose down --volumes --remove-orphans
printf 'Lab destroyed. Rebuild with: tests/bin/lab setup\n'
