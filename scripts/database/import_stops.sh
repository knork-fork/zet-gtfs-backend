#!/usr/bin/env bash

current_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

# clean csv version is commited so no reliance on mlr is needed
if [[ -f "${current_path}/../gtfs/static_gtfs_files/stops_clean.csv" ]]; then
    if [[ "$1" != "--recreate" ]]; then
        echo "Using existing stops_clean.csv, use --recreate to recreate from original stops.txt."
    else
        echo "Recreating stops_clean.csv from original stops.txt..."
        rm -f "${current_path}/../gtfs/static_gtfs_files/stops_clean.csv"
        mlr --icsv --ocsv cut -f stop_id,stop_name,stop_lat,stop_lon,parent_station "${current_path}/../gtfs/static_gtfs_files/stops.txt" > "${current_path}/../gtfs/static_gtfs_files/stops_clean.csv"

    fi
else
    echo "Creating stops_clean.csv from original stops.txt..."
    mlr --icsv --ocsv cut -f stop_id,stop_name,stop_lat,stop_lon,parent_station "${current_path}/../gtfs/static_gtfs_files/stops.txt" > "${current_path}/../gtfs/static_gtfs_files/stops_clean.csv"
fi

docker cp "${current_path}/../gtfs/static_gtfs_files/stops_clean.csv" zet-gtfs-db:/tmp/stops.txt

docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d zetgtfs_db <<EOF
TRUNCATE stops;

\\COPY stops (stop_id, stop_name, stop_lat, stop_lon, parent_station) FROM '/tmp/stops.txt' WITH (FORMAT csv, HEADER, DELIMITER ',');
EOF
