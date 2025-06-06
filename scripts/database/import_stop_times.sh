#!/usr/bin/env bash

current_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

# Unzip stop_times file
gzip -dc "${current_path}/../gtfs/static_gtfs_files/stop_times.txt.gz" > "${current_path}/../gtfs/static_gtfs_files/stop_times.txt"

# Get only the following columns: trip_id, arrival_time, stop_id, stop_sequence
cut -d',' -f1,2,4,5 "${current_path}/../gtfs/static_gtfs_files/stop_times.txt" > "${current_path}/../gtfs/static_gtfs_files/stop_times_clean.csv"
docker cp "${current_path}/../gtfs/static_gtfs_files/stop_times_clean.csv" zet-gtfs-db:/tmp/stop_times.txt

docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d zetgtfs_db <<EOF
TRUNCATE stop_times;

\\COPY stop_times (trip_id, arrival_time, stop_id, stop_sequence) FROM '/tmp/stop_times.txt' WITH (FORMAT csv, HEADER, DELIMITER ',');
EOF
