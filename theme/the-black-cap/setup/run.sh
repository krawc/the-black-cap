#!/usr/bin/env bash
# ─────────────────────────────────────────────────────────────────
# Usage:
#   bash theme/the-black-cap/setup/run.sh                         # setup, no room photos
#   bash theme/the-black-cap/setup/run.sh /path/to/photos         # setup + upload room photos
#   bash theme/the-black-cap/setup/run.sh --skip-rooms            # skip room photo import
#   bash theme/the-black-cap/setup/run.sh /path/to/photos --skip-rooms  # stage but don't import
# ─────────────────────────────────────────────────────────────────
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/../../.." && pwd)"
IMPORT_DIR="$REPO_ROOT/theme/the-black-cap/setup/import-images"
IMG_SRC=""
SKIP_ROOMS=0

# ── Parse args ────────────────────────────────────────────────────
for arg in "$@"; do
  case "$arg" in
    --skip-rooms) SKIP_ROOMS=1 ;;
    -*) echo "✖  Unknown flag: $arg" >&2; exit 1 ;;
    *)  IMG_SRC="$arg" ;;
  esac
done

# ── Stage images if a path was given and not skipping ─────────────
if [[ -n "$IMG_SRC" ]] && [[ "$SKIP_ROOMS" -eq 0 ]]; then
  if [[ ! -d "$IMG_SRC" ]]; then
    echo "✖  Not a directory: $IMG_SRC" >&2
    exit 1
  fi

  IMG_SRC="$(cd "$IMG_SRC" && pwd)"
  echo "→ Staging images from:  $IMG_SRC"

  rm -rf "$IMPORT_DIR"
  mkdir -p "$IMPORT_DIR"

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

if [[ "$SKIP_ROOMS" -eq 1 ]]; then
  echo "   (room image import skipped)"
fi

WP_ENV_COMPOSE=$(grep -rl "the-black-cap" "$HOME/.wp-env" \
  --include="docker-compose.yml" 2>/dev/null | head -1)

if [[ -n "$WP_ENV_COMPOSE" ]]; then
  docker compose -f "$WP_ENV_COMPOSE" run --rm \
    ${SKIP_ROOMS:+-e TBC_SKIP_ROOMS=1} \
    cli wp eval-file \
    /var/www/html/wp-content/themes/the-black-cap/setup/setup.php
else
  TBC_SKIP_ROOMS=$SKIP_ROOMS wp-env run cli wp eval-file \
    /var/www/html/wp-content/themes/the-black-cap/setup/setup.php
fi

echo ""
echo "→ Done."
echo "   Site:   http://localhost:8888"
echo "   Admin:  http://localhost:8888/wp-admin  (admin / password)"
