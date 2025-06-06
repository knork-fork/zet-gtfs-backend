#!/usr/bin/env bash

# INSTRUCTIONS:
# Call from project root to update scripts/gtfs/static_gtfs_files/calendar_dates.txt content

# Check if the script is being run from the root of the repository
if [ ! -d ".git" ]; then
  echo "This script must be run from the root of the repository."
  exit 1
fi

if [ ! -d "var/" ]; then
  echo "The 'var' directory does not exist. Please start the container first."
  exit 1
fi

# Download and unzip latest GTFS static files
curl -L -o scripts/gtfs/static_gtfs_files/gtfs_static.zip https://www.zet.hr/gtfs-scheduled/latest
unzip -o scripts/gtfs/static_gtfs_files/gtfs_static.zip -d scripts/gtfs/static_gtfs_files/gtfs_static_unzipped

# Update static files
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/calendar_dates.txt scripts/gtfs/static_gtfs_files/calendar_dates.txt
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/calendar.txt scripts/gtfs/static_gtfs_files/calendar.txt
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/routes.txt scripts/gtfs/static_gtfs_files/routes.txt
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/shapes.txt scripts/gtfs/static_gtfs_files/shapes.txt
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/stop_times.txt scripts/gtfs/static_gtfs_files/stop_times.txt
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/stops.txt scripts/gtfs/static_gtfs_files/stops.txt
mv scripts/gtfs/static_gtfs_files/gtfs_static_unzipped/trips.txt scripts/gtfs/static_gtfs_files/trips.txt

# Gzip stop_times.txt as it's too large for git
gzip -kf9 scripts/gtfs/static_gtfs_files/stop_times.txt

# Update geojson routes (hardcoded for routeId 1 to 313)
for id in $(seq 1 313); do
  python3 scripts/gtfs/generate_geojson_route.py "$id"
done
find scripts/gtfs/generated_geojson_routes -type f -size 51c -delete

# Update filtered stops file
python3 scripts/gtfs/generate_stops.py
gzip -kf9 scripts/gtfs/generated_stops/stops.json

# Cleanup
rm scripts/gtfs/static_gtfs_files/gtfs_static.zip
rm -rf scripts/gtfs/static_gtfs_files/gtfs_static_unzipped

echo "Static files have been updated successfully, please commit the changes."
echo "Make sure to run init-db.sh with --reinit to reinitialize the database with the new static files."
exit 0