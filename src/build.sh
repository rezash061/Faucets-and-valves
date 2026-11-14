#!/bin/sh
# Builds assets/js/scene/home.js (front page) and product.js (product viewer) from src/scene (Three.js bundled; GSAP comes from the theme's own scripts).
# Needs: npm i three@0.170 esbuild   (run from this folder or set NODE_PATH)
set -e
cd "$(dirname "$0")/.."
ESBUILD=${ESBUILD:-npx esbuild}
for entry in home product; do
  $ESBUILD src/scene/$entry.js --bundle --format=iife --minify --target=es2020 --legal-comments=none \
    --outfile=assets/js/scene/$entry.js --log-level=warning ${THREE_ALIAS}
  echo "built assets/js/scene/$entry.js"
done
