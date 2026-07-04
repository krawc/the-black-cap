#!/usr/bin/env bash
# Run the TBC setup script directly on the remote server.
#
# Usage (from anywhere, as long as the theme is installed):
#   bash /path/to/wp-content/themes/the-black-cap/setup/run-remote.sh
#
# Requirements: WP-CLI available as `wp` (or `php wp-cli.phar` — edit WP_CLI below).
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

# Derive WP root: setup/ → the-black-cap/ → themes/ → wp-content/ → WP root
WP_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"

if [[ ! -f "$WP_ROOT/wp-config.php" ]]; then
  echo "✖  wp-config.php not found in $WP_ROOT"
  echo "   Make sure the theme is installed at wp-content/themes/the-black-cap/"
  exit 1
fi

# Change this if wp-cli isn't in PATH (e.g. WP_CLI="php ~/wp-cli.phar")
WP_CLI="wp"

echo "→ WordPress root: $WP_ROOT"
echo ""

$WP_CLI eval-file "$SCRIPT_DIR/setup.php" --path="$WP_ROOT"
