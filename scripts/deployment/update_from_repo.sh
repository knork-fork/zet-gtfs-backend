#!/usr/bin/env bash

# NOTE: this script will update frontend to currently referenced commit in the backend repo submodule,
# but this may or may not match to latest commit in the frontend repo.

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

get_env_value() {
    local key="$1"
    local value=""

    # Load from .env
    if [[ -f .env ]]; then
        value=$(grep -E "^${key}=" .env | tail -n1 | cut -d'=' -f2-)
    fi

    # Override from .env.local
    if [[ -f .env.local ]]; then
        local local_value
        local_value=$(grep -E "^${key}=" .env.local | tail -n1 | cut -d'=' -f2-)
        if [[ -n "$local_value" ]]; then
            value="$local_value"
        fi
    fi

    echo "$value"
}

# Get local and remote commit hashes for frontend and backend
checkedOutFrontendCommit=$(git -C ./frontend rev-parse HEAD | tr -d '\n')
checkedOutBackendCommit=$(git -C ./ rev-parse HEAD | tr -d '\n')

frontendRepoUrl=$(get_env_value FRONTEND_REPO)
backendRepoUrl=$(get_env_value BACKEND_REPO)
remoteFrontendCommit=$(git ls-remote $frontendRepoUrl HEAD | awk '{ print $1 }' | tr -d '\n')
remoteBackendCommit=$(git ls-remote $backendRepoUrl HEAD | awk '{ print $1 }' | tr -d '\n')

shouldUpdate=false
if [[ "$checkedOutFrontendCommit" != "$remoteFrontendCommit" ]]; then
    shouldUpdate=true
fi
if [[ "$checkedOutBackendCommit" != "$remoteBackendCommit" ]]; then
    shouldUpdate=true
fi

if [[ "$shouldUpdate" == "false" ]]; then
    echo "No updates available."
    exit 0
fi
echo "Updates available. Proceeding with update..."

# Pull changes and rebuild containers
if ! ( docker-compose down \
    && git pull \
    && git submodule update \
    && scripts/deployment/build_frontend.sh \
    && scripts/deployment/start_server_prod.sh \
    && docker/composer install --no-dev \
    && scripts/database/init-db.sh ); then
    echo "Failed applying updates."
    exit 1
fi

echo "Update complete."

# Send Discord notification (if webhook set)
discordNotificationWebhook=$(get_env_value DISCORD_NOTIFICATION_WEBHOOK)
if [[ -z "$discordNotificationWebhook" ]]; then
    echo "DISCORD_NOTIFICATION_WEBHOOK not set. Exiting."
    exit 0
fi

frontendCommitShortFormat=$(echo "$remoteFrontendCommit" | cut -c1-7)
backendCommitShortFormat=$(echo "$remoteBackendCommit" | cut -c1-7)
message="Zet Web has been updated to the latest version.\n\n"
message+="Current version:\n"
message+="Frontend: [$frontendCommitShortFormat](<$frontendRepoUrl/commit/$remoteFrontendCommit>)\n"
message+="Backend: [$backendCommitShortFormat](<$backendRepoUrl/commit/$remoteBackendCommit>)\n\n"
message+="Time of update: $(date +'%Y-%m-%d %H:%M:%S')"


curl -X POST "$discordNotificationWebhook" \
    -H "Content-Type: application/json" \
    -d "{\"content\": \"$message\"}"

echo "Discord notification sent."