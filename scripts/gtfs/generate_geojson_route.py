import csv
import json
import sys
from collections import defaultdict

# INSTRUCTIONS:
# Call from project root
# for id in $(seq 1 313); do
#   python3 scripts/gtfs/generate_geojson_route.py "$id"
# done
# Cleanup empty files: find scripts/gtfs/generated_geojson_routes -type f -size 51c -delete
# This is needed only once per static file update

if len(sys.argv) != 2:
    print("Usage: python generate_geojson_for_route.py <route_id>")
    sys.exit(1)

# Load shape points by shape_id
def load_shapes(shapes_file):
    shapes = defaultdict(list)
    with open(shapes_file, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            shape_id = row['shape_id']
            lat = float(row['shape_pt_lat'])
            lon = float(row['shape_pt_lon'])
            seq = int(row['shape_pt_sequence'])
            shapes[shape_id].append((seq, [lon, lat]))
    # Sort each shape by shape_pt_sequence
    for shape_id in shapes:
        shapes[shape_id] = [pt[1] for pt in sorted(shapes[shape_id])]
    return shapes

# Map route_id to shape_ids via trips
def get_shape_ids_for_route(trips_file, route_id):
    shape_ids = set()
    with open(trips_file, newline='', encoding='utf-8') as f:
        reader = csv.DictReader(f)
        for row in reader:
            if row['route_id'] == route_id:
                shape_ids.add(row['shape_id'])
    return shape_ids

# Create GeoJSON LineString
def create_geojson_linestring(coords):
    return {
        "type": "Feature",
        "geometry": {
            "type": "LineString",
            "coordinates": coords
        },
        "properties": {}
    }

# Main function
def generate_geojson_for_route(route_id, shapes_file, trips_file):
    shapes = load_shapes(shapes_file)
    shape_ids = get_shape_ids_for_route(trips_file, route_id)
    features = []

    for shape_id in shape_ids:
        coords = shapes.get(shape_id)
        if coords:
            features.append(create_geojson_linestring(coords))

    return {
        "type": "FeatureCollection",
        "features": features
    }

route_id = sys.argv[1]
print (f"Generating GeoJSON for route {route_id}...")
shapes_file = "scripts/gtfs/static_gtfs_files/shapes.txt"
trips_file = "scripts/gtfs/static_gtfs_files/trips.txt"

geojson = generate_geojson_for_route(route_id, shapes_file, trips_file)

# Save to file
with open(f"scripts/gtfs/generated_geojson_routes/route_{route_id}.geojson", "w", encoding="utf-8") as f:
    json.dump(geojson, f, indent=2)

print(f"GeoJSON for route {route_id} saved.")