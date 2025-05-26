import csv
import json
from typing import List, Dict

# INSTRUCTIONS:
# Call from project root only if stops.txt has been updated
# python3 scripts/gtfs/get_station_list.py
# after that gzip the output file:
# gzip -k9 scripts/gtfs/generated_stops/stops.json

def parse_stops_file(file_path: str) -> List[Dict[str, str]]:
    with open(file_path, newline='', encoding='utf-8') as csvfile:
        reader = csv.DictReader(csvfile)
        return [dict(row) for row in reader]

def save_stops_to_json(stops: List[Dict[str, str]], output_path: str) -> None:
    with open(output_path, 'w', encoding='utf-8') as jsonfile:
        json.dump(stops, jsonfile, ensure_ascii=False, indent=2)

if __name__ == "__main__":
    stops = parse_stops_file("scripts/gtfs/static_gtfs_files/stops.txt")
    save_stops_to_json(stops, "scripts/gtfs/generated_stops/stops.json")