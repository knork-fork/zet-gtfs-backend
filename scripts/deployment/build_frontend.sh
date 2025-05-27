#!/usr/bin/env bash

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

if ! ( \
    # Note: using `npx vite build` instead of npm run build to skip vue-tsc, for now
    docker run --rm \
        -v "$(pwd)/frontend":/app \
        -w /app \
        node:20-alpine \
        sh -c "npm install && npx vite build" ); then
    echo "Build failed."
    exit 1
fi

exit 0