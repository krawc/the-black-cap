#!/usr/bin/env bash
# Run the one-shot WP setup from the repo root.
# Usage: bash theme/the-black-cap/setup/run.sh
set -e

REPO_ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
cd "$REPO_ROOT"

echo "→ Running setup script inside wp-env container…"
wp-env run cli wp eval-file /var/www/html/wp-content/themes/the-black-cap/setup/setup.php

echo ""
echo "→ Done. Open http://localhost:8888 to see the site."
echo "   WP Admin: http://localhost:8888/wp-admin  (admin / password)"
