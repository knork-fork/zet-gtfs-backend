import csv
import json
from typing import List, Dict

# INSTRUCTIONS:
# Call from project root only if stops.txt has been updated
# python3 scripts/gtfs/generate_stops.py
# after that gzip the output file:
# gzip -k9 scripts/gtfs/generated_stops/stops.json

def parse_stops_file(file_path: str) -> List[Dict[str, str]]:
    filtered = []
    with open(file_path, newline='', encoding='utf-8') as csvfile:
        reader = csv.DictReader(csvfile)
        for row in reader:
            # only include stops that have a non-empty parent_station
            if not row.get('parent_station', '').strip():
                continue

            filtered.append({
                'stop_id': row['stop_id'],
                'stop_name': row['stop_name'],
                'stop_lat': row['stop_lat'],
                'stop_lon': row['stop_lon'],
            })
    return filtered

def save_stops_to_json(stops: List[Dict[str, str]], output_path: str) -> None:
    with open(output_path, 'w', encoding='utf-8') as jsonfile:
        json.dump(stops, jsonfile, ensure_ascii=False, indent=2)

if __name__ == "__main__":
    stops = parse_stops_file("scripts/gtfs/static_gtfs_files/stops.txt")
    save_stops_to_json(stops, "scripts/gtfs/generated_stops/stops.json")