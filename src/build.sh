#!/bin/sh
# Builds assets/js/scene/home.js from src/scene (Three.js bundled; GSAP comes from the theme's own scripts).
# Needs: npm i three@0.170 esbuild   (run from this folder or set NODE_PATH)
set -e
cd "$(dirname "$0")/.."
ESBUILD=${ESBUILD:-npx esbuild}
$ESBUILD src/scene/home.js --bundle --format=iife --minify --target=es2020 --legal-comments=none \
  --outfile=assets/js/scene/home.js --log-level=warning ${THREE_ALIAS}
echo "built assets/js/scene/home.js"
