#!/usr/bin/env bash

current_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

if [[ -f "${current_path}/db_initialized" ]]; then
    if [[ "$1" != "--reinit" ]]; then
        echo "Database already initialized, use --reinit to reinitialize."
        exit 0
    fi

    echo "Reinitializing database..."
    rm -f "${current_path}/db_initialized"
else
    echo "Initializing database..."
fi

db_name="zetgtfs_db"

echo "Running init-db.sh for ${db_name}..."

# Drop all processes connected to the database
docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${db_name}' AND pid <> pg_backend_pid();"

# Drop database if it existed
docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -c "DROP DATABASE IF EXISTS ${db_name};"
if [[ $? -ne 0 ]]
then
    echo "Cannot drop database ${db_name}. Aborting..."
    exit 2
fi

# Create database
docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -c "CREATE DATABASE ${db_name};"
if [[ $? -ne 0 ]]
then
    echo "Failed creating database ${db_name}."
    exit 2
fi

# Import database dump
docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d ${db_name} < ${current_path}/db_skeleton.sql
if [[ $? -ne 0 ]]
then
    echo "Failed importing database dump to ${db_name}."
    exit 2
fi

# Import stop times
${current_path}/import_stop_times.sh
if [[ $? -ne 0 ]]
then
    echo "Failed importing stop times."
    exit 2
fi

echo "Database initialized."
touch ${current_path}/db_initialized
exit 0