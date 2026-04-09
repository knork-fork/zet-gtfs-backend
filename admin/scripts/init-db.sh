#!/usr/bin/env bash

current_path=$( cd "$(dirname "${BASH_SOURCE[0]}")" ; pwd -P )

db_name="zetgtfs_admin_db"

if [[ "$1" == "--reinit" ]]; then
    echo "Reinitializing admin database..."

    docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
        psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -c "SELECT pg_terminate_backend(pid) FROM pg_stat_activity WHERE datname = '${db_name}' AND pid <> pg_backend_pid();"

    docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
        psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -c "DROP DATABASE IF EXISTS ${db_name};"
    if [[ $? -ne 0 ]]; then
        echo "Cannot drop database ${db_name}. Aborting..."
        exit 2
    fi
fi

# Create database if it doesn't exist
docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
    psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -tc "SELECT 1 FROM pg_database WHERE datname = '${db_name}'" | grep -q 1
if [[ $? -ne 0 ]]; then
    echo "Creating admin database..."
    docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
        psql -U zetgtfs_user -h zet-gtfs-postgres -d postgres -c "CREATE DATABASE ${db_name};"
    if [[ $? -ne 0 ]]; then
        echo "Failed creating database ${db_name}."
        exit 2
    fi

    docker exec -e PGPASSWORD=zetgtfs_pass -i zet-gtfs-db \
        psql -U zetgtfs_user -h zet-gtfs-postgres -d ${db_name} < ${current_path}/db_skeleton.sql
    if [[ $? -ne 0 ]]; then
        echo "Failed importing admin database schema."
        exit 2
    fi

    echo "Admin database initialized."
else
    echo "Admin database already exists, skipping. Use --reinit to reinitialize."
fi

exit 0
