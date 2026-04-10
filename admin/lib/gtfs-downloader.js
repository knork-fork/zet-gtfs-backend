const fs = require('fs');
const path = require('path');
const unzipper = require('unzipper');

const GTFS_URL = 'https://www.zet.hr/gtfs-scheduled/latest';

async function downloadAndExtract(tempDir) {
  fs.mkdirSync(tempDir, { recursive: true });

  const response = await fetch(GTFS_URL);
  if (!response.ok) {
    throw new Error(`Failed to download GTFS: ${response.status} ${response.statusText}`);
  }

  const buffer = Buffer.from(await response.arrayBuffer());
  const directory = await unzipper.Open.buffer(buffer);

  const needed = ['shapes.txt', 'trips.txt'];
  for (const entry of directory.files) {
    const basename = path.basename(entry.path);
    if (needed.includes(basename)) {
      const content = await entry.buffer();
      fs.writeFileSync(path.join(tempDir, basename), content);
    }
  }

  for (const file of needed) {
    if (!fs.existsSync(path.join(tempDir, file))) {
      throw new Error(`Missing ${file} in GTFS archive`);
    }
  }

  return {
    shapesPath: path.join(tempDir, 'shapes.txt'),
    tripsPath: path.join(tempDir, 'trips.txt'),
  };
}

module.exports = { downloadAndExtract };
