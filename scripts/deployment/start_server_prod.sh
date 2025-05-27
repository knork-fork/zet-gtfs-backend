#!/usr/bin/env bash

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

# Check if frontend/dist_temp does not exist
if [ ! -d "frontend/dist" ]; then
    echo "Frontend not built, please run scripts/deployment/build_frontend.sh"
    exit 1
fi

echo "Starting server in production mode..."
if ! ( docker-compose -f docker-compose.prod.yml up -d --build ); then
    echo "Failed starting server."
    exit 1
fi

exit 0