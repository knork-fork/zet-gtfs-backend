#!/usr/bin/env bash

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

if [ ! -d "frontend" ]; then
  echo "The frontend directory does not exist. Please clone the frontend repository first."
  exit 1
fi

# Pull the latest changes for the frontend repository
cd frontend
git pull origin master

echo "Frontend repository updated to the latest version, commit submodule ref."