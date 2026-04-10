const fs = require('fs');
const path = require('path');
const { parse } = require('csv-parse/sync');

const MAX_ROUTE_ID = 313;
const EMPTY_FEATURE_COLLECTION = { type: 'FeatureCollection', features: [] };

function loadShapes(shapesPath) {
  const content = fs.readFileSync(shapesPath, 'utf-8');
  const records = parse(content, { columns: true, skip_empty_lines: true });

  const shapes = new Map();
  for (const row of records) {
    const shapeId = row.shape_id;
    if (!shapes.has(shapeId)) shapes.set(shapeId, []);
    shapes.get(shapeId).push({
      seq: parseInt(row.shape_pt_sequence, 10),
      coord: [parseFloat(row.shape_pt_lon), parseFloat(row.shape_pt_lat)],
    });
  }

  for (const [id, points] of shapes) {
    points.sort((a, b) => a.seq - b.seq);
    shapes.set(id, points.map(p => p.coord));
  }

  return shapes;
}

function getShapeIdsForRoute(tripsPath, routeId) {
  const content = fs.readFileSync(tripsPath, 'utf-8');
  const records = parse(content, { columns: true, skip_empty_lines: true });

  const shapeIds = new Set();
  for (const row of records) {
    if (row.route_id === String(routeId)) {
      shapeIds.add(row.shape_id);
    }
  }
  return shapeIds;
}

function generateGeojsonForRoute(routeId, shapesMap, shapeIds) {
  const features = [];
  for (const shapeId of shapeIds) {
    const coords = shapesMap.get(shapeId);
    if (coords && coords.length > 0) {
      features.push({
        type: 'Feature',
        geometry: { type: 'LineString', coordinates: coords },
        properties: { shape_id: shapeId },
      });
    }
  }
  return { type: 'FeatureCollection', features };
}

function generateAllRoutes(shapesPath, tripsPath, outputDir) {
  fs.mkdirSync(outputDir, { recursive: true });

  const shapesMap = loadShapes(shapesPath);

  const tripsContent = fs.readFileSync(tripsPath, 'utf-8');
  const tripsRecords = parse(tripsContent, { columns: true, skip_empty_lines: true });

  const routeShapeIds = new Map();
  for (const row of tripsRecords) {
    const rid = row.route_id;
    if (!routeShapeIds.has(rid)) routeShapeIds.set(rid, new Set());
    routeShapeIds.get(rid).add(row.shape_id);
  }

  const generated = {};
  for (let id = 1; id <= MAX_ROUTE_ID; id++) {
    const shapeIds = routeShapeIds.get(String(id));
    if (!shapeIds || shapeIds.size === 0) continue;

    const geojson = generateGeojsonForRoute(id, shapesMap, shapeIds);
    if (geojson.features.length === 0) continue;

    const filePath = path.join(outputDir, `route_${id}.geojson`);
    fs.writeFileSync(filePath, JSON.stringify(geojson));
    generated[id] = geojson;
  }

  return generated;
}

module.exports = { generateAllRoutes };
