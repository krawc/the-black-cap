#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────
# Usage:
#   bash theme/the-black-cap/setup/run.sh                  # first-time setup only
#   bash theme/the-black-cap/setup/run.sh /path/to/photos  # setup + upload room photos
#
# Re-running with a folder replaces existing media library entries
# in-place, keeping the same attachment IDs.
# ─────────────────────────────────────────────────────────────────
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
IMPORT_DIR="$REPO_ROOT/theme/the-black-cap/setup/import-images"
IMG_SRC="${1:-}"

# ── Stage images if a path was given ──────────────────────────────
if [[ -n "$IMG_SRC" ]]; then
  if [[ ! -d "$IMG_SRC" ]]; then
    echo "✖  Not a directory: $IMG_SRC" >&2
    exit 1
  fi

  IMG_SRC="$(cd "$IMG_SRC" && pwd)"
  echo "→ Staging images from:  $IMG_SRC"

  rm -rf "$IMPORT_DIR"
  mkdir -p "$IMPORT_DIR"

  # Copy only image files, sorted so slot numbers are deterministic
  n=0
  while IFS= read -r -d '' f; do
    cp "$f" "$IMPORT_DIR/"
    (( n++ )) || true
  done < <(
    find "$IMG_SRC" -maxdepth 1 -type f \
      \( -iname "*.jpg" -o -iname "*.jpeg" \
         -o -iname "*.png" -o -iname "*.webp" \
         -o -iname "*.gif" \) \
      -print0 | sort -z
  )

  if (( n == 0 )); then
    echo "✖  No image files found in $IMG_SRC" >&2
    exit 1
  fi

  echo "   Staged $n image(s) → will occupy attachment slots 1–$n"
fi

# ── Run setup inside wp-env ────────────────────────────────────────
cd "$REPO_ROOT"
echo "→ Running setup inside wp-env…"

# Recent wp-env versions use `docker compose exec` for `wp-env run cli`,
# which fails because the cli service is ephemeral (never persistently running).
# Work around it by calling `docker compose run` directly on the compose file
# wp-env generated for this project.
WP_ENV_COMPOSE=$(grep -rl "the-black-cap" "$HOME/.wp-env" \
  --include="docker-compose.yml" 2>/dev/null | head -1)

if [[ -n "$WP_ENV_COMPOSE" ]]; then
  docker compose -f "$WP_ENV_COMPOSE" run --rm cli wp eval-file \
    /var/www/html/wp-content/themes/the-black-cap/setup/setup.php
else
  # Fallback for older wp-env that supports run correctly
  wp-env run cli wp eval-file \
    /var/www/html/wp-content/themes/the-black-cap/setup/setup.php
fi

echo ""
echo "→ Done."
echo "   Site:   http://localhost:8888"
echo "   Admin:  http://localhost:8888/wp-admin  (admin / password)"
