#!/usr/bin/env bash

# INSTRUCTIONS:
# Call from project root to update scripts/gtfs/static_gtfs_files/bajs.txt content

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

# Fetch nextbike data and parse bike stations
curl -s "https://maps.nextbike.net/maps/nextbike-live.json?domains=hd&list_cities=0&bikes=0" \
  | jq '[.countries[0].cities[0].places[] | {uid, lat, lng, name, bikes_available_to_rent, bike_racks}]' \
  > scripts/gtfs/static_gtfs_files/bajs.txt

echo "Bajs stations have been updated successfully."
exit 0
