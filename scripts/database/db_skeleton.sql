CREATE TABLE schema_migrations (
    version VARCHAR(255) PRIMARY KEY,
    executed_at TIMESTAMP DEFAULT NULL,
    execution_time_seconds DOUBLE PRECISION DEFAULT NULL
);

CREATE TABLE stop_times (
    id SERIAL PRIMARY KEY,
    stop_id TEXT NOT NULL,
    trip_id TEXT NOT NULL,
    arrival_time TEXT NOT NULL,
    stop_sequence INT NOT NULL
);

CREATE INDEX idx_stop_times_stop_id ON stop_times (stop_id);
CREATE INDEX idx_stop_times_trip_id ON stop_times (trip_id);

CREATE TABLE stops (
    id SERIAL PRIMARY KEY,
    stop_id TEXT NOT NULL,
    stop_name TEXT NOT NULL,
    stop_lat DOUBLE PRECISION NOT NULL,
    stop_lon DOUBLE PRECISION NOT NULL,
    parent_station TEXT DEFAULT NULL
);

CREATE INDEX idx_stops_stop_id ON stops (stop_id);
