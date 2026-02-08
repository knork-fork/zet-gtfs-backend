#!/usr/bin/env bash
set -euo pipefail

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

UID_GID="$(id -u):$(id -g)"

if ! ( \
    docker run --rm \
        -v "$(pwd)/frontend":/app \
        -w /app \
        node:20-alpine \
        sh -c "npm ci && npm run build" ); then
    echo "Build failed."
    exit 1
fi

sudo chown -R "$UID_GID" frontend/dist frontend/node_modules

FILE="frontend/dist/index.html"

# Insert meta description tag
META_TAG='<meta name="description" content="Pratite ZET tramvaje i autobuse uživo na karti Zagreba. Stanice, vozni red i dijeljenje linka na vozilo.">'
if ! grep -qi '<meta[^>]*name=["'\'']description["'\'']' "$FILE"; then
  sed -i \
    '/<meta charset=/a\
      '"$META_TAG" \
    "$FILE"
fi

# Change lang to hr
sed -i 's/<html lang="en">/<html lang="hr">/' "$FILE"

exit 0