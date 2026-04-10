(function () {
  // --- State ---
  let map = null;
  let blueLayers = [];
  let redLayers = [];
  let freshData = {};       // routeId -> geojson FeatureCollection
  let allRouteIds = [];     // sorted union of dynamic + fresh route IDs
  let stagedPicks = {};     // routeId -> featureIndex
  let currentRouteId = null;
  let lastHoveredIndex = null; // track last hovered feature per current view
  let dynamicRouteIds = []; // existing in dynamic folder

  // --- DOM refs ---
  const btnPull = document.getElementById('btn-pull');
  const btnPublish = document.getElementById('btn-publish');
  const btnCancel = document.getElementById('btn-cancel');
  const statusText = document.getElementById('status-text');
  const lastUpdateEl = document.getElementById('last-update');
  const routesContent = document.getElementById('routes-content');
  const mapContainer = document.getElementById('map-container');
  const featureBar = document.getElementById('feature-bar');
  const routeList = document.getElementById('route-list');

  // --- Helpers ---
  function setStatus(msg) { statusText.textContent = msg; }
  function setButtons(pull, publish, cancel) {
    btnPull.disabled = !pull;
    btnPublish.disabled = !publish;
    btnCancel.disabled = !cancel;
  }

  function formatDate(iso) {
    if (!iso) return 'none';
    const d = new Date(iso);
    return d.toLocaleString();
  }

  async function apiGet(url) {
    const res = await fetch(url);
    if (!res.ok) {
      if (res.status === 404) return null;
      throw new Error(res.statusText);
    }
    return res.json();
  }

  async function apiPost(url, body) {
    const opts = { method: 'POST', headers: { 'Content-Type': 'application/json' } };
    if (body) opts.body = JSON.stringify(body);
    const res = await fetch(url, opts);
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.error || res.statusText);
    }
    return res.json();
  }

  async function apiDelete(url) {
    const res = await fetch(url, { method: 'DELETE' });
    if (!res.ok) {
      const err = await res.json().catch(() => ({ error: res.statusText }));
      throw new Error(err.error || res.statusText);
    }
    return res.json();
  }

  // --- Map ---
  function initMap() {
    if (map) return;
    map = L.map(mapContainer, { zoomControl: true }).setView([45.815, 15.98], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '&copy; OpenStreetMap contributors',
      maxZoom: 19,
    }).addTo(map);
  }

  function clearMap() {
    blueLayers.forEach(l => map.removeLayer(l));
    redLayers.forEach(l => map.removeLayer(l));
    blueLayers = [];
    redLayers = [];
  }

  function drawBlue(geojson) {
    if (!geojson || !geojson.features) return;
    for (const feature of geojson.features) {
      const layer = L.geoJSON(feature, {
        style: { color: '#3388ff', weight: 5, opacity: 0.7 },
      }).addTo(map);
      blueLayers.push(layer);
    }
  }

  function drawRedSingle(geojson, featureIndex) {
    redLayers.forEach(l => map.removeLayer(l));
    redLayers = [];
    if (!geojson || !geojson.features || !geojson.features[featureIndex]) return;
    const layer = L.geoJSON(geojson.features[featureIndex], {
      style: { color: '#e74c3c', weight: 5, opacity: 0.9 },
    }).addTo(map);
    redLayers.push(layer);
  }

  function fitBounds() {
    const allLayers = [...blueLayers, ...redLayers];
    if (allLayers.length === 0) return;
    const group = L.featureGroup(allLayers);
    map.fitBounds(group.getBounds(), { padding: [30, 30] });
  }

  // --- Sidebar ---
  function renderRouteList() {
    routeList.innerHTML = '';
    for (const id of allRouteIds) {
      const item = document.createElement('div');
      item.className = 'route-item' + (id === currentRouteId ? ' active' : '') + (stagedPicks[id] !== undefined ? ' staged' : '');

      const hasFresh = freshData[id] && freshData[id].features && freshData[id].features.length > 0;
      const isDynamic = dynamicRouteIds.includes(id);
      let label = id;
      if (!hasFresh && !isDynamic) label += ' (empty)';

      item.textContent = label;
      if (stagedPicks[id] !== undefined) {
        const check = document.createElement('span');
        check.className = 'route-check';
        check.textContent = ' \u2713';
        item.appendChild(check);
      }
      item.addEventListener('click', () => selectRoute(id));
      routeList.appendChild(item);
    }
  }

  // --- Feature bar ---
  function renderFeatureBar(geojson, routeId) {
    featureBar.innerHTML = '';
    if (!geojson || !geojson.features || geojson.features.length === 0) {
      featureBar.innerHTML = '<span class="feature-empty">No fresh data for this route</span>';
      return;
    }

    for (let i = 0; i < geojson.features.length; i++) {
      const btn = document.createElement('button');
      const shapeId = geojson.features[i].properties?.shape_id || '';
      btn.className = 'feature-btn' + (stagedPicks[routeId] === i ? ' selected' : '');
      btn.textContent = 'Path ' + (i + 1) + (shapeId ? ' (' + shapeId + ')' : '');

      btn.addEventListener('mouseenter', () => {
        lastHoveredIndex = i;
        drawRedSingle(geojson, i);
      });
      btn.addEventListener('click', () => stageFeature(routeId, i));

      featureBar.appendChild(btn);
    }

    if (stagedPicks[routeId] !== undefined) {
      const cancelBtn = document.createElement('button');
      cancelBtn.className = 'feature-btn feature-btn-cancel';
      cancelBtn.textContent = '\u2715';
      cancelBtn.title = 'Deselect path';
      cancelBtn.addEventListener('click', () => unstageFeature(routeId));
      featureBar.appendChild(cancelBtn);
    }
  }

  // --- Route selection ---
  async function selectRoute(routeId) {
    currentRouteId = routeId;
    renderRouteList();
    clearMap();
    featureBar.innerHTML = '<span class="feature-empty">Loading...</span>';

    // Draw blue (current) path
    const current = await apiGet('/admin/api/routes/current/' + routeId);
    if (current) drawBlue(current);

    // Draw red (fresh) path - only if previously staged, otherwise none until hover
    const fresh = freshData[routeId];
    lastHoveredIndex = null;
    if (stagedPicks[routeId] !== undefined && fresh) {
      drawRedSingle(fresh, stagedPicks[routeId]);
      lastHoveredIndex = stagedPicks[routeId];
    }

    fitBounds();
    renderFeatureBar(fresh, routeId);
  }

  // --- Staging ---
  async function stageFeature(routeId, featureIndex) {
    try {
      await apiPost('/admin/api/routes/stage', { routeId, featureIndex });
      stagedPicks[routeId] = featureIndex;

      // Update UI
      renderFeatureBar(freshData[routeId], routeId);
      drawRedSingle(freshData[routeId], featureIndex);
      renderRouteList();

      // Auto-advance
      advanceToNext(routeId);
    } catch (err) {
      setStatus('Stage error: ' + err.message);
    }
  }

  async function unstageFeature(routeId) {
    try {
      await apiDelete('/admin/api/routes/stage/' + routeId);
      delete stagedPicks[routeId];
      lastHoveredIndex = null;

      // Clear red, keep blue
      redLayers.forEach(l => map.removeLayer(l));
      redLayers = [];

      renderFeatureBar(freshData[routeId], routeId);
      renderRouteList();
    } catch (err) {
      setStatus('Unstage error: ' + err.message);
    }
  }

  function advanceToNext(fromRouteId) {
    const idx = allRouteIds.indexOf(fromRouteId);
    for (let i = idx + 1; i < allRouteIds.length; i++) {
      const id = allRouteIds[i];
      // Skip routes with no fresh data and no staged pick
      const hasFresh = freshData[id] && freshData[id].features && freshData[id].features.length > 0;
      if (!hasFresh && stagedPicks[id] === undefined) continue;
      selectRoute(id);
      return;
    }
    setStatus('All routes reviewed. Ready to publish.');
  }

  // --- Actions ---
  async function doPull() {
    setButtons(false, false, false);
    setStatus('Downloading and generating GTFS routes...');
    try {
      const result = await apiPost('/admin/api/routes/pull');
      freshData = result.freshData || {};
      const freshIds = result.routeIds || [];

      // Build union of dynamic + fresh route IDs
      const idSet = new Set([...dynamicRouteIds, ...freshIds]);
      allRouteIds = [...idSet].sort((a, b) => a - b);
      stagedPicks = {};

      enterComparing();
      setStatus('Pull complete. ' + freshIds.length + ' routes generated.');
    } catch (err) {
      setStatus('Pull failed: ' + err.message);
      setButtons(true, false, false);
    }
  }

  async function doPublish() {
    if (!confirm('Publish staged routes to dynamic? This will overwrite existing files.')) return;
    setButtons(false, false, false);
    setStatus('Publishing...');
    try {
      const result = await apiPost('/admin/api/routes/publish');
      lastUpdateEl.textContent = 'Last update: ' + formatDate(result.lastUpdate);
      exitComparing();
      setStatus('Published successfully.');
    } catch (err) {
      setStatus('Publish failed: ' + err.message);
      setButtons(false, true, true);
    }
  }

  async function doCancel() {
    setButtons(false, false, false);
    setStatus('Cancelling...');
    try {
      await apiPost('/admin/api/routes/cancel');
      exitComparing();
      setStatus('Cancelled.');
    } catch (err) {
      setStatus('Cancel failed: ' + err.message);
      setButtons(false, true, true);
    }
  }

  function enterComparing() {
    routesContent.style.display = 'flex';
    initMap();
    setTimeout(() => map.invalidateSize(), 100);
    setButtons(false, true, true);
    renderRouteList();

    // Auto-select first route with fresh data
    const firstWithFresh = allRouteIds.find(id => {
      const hasFresh = freshData[id] && freshData[id].features && freshData[id].features.length > 0;
      return hasFresh;
    });
    if (firstWithFresh !== undefined) {
      selectRoute(firstWithFresh);
    } else if (allRouteIds.length > 0) {
      selectRoute(allRouteIds[0]);
    }
  }

  function exitComparing() {
    routesContent.style.display = 'none';
    if (map) {
      clearMap();
    }
    featureBar.innerHTML = '';
    routeList.innerHTML = '';
    freshData = {};
    allRouteIds = [];
    stagedPicks = {};
    currentRouteId = null;
    setButtons(true, false, false);
  }

  // --- Recovery: check if temp pull exists from interrupted session ---
  async function recoverState() {
    try {
      const status = await apiGet('/admin/api/routes/status');
      lastUpdateEl.textContent = 'Last update: ' + formatDate(status.lastUpdate);
      dynamicRouteIds = status.dynamicRouteIds || [];

      if (status.hasTempPull) {
        // Temp files exist - resume the session by loading fresh data from disk
        setStatus('Resuming previous pull...');
        try {
          const resumed = await apiGet('/admin/api/routes/resume');
          freshData = resumed.freshData || {};
          const freshIds = resumed.routeIds || [];
          const stagedList = resumed.stagedRoutes || [];
          stagedPicks = {};
          // We don't know which featureIndex was staged, but we know the routeId was staged.
          // Read staged files to figure out which feature was picked.
          for (const sid of stagedList) {
            // Find matching feature index in fresh data
            if (freshData[sid]) {
              const stagedGeojson = await apiGet('/admin/api/routes/staged-pick/' + sid);
              if (stagedGeojson && stagedGeojson.features && stagedGeojson.features.length > 0) {
                const stagedCoords = JSON.stringify(stagedGeojson.features[0].geometry.coordinates);
                const idx = freshData[sid].features.findIndex(f =>
                  JSON.stringify(f.geometry.coordinates) === stagedCoords
                );
                if (idx !== -1) stagedPicks[sid] = idx;
              }
            }
          }

          const idSet = new Set([...dynamicRouteIds, ...freshIds]);
          allRouteIds = [...idSet].sort((a, b) => a - b);
          enterComparing();
          setStatus('Resumed previous pull. ' + freshIds.length + ' routes available.');
        } catch (err) {
          setStatus('Previous pull detected. Cancel to start fresh.');
          setButtons(false, false, true);
        }
      } else {
        setButtons(true, false, false);
      }
    } catch (err) {
      setStatus('Error loading status: ' + err.message);
    }
  }

  // --- Init ---
  btnPull.addEventListener('click', doPull);
  btnPublish.addEventListener('click', doPublish);
  btnCancel.addEventListener('click', doCancel);
  recoverState();
})();
