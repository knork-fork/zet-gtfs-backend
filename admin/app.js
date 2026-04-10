const express = require('express');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const { Pool } = require('pg');
const path = require('path');
const fs = require('fs');
const { downloadAndExtract } = require('./lib/gtfs-downloader');
const { generateAllRoutes } = require('./lib/geojson-generator');

const app = express();
const port = 3000;

const pool = new Pool({
  host: process.env.DB_HOST,
  port: parseInt(process.env.DB_PORT),
  database: process.env.DB_NAME,
  user: process.env.DB_USER,
  password: process.env.DB_PASSWORD,
});

app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));
app.use('/admin/static', express.static(path.join(__dirname, 'public')));
app.use(express.urlencoded({ extended: false }));
app.use(express.json());

const DYNAMIC_DIR = '/application/resources/gtfs/dynamic/routes';
const TEMP_DIR = '/application/resources/gtfs/temp';
const STAGING_DIR = '/application/resources/gtfs/staging';
const STATIC_DIR = '/application/static_geojson';

[DYNAMIC_DIR, TEMP_DIR, STAGING_DIR].forEach(d => fs.mkdirSync(d, { recursive: true }));
app.use(session({
  secret: 'zet-admin-session-secret',
  resave: false,
  saveUninitialized: false,
  cookie: { httpOnly: true, sameSite: 'lax' },
}));

function requireAuth(req, res, next) {
  if (!req.session.user) return res.redirect('/admin/login');
  next();
}

async function logAction(username, action, details = null) {
  await pool.query(
    'INSERT INTO admin_logs (username, action, details) VALUES ($1, $2, $3)',
    [username, action, details]
  );
}

// Login
app.get('/admin/login', (req, res) => {
  if (req.session.user) return res.redirect('/admin/actions/schedule');
  res.render('login', { error: null });
});

app.post('/admin/login', async (req, res) => {
  const { username, password } = req.body;
  const result = await pool.query('SELECT * FROM admin_users WHERE username = $1', [username]);
  const user = result.rows[0];

  if (!user || !bcrypt.compareSync(password, user.password)) {
    return res.render('login', { error: 'Invalid username or password' });
  }

  req.session.user = user.username;
  await logAction(user.username, 'login');
  res.redirect('/admin/actions/schedule');
});

// Logout
app.get('/admin/logout', (req, res) => {
  req.session.destroy(() => res.redirect('/admin/login'));
});

// Protected routes
app.use(requireAuth);

app.get('/admin', (req, res) => res.redirect('/admin/actions/schedule'));

app.get('/admin/actions/:type', (req, res) => {
  const type = req.params.type;
  if (type !== 'schedule' && type !== 'routes') return res.status(404).send('Not found');
  const titles = { schedule: 'Update schedule', routes: 'Update routes (geojson)' };
  res.render('layout', {
    user: req.session.user,
    active: `/admin/actions/${type}`,
    title: titles[type],
    body: type === 'routes' ? 'routes' : 'actions',
    data: { type },
  });
});

app.get('/admin/logs', async (req, res) => {
  const page = Math.max(1, parseInt(req.query.page) || 1);
  const perPage = 50;
  const offset = (page - 1) * perPage;

  const [logs, countResult] = await Promise.all([
    pool.query('SELECT * FROM admin_logs ORDER BY created_at DESC LIMIT $1 OFFSET $2', [perPage, offset]),
    pool.query('SELECT COUNT(*) FROM admin_logs'),
  ]);

  const total = parseInt(countResult.rows[0].count);
  const totalPages = Math.ceil(total / perPage);

  res.render('layout', {
    user: req.session.user,
    active: '/admin/logs',
    title: 'Logs',
    body: 'logs',
    data: { logs: logs.rows, page, totalPages },
  });
});

app.get('/admin/settings', (req, res) => {
  res.render('layout', {
    user: req.session.user,
    active: '/admin/settings',
    title: 'Settings',
    body: 'settings',
    data: { success: null, error: null },
  });
});

app.post('/admin/settings', async (req, res) => {
  const { current_password, new_password, confirm_password } = req.body;

  const result = await pool.query('SELECT * FROM admin_users WHERE username = $1', [req.session.user]);
  const user = result.rows[0];

  let error = null;
  if (!bcrypt.compareSync(current_password, user.password)) {
    error = 'Current password is incorrect';
  } else if (new_password !== confirm_password) {
    error = 'New passwords do not match';
  } else if (new_password.length < 6) {
    error = 'New password must be at least 6 characters';
  }

  if (error) {
    return res.render('layout', {
      user: req.session.user,
      active: '/admin/settings',
      title: 'Settings',
      body: 'settings',
      data: { success: null, error },
    });
  }

  const hash = bcrypt.hashSync(new_password, 10);
  await pool.query('UPDATE admin_users SET password = $1 WHERE username = $2', [hash, req.session.user]);
  await logAction(req.session.user, 'password_change');

  res.render('layout', {
    user: req.session.user,
    active: '/admin/settings',
    title: 'Settings',
    body: 'settings',
    data: { success: 'Password updated successfully', error: null },
  });
});

// --- Routes GeoJSON API ---

function getDynamicLastUpdate() {
  try {
    const files = fs.readdirSync(DYNAMIC_DIR).filter(f => f.endsWith('.geojson'));
    if (files.length === 0) return null;
    let latest = 0;
    for (const f of files) {
      const stat = fs.statSync(path.join(DYNAMIC_DIR, f));
      if (stat.mtimeMs > latest) latest = stat.mtimeMs;
    }
    return new Date(latest).toISOString();
  } catch { return null; }
}

function hasTempPull() {
  try {
    return fs.readdirSync(TEMP_DIR).some(f => f.endsWith('.geojson'));
  } catch { return false; }
}

function getStagedRoutes() {
  try {
    return fs.readdirSync(STAGING_DIR)
      .filter(f => f.endsWith('.geojson'))
      .map(f => parseInt(f.match(/route_(\d+)/)?.[1], 10))
      .filter(n => !isNaN(n));
  } catch { return []; }
}

function getDynamicRouteIds() {
  try {
    return fs.readdirSync(DYNAMIC_DIR)
      .filter(f => f.endsWith('.geojson'))
      .map(f => parseInt(f.match(/route_(\d+)/)?.[1], 10))
      .filter(n => !isNaN(n));
  } catch { return []; }
}

function getCurrentGeojson(routeId) {
  const dynamicFile = path.join(DYNAMIC_DIR, `route_${routeId}.geojson`);
  const staticFile = path.join(STATIC_DIR, `route_${routeId}.geojson`);
  const file = fs.existsSync(dynamicFile) ? dynamicFile : fs.existsSync(staticFile) ? staticFile : null;
  if (!file) return null;
  return JSON.parse(fs.readFileSync(file, 'utf-8'));
}

app.get('/admin/api/routes/status', (req, res) => {
  res.json({
    lastUpdate: getDynamicLastUpdate(),
    hasTempPull: hasTempPull(),
    stagedRoutes: getStagedRoutes(),
    dynamicRouteIds: getDynamicRouteIds(),
  });
});

app.get('/admin/api/routes/resume', (req, res) => {
  if (!hasTempPull()) {
    return res.status(404).json({ error: 'No pull to resume' });
  }

  const freshData = {};
  const files = fs.readdirSync(TEMP_DIR).filter(f => f.endsWith('.geojson'));
  for (const f of files) {
    const match = f.match(/route_(\d+)/);
    if (match) {
      freshData[parseInt(match[1], 10)] = JSON.parse(fs.readFileSync(path.join(TEMP_DIR, f), 'utf-8'));
    }
  }

  const routeIds = Object.keys(freshData).map(Number).sort((a, b) => a - b);
  res.json({ routeIds, freshData, stagedRoutes: getStagedRoutes() });
});

app.post('/admin/api/routes/pull', async (req, res) => {
  if (hasTempPull()) {
    return res.status(409).json({ error: 'A pull already exists. Publish or cancel first.' });
  }

  try {
    const workDir = path.join(TEMP_DIR, '_work');
    fs.mkdirSync(workDir, { recursive: true });

    const { shapesPath, tripsPath } = await downloadAndExtract(workDir);
    const generated = generateAllRoutes(shapesPath, tripsPath, TEMP_DIR);

    // Clean up work files
    fs.rmSync(workDir, { recursive: true, force: true });

    const routeIds = Object.keys(generated).map(Number).sort((a, b) => a - b);
    await logAction(req.session.user, 'routes_pull', `Pulled fresh GTFS geojson, ${routeIds.length} routes generated`);

    res.json({ routeIds, freshData: generated });
  } catch (err) {
    // Clean up on failure
    fs.rmSync(TEMP_DIR, { recursive: true, force: true });
    fs.mkdirSync(TEMP_DIR, { recursive: true });
    res.status(500).json({ error: err.message });
  }
});

app.get('/admin/api/routes/current/:id', (req, res) => {
  const routeId = parseInt(req.params.id, 10);
  if (isNaN(routeId)) return res.status(400).json({ error: 'Invalid route ID' });

  const geojson = getCurrentGeojson(routeId);
  if (!geojson) return res.status(404).json({ error: 'Not found' });
  res.json(geojson);
});

app.get('/admin/api/routes/staged-pick/:id', (req, res) => {
  const routeId = parseInt(req.params.id, 10);
  if (isNaN(routeId)) return res.status(400).json({ error: 'Invalid route ID' });

  const file = path.join(STAGING_DIR, `route_${routeId}.geojson`);
  if (!fs.existsSync(file)) return res.status(404).json({ error: 'Not found' });
  res.json(JSON.parse(fs.readFileSync(file, 'utf-8')));
});

app.post('/admin/api/routes/stage', (req, res) => {
  const { routeId, featureIndex } = req.body;
  if (!routeId || featureIndex === undefined) {
    return res.status(400).json({ error: 'routeId and featureIndex required' });
  }

  const freshFile = path.join(TEMP_DIR, `route_${routeId}.geojson`);
  if (!fs.existsSync(freshFile)) {
    return res.status(404).json({ error: 'Fresh geojson not found for this route' });
  }

  const geojson = JSON.parse(fs.readFileSync(freshFile, 'utf-8'));
  const feature = geojson.features[featureIndex];
  if (!feature) {
    return res.status(400).json({ error: 'Invalid feature index' });
  }

  const staged = { type: 'FeatureCollection', features: [feature] };
  fs.mkdirSync(STAGING_DIR, { recursive: true });
  fs.writeFileSync(path.join(STAGING_DIR, `route_${routeId}.geojson`), JSON.stringify(staged));

  res.json({ ok: true });
});

app.delete('/admin/api/routes/stage/:id', (req, res) => {
  const routeId = parseInt(req.params.id, 10);
  if (isNaN(routeId)) return res.status(400).json({ error: 'Invalid route ID' });

  const file = path.join(STAGING_DIR, `route_${routeId}.geojson`);
  if (fs.existsSync(file)) fs.unlinkSync(file);
  res.json({ ok: true });
});

app.post('/admin/api/routes/publish', async (req, res) => {
  try {
    const stagingFiles = fs.readdirSync(STAGING_DIR).filter(f => f.endsWith('.geojson'));
    if (stagingFiles.length === 0) {
      return res.status(400).json({ error: 'Nothing staged to publish' });
    }

    fs.mkdirSync(DYNAMIC_DIR, { recursive: true });
    for (const file of stagingFiles) {
      fs.copyFileSync(path.join(STAGING_DIR, file), path.join(DYNAMIC_DIR, file));
    }

    // Cleanup temp and staging
    fs.rmSync(TEMP_DIR, { recursive: true, force: true });
    fs.rmSync(STAGING_DIR, { recursive: true, force: true });
    fs.mkdirSync(TEMP_DIR, { recursive: true });
    fs.mkdirSync(STAGING_DIR, { recursive: true });

    await logAction(req.session.user, 'routes_publish', `Published ${stagingFiles.length} staged route files`);
    res.json({ ok: true, lastUpdate: new Date().toISOString() });
  } catch (err) {
    res.status(500).json({ error: err.message });
  }
});

app.post('/admin/api/routes/cancel', async (req, res) => {
  fs.rmSync(TEMP_DIR, { recursive: true, force: true });
  fs.rmSync(STAGING_DIR, { recursive: true, force: true });
  fs.mkdirSync(TEMP_DIR, { recursive: true });
  fs.mkdirSync(STAGING_DIR, { recursive: true });

  await logAction(req.session.user, 'routes_cancel', 'Cancelled route geojson update');
  res.json({ ok: true });
});

app.listen(port, '0.0.0.0', () => {
  console.log(`Admin panel running on port ${port}`);
});
