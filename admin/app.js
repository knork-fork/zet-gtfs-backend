const express = require('express');
const session = require('express-session');
const bcrypt = require('bcryptjs');
const { Pool } = require('pg');
const path = require('path');

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
    body: 'actions',
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

app.listen(port, '0.0.0.0', () => {
  console.log(`Admin panel running on port ${port}`);
});
