// Prototype of the wt-maps build step: one SVG per test language (and per
// disputed-border view), plus the projection parameters gdalwarp needs to
// reproject the relief raster into exactly the same frame.
import fs from 'node:fs';
import * as d3 from 'd3-geo';
import { choose, maxStretch } from './projection.mjs';

const S = process.argv[2];
const W = 400, H = 300, R_EARTH = 6371000;
const read = (p) => JSON.parse(fs.readFileSync(`${S}/${p}`, 'utf8'));
const layer = (n, res = '50m') => read(`ne/ne_${res}_admin_0_${n}.geojson`).features;

const countries = layer('countries');
const units = layer('map_units');
const brk = layer('breakaway_disputed_areas');
// 1:10m outlines for zoomed-in maps (small territories); 1:50m is too coarse there.
// Disputed areas stay 1:50m: none falls inside a zoomed frame.
const countries10 = layer('countries', '10m');
const units10 = layer('map_units', '10m');
// Sub-units (Hawaii, Alaska, Wales, Scotland…): for a language-level map focus
// inside one territory, not for territories themselves.
const subunits = layer('map_subunits');
// 1:110m land, no borders: all the locator globe needs, and a fraction of the bytes.
const land110 = read('ne/ne_110m_land.geojson').features;
const subunits10 = layer('map_subunits', '10m');
const matches = read('data/matches.json');
const byTitle = Object.fromEntries(matches.map((m) => [m.title, m]));
// Territories proposed in the spec but not yet in WordPress (Guam, Northern Mariana Islands).
for (const [title, refs] of Object.entries(read('data/cases.json').proposed || {})) byTitle[title] = { title, refs, proposed: true };

const idx = {
  countries: Object.fromEntries(countries.map((f) => [f.properties.ADM0_A3, f])),
  units: Object.fromEntries(units.map((f) => [f.properties.GU_A3, f])),
  breakaway: Object.fromEntries(brk.map((f) => [f.properties.BRK_A3, f])),
  subunits: Object.fromEntries(subunits.map((f) => [f.properties.SU_A3, f])),
};
const idx10 = {
  countries: Object.fromEntries(countries10.map((f) => [f.properties.ADM0_A3, f])),
  units: Object.fromEntries(units10.map((f) => [f.properties.GU_A3, f])),
  breakaway: idx.breakaway,
  subunits: Object.fromEntries(subunits10.map((f) => [f.properties.SU_A3, f])),
};
// Frames tighter than this radius draw from 1:10m.
const FINE_BELOW_DEG = 6;

// Disputed overlays: every NE breakaway area, plus the map units that are
// themselves contested states/areas.
const DISPUTED_UNITS = ['KOS', 'SOL', 'CYN', 'GAZ', 'WEB', 'SAH', 'TWN'];
const POV_KEYS = Object.keys(brk[0].properties).filter((k) => /^ADM0_A3(_[A-Z]{2})?$/.test(k));
const disputed = [
  ...brk.map((f) => ({ code: f.properties.BRK_A3, name: f.properties.BRK_NAME, f })),
  ...DISPUTED_UNITS.map((c) => ({ code: c, name: idx.units[c].properties.NAME, f: idx.units[c] })),
].map((d) => {
  const p = d.f.properties;
  const claimants = new Set(POV_KEYS.map((k) => p[k]).filter((v) => typeof v === 'string' && /^[A-Z]{3}$/.test(v) && !/^[BC]\d\d$/.test(v)));
  return { ...d, defacto: p.ADM0_A3, us: p.ADM0_A3_US, claimants, note: p.NOTE_BRK };
});

const geomFor = (refs, ix = idx) => refs.map(([l, c]) => ix[l][c] || idx[l][c]).filter(Boolean);
// Codes a highlighted territory answers to when an overlay names an owner.
const ownerCodes = (refs) => new Set(geomFor(refs).flatMap((f) => [f.properties.ADM0_A3, f.properties.GU_A3, f.properties.BRK_A3, f.properties.SOV_A3]).filter(Boolean));

// Split into polygons, keep the main body and anything big or close to it.
function framingParts(feature) {
  const g = feature.geometry;
  const polys = g.type === 'Polygon' ? [g.coordinates] : g.coordinates;
  const parts = polys.map((c) => ({ type: 'Feature', geometry: { type: 'Polygon', coordinates: c } }))
    .map((f) => ({ f, a: d3.geoArea(f), c: d3.geoCentroid(f) }))
    .sort((x, y) => y.a - x.a);
  const L = parts[0];
  const rL = Math.sqrt(L.a / Math.PI); // angular radius of an equal-area disc
  // A scattered archipelago (Tuvalu): no island is big enough to read as a shape,
  // so frame on all of them, not on whichever atoll is largest.
  if (rL < (0.1 * Math.PI) / 180) return parts.map((p) => p.f);
  // Grow from the main body: a part joins if it is big (≥15% of it) or near any kept
  // part. Only parts of at least 1% can extend the chain further, so an island chain
  // (Hawaiʻi: Big Island → Maui → Oʻahu → Kauaʻi → Niʻihau) stays whole while specks
  // far out (the Northwestern Hawaiian Islands) don't widen the frame.
  const reach = rL * 1.5 + 0.035;
  const kept = [L], seeds = [L];
  let grew = true;
  while (grew) {
    grew = false;
    for (const p of parts) {
      if (kept.includes(p)) continue;
      // Specks join only when near the main body itself, never by way of the chain.
      const near = (q) => d3.geoDistance(p.c, q.c) <= reach;
      if (p.a >= 0.15 * L.a || near(L) || (p.a >= 0.01 * L.a && seeds.some(near))) {
        kept.push(p); grew = true;
        if (p.a >= 0.01 * L.a) seeds.push(p);
      }
    }
  }
  return kept.map((p) => p.f);
}

// Douglas–Peucker on projected points (iterative).
function dp(pts, eps) {
  const n = pts.length; if (n < 4) return pts;
  const keep = new Uint8Array(n); keep[0] = keep[n - 1] = 1;
  const stack = [[0, n - 1]];
  while (stack.length) {
    const [a, b] = stack.pop();
    const [ax, ay] = pts[a], [bx, by] = pts[b];
    const dx = bx - ax, dy = by - ay, len = Math.hypot(dx, dy) || 1e-9;
    let md = -1, mi = -1;
    for (let i = a + 1; i < b; i++) {
      const d = len > 1e-9 ? Math.abs(dy * pts[i][0] - dx * pts[i][1] + bx * ay - by * ax) / len : Math.hypot(pts[i][0] - ax, pts[i][1] - ay);
      if (d > md) { md = d; mi = i; }
    }
    if (md > eps) { keep[mi] = 1; stack.push([a, mi], [mi, b]); }
  }
  return pts.filter((_, i) => keep[i]);
}

function maxRadius(fc, center) {
  let m = 0;
  d3.geoStream(fc, { point(x, y) { m = Math.max(m, d3.geoDistance(center, [x, y])); }, lineStart() {}, lineEnd() {}, polygonStart() {}, polygonEnd() {}, sphere() {} });
  return m;
}

const THE = new Set(['United States', 'Russian Federation', 'Netherlands', 'Philippines', 'Bahamas', 'Gambia', 'Maldives', 'Marshall Islands', 'Solomon Islands', 'Cook Islands', 'Comoros', 'Seychelles', 'Democratic Republic of the Congo', 'Republic of the Congo', 'Central African Republic', 'Czech Republic', 'Dominican Republic', 'United Kingdom', 'United Arab Emirates', 'Sahrawi Arab Democratic Republic', 'Cape Verde Islands', 'Northern Mariana Islands']);
const place = (t) => (THE.has(t) ? `the ${t}` : t);
const list = (a) => (a.length < 3 ? a.join(' and ') : `${a.slice(0, -1).join(', ')}, and ${a.at(-1)}`);

function render(lang, pov = 'defacto') {
  // A language-level map focus swaps a territory's shape for a part of it
  // (Hawaiian: the United States, drawn as Hawaii). The territory link is unchanged.
  const focus_ = lang.focus || {};
  const pick = (t) => (focus_[t] ? { ...byTitle[t], refs: focus_[t].refs, focusLabel: focus_[t].label } : byTitle[t]);
  const prim = lang.primary.map(pick);
  const sec = lang.secondary.map(pick);
  const hl = [...prim.map((m) => ({ m, cls: 'primary' })), ...sec.map((m) => ({ m, cls: 'secondary' }))];

  // Contested areas this language's territories claim (neutral view). They get the
  // same treatment as the territory itself: always inside the frame, and revealed by
  // the fog. Leaving one out (Crimea fogged on a Ukrainian page) would be a statement.
  // The rule is symmetric: a Russian page frames and reveals Crimea too, hatched.
  const claimCodes = new Set(hl.flatMap(({ m }) => [...ownerCodes(m.refs)]));
  const isSelf = (d) => hl.some(({ m }) => m.refs.some(([l, c]) => c === d.code || (l !== 'countries' && c === d.defacto)));
  const claimed = pov === 'neutral' ? disputed.filter((d) => !isSelf(d) && [...d.claimants].some((c) => claimCodes.has(c))) : [];
  const claimedSet = new Set(claimed);

  // Frame on the main body of every highlighted territory, plus the areas it claims.
  // Framed on 1:10m outlines: 1:50m drops most small islands (it has one of Tuvalu's nine atolls).
  const focus = { type: 'FeatureCollection', features: [
    ...hl.flatMap(({ m }) => geomFor(m.refs, idx10).flatMap(framingParts)),
    ...claimed.map((d) => d.f),
  ] };
  const center = d3.geoCentroid(focus);
  let frame = focus;
  // Minimum frame radius ~45 km: small countries zoom until they read as a shape
  // (Malta and Gozo take about a quarter of the width). Neighbours drop out of
  // view at this zoom; the scale bar says how small the place is.
  // Scattered islands (Tuvalu) still frame on their spread.
  const MIN_DEG = 0.4;
  const focusDeg = (maxRadius(focus, center) * 180) / Math.PI;
  if (focusDeg < MIN_DEG) frame = d3.geoCircle().center(center).radius(MIN_DEG)();
  const fine = Math.max(focusDeg, MIN_DEG) < FINE_BELOW_DEG;
  const ix = fine ? idx10 : idx;
  const base = fine ? countries10 : countries;

  const { proj, spec } = choose(frame, center);
  proj.clipExtent([[0, 0], [W, H]]);
  // Drop points closer than TOL px to the last kept one: 1:50m detail is far
  // finer than a sidebar map can show.
  const TOL = 0.3, MIN_RING = 1.5, EPS = 0.6;
  const thin = (sink) => {
    let lx, ly, first, buf;
    return {
      point(x, y) {
        if (!buf) return sink.point(x, y);
        if (first === undefined) { first = [x, y]; lx = x; ly = y; buf.push([x, y]); return; }
        if (Math.hypot(x - lx, y - ly) >= TOL) { buf.push([x, y]); lx = x; ly = y; }
      },
      lineStart() { buf = []; first = undefined; },
      lineEnd() {
        const b = buf; buf = null;
        const xs = b.map((p) => p[0]), ys = b.map((p) => p[1]);
        const span = b.length ? Math.max(Math.max(...xs) - Math.min(...xs), Math.max(...ys) - Math.min(...ys)) : 0;
        if (b.length < 3 || span < MIN_RING) return; // too small to see
        const s = dp(b, EPS);
        if (s.length < 3) return;
        sink.lineStart(); s.forEach(([x, y]) => sink.point(x, y)); sink.lineEnd();
      },
      polygonStart() { sink.polygonStart(); }, polygonEnd() { sink.polygonEnd(); },
      sphere() { sink.sphere && sink.sphere(); },
    };
  };
  const path = d3.geoPath({ stream: (s) => proj.stream(thin(s)) }).digits(1);

  const out = [];
  const pathEl = (f, cls, extra = '') => { const d = path(f); return d ? `<path class="${cls}" d="${d}"${extra}/>` : ''; };

  // Base: every country, de facto (NE default).
  // (A highlighted whole country is drawn once, in the highlight layer.)
  const hlCountry = new Set(hl.flatMap(({ m }) => m.refs.filter(([l]) => l === 'countries').map(([, c]) => c)));
  const land = base.filter((f) => !hlCountry.has(f.properties.ADM0_A3))
    .map((f) => pathEl(f, 'land', ` data-a3="${f.properties.ADM0_A3}"`)).join('');

  // Highlights
  const hiCodes = { primary: new Set(), secondary: new Set() };
  const markers = [];
  const highlight = hl.map(({ m, cls }) => {
    ownerCodes(m.refs).forEach((c) => hiCodes[cls].add(c));
    return geomFor(m.refs, ix).map((f) => {
      const area = path.area(f);
      if (area < 40) { // too small to read as a shape: add a ring
        // One ring per island group, so a scattered archipelago (Tuvalu) shows its
        // spread instead of one ring in empty sea. Groups closer than 12 units merge.
        const g = f.geometry, polys = g.type === 'Polygon' ? [g.coordinates] : g.coordinates;
        const pts = [];
        for (const c of polys) {
          const p = proj(d3.geoCentroid({ type: 'Polygon', coordinates: c }));
          if (p && p[0] >= 0 && p[0] <= W && p[1] >= 0 && p[1] <= H && !pts.some((q) => Math.hypot(q[0] - p[0], q[1] - p[1]) < 12)) pts.push(p);
        }
        const r = pts.length > 1 ? 4.5 : 7;
        for (const [x, y] of pts) markers.push(`<circle class="marker ${cls}" cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${r}"/>`);
      }
      return pathEl(f, `hl ${cls}`, ` data-territory="${m.title}"`);
    }).join('');
  }).join('');

  // Disputed overlays, coloured by the chosen view.
  const hiCls = (code) => (hiCodes.primary.has(code) ? 'primary' : hiCodes.secondary.has(code) ? 'secondary' : null);
  // A disputed area is the territory itself when it shares the territory's code,
  // or when its de facto owner is a highlighted map unit or breakaway region
  // (Natural Earth stores Somaliland both ways). Never via a whole country:
  // that would hand Crimea to Russia on a Russian page.
  const selfHi = (d) => hl.find(({ m }) => m.refs.some(([l, c]) => c === d.code || (l !== 'countries' && c === d.defacto)));
  // The fog never cuts through a contested area: any one that comes within the reveal
  // (about 30 units of a territory or a claimed area) is revealed whole, in its own
  // neutral hatch (the West Bank and Gaza on a Hebrew page, Transnistria on a Ukrainian one).
  const REACH = 30, cell = REACH;
  const grid = new Map();
  const ptsOf = (f) => { const out = []; d3.geoStream(f, proj.stream({ point(x, y) { out.push([x, y]); }, lineStart() {}, lineEnd() {}, polygonStart() {}, polygonEnd() {}, sphere() {} })); return out; };
  for (const f of [...hl.flatMap(({ m }) => geomFor(m.refs, ix)), ...claimed.map((d) => d.f)]) {
    for (const [x, y] of ptsOf(f)) { const k = `${Math.floor(x / cell)},${Math.floor(y / cell)}`; if (!grid.has(k)) grid.set(k, []); grid.get(k).push([x, y]); }
  }
  const nearReveal = (f) => ptsOf(f).some(([x, y]) => {
    if (x < -REACH || y < -REACH || x > W + REACH || y > H + REACH) return false;
    const cx = Math.floor(x / cell), cy = Math.floor(y / cell);
    for (let i = -1; i <= 1; i++) for (let j = -1; j <= 1; j++) for (const [px, py] of grid.get(`${cx + i},${cy + j}`) || []) if (Math.hypot(px - x, py - y) <= REACH) return true;
    return false;
  });
  const touched = [];
  const edges = [], claims = [];
  const disp = disputed.map((d) => {
    const own = selfHi(d);
    let cls;
    // Already filled in the highlight layer; here it only gets its dashed edge.
    if (own) cls = 'edge';
    else if (pov === 'neutral') {
      const claim = [...d.claimants].map(hiCls).find(Boolean);
      cls = claim ? `hatch hatch-${claim}` : 'hatch hatch-land';
    } else {
      const owner = pov === 'us' ? d.us : d.defacto;
      cls = hiCls(owner) ? `hl ${hiCls(owner)}` : 'land';
    }
    const el = pathEl(d.f, `disputed ${cls}`, ` data-area="${d.name}"`);
    const dd = el.match(/ d="([^"]+)"/);
    if (dd) edges.push(`<path d="${dd[1]}"/>`);
    if (claimedSet.has(d) || (pov === 'neutral' && !isSelf(d) && el && nearReveal(d.f))) {
      if (!claimedSet.has(d)) touched.push(d.name);
      claims.push(el); return '';
    }
    return el;
  }).join('');

  const placeOf = (t) => (focus_[t] ? `${focus_[t].label} in ${place(t)}` : place(t));
  const capPrim = list(lang.primary.map(placeOf));
  const caption = `Spoken in ${capPrim}.` + (lang.secondary.length ? ` Also in ${list(lang.secondary.map(placeOf))}.` : '');
  const desc = `Map centred on ${list(lang.primary.map(placeOf))}${lang.secondary.length ? `, with ${list(lang.secondary.map(placeOf))} also marked` : ''}.`;

  const id = `${lang.iso}-${pov}`;

  // Abstract scale bar: one round length, true at the centre of the map (an
  // equal-area projection keeps area, not distance, so it is a guide, not a ruler).
  const c0 = proj.invert([W / 2, H / 2]), c1 = proj.invert([W / 2 + 1, H / 2]);
  const kmPerUnit = d3.geoDistance(c0, c1) * (R_EARTH / 1000);
  const nice = [1, 2, 5].flatMap((m) => [1, 10, 100, 1000, 10000].map((e) => m * e)).sort((a, b) => a - b)
    .filter((km) => km / kmPerUnit <= 80).at(-1);
  const len = nice / kmPerUnit;
  // Hidden at continent scale (OS: scale varies there, so a bar misleads):
  // when shapes stretch more than 5% anywhere in the frame.
  const stretch = maxStretch(proj);
  const scale = stretch > 5 ? '' : `<g class="scale" aria-hidden="true"><path class="scale-bar" d="M14 286H${(14 + len).toFixed(1)}"/>`
    + `<text class="scale-label" x="${(20 + len).toFixed(1)}" y="290">${nice.toLocaleString('en')} km</text></g>`;

  // Locator globe (OS: a locator map shows the area within its wider region). Only on
  // small frames, where the map alone can't say where on Earth it is; a continent-sized
  // map is its own context. An orthographic globe, ~50px across at a 300px sidebar,
  // centred on the map, with a ring on the spot. It goes in whichever top or bottom-right
  // corner the language's territories and claims crowd least; the scale bar has bottom-left.
  let inset = '';
  // Threshold 30°: includes Morocco (17.6°), Somalia (15.9°), Peru (20.8°) and Greenland
  // (27°), not India (35°), China or Russia. Aimed at readers who don't know where a
  // mid-sized country is; only continent-sized frames are their own context.
  if (spec.cornerDeg < 30) {
    const GR = 34, PADI = 10;
    const corners = [[W - PADI - GR, PADI + GR], [PADI + GR, PADI + GR], [W - PADI - GR, H - PADI - GR]];
    const busy = [...hl.flatMap(({ m }) => geomFor(m.refs, ix)), ...claimed.map((d) => d.f)].flatMap(ptsOf);
    const crowd = ([cx, cy]) => busy.filter(([x, y]) => Math.hypot(x - cx, y - cy) < GR + 6).length;
    const [gx, gy] = corners.reduce((a, b) => (crowd(b) < crowd(a) ? b : a));
    const globe = d3.geoOrthographic().rotate([-center[0], -center[1]]).scale(GR).translate([gx, gy]).clipAngle(90);
    const gpath = d3.geoPath({ stream: (st) => globe.stream(thin(st)) }).digits(1);
    const gland = land110.map((f) => gpath(f)).filter(Boolean).join('');
    const [mx, my] = globe(center);
    // Mark: a ring on small frames; on larger ones, the map's own view outlined on the
    // globe (OS: mark an inset's area on the locator), once that outline outgrows the ring.
    const view = [];
    for (let i = 0; i <= 16; i++) view.push([(i / 16) * W, 0]);
    for (let i = 1; i <= 12; i++) view.push([W, (i / 12) * H]);
    for (let i = 15; i >= 0; i--) view.push([(i / 16) * W, H]);
    for (let i = 11; i >= 1; i--) view.push([0, (i / 12) * H]);
    const vpts = view.map((p) => proj.invert(p)).filter(Boolean).map((ll) => globe(ll)).filter(Boolean);
    const span = vpts.length ? Math.max(...vpts.map((p) => Math.hypot(p[0] - mx, p[1] - my))) : 0;
    const mark = span > 6
      ? `<path class="globe-view" d="M${vpts.map((p) => p.map((v) => v.toFixed(1)).join(' ')).join('L')}Z"/>`
      : `<circle class="globe-mark" cx="${mx.toFixed(1)}" cy="${my.toFixed(1)}" r="4"/>`;
    inset = `<g class="inset" aria-hidden="true"><circle class="globe-halo" cx="${gx.toFixed(1)}" cy="${gy.toFixed(1)}" r="${GR + 1.5}"/>`
      + `<circle class="globe-sea" cx="${gx.toFixed(1)}" cy="${gy.toFixed(1)}" r="${GR}"/><path class="globe-land" d="${gland}"/>`
      + `${mark}<circle class="globe-rim" cx="${gx.toFixed(1)}" cy="${gy.toFixed(1)}" r="${GR}"/></g>`;
  }
  const svg = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-labelledby="t-${id} d-${id}" class="wt-map__svg"><title id="t-${id}">Where ${lang.name} is spoken</title><desc id="d-${id}">${desc}</desc>`
    // Fog-of-war mask: the highlighted shapes (and their rings), grown and blurred by
    // the shared #fog filter. Only the fog style applies it, through --fog.
    + `<defs><mask id="fog-${id}" mask-type="alpha" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="${W}" height="${H}"><g mask="url(#fog-edge)"><g filter="url(#fog)"><use href="#hls-${id}"/><use href="#cl-${id}"/><use href="#mk-${id}"/></g></g></mask>`
    // The coloured relief as a fill, for the Fog relief style's territories.
    + `<pattern id="rp-${id}" patternUnits="userSpaceOnUse" width="${W}" height="${H}"><image href="relief/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/></pattern></defs>`
    + `<g class="world" style="--fog:url(#fog-${id});--relief:url(#rp-${id})">`
    + `<rect class="sea" width="${W}" height="${H}"/>`
    + `<image class="seashade" href="sea/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<image class="relief" href="relief/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<g class="lands">${land}</g><g class="hls" id="hls-${id}">${highlight}</g><g class="disputes">${disp}</g><g class="claims" id="cl-${id}">${claims.join('')}</g>`
    // Contested edges: a parchment casing under dark dashes, legible on sea, land and parchment alike
    // (gzip makes the repeated paths nearly free).
    + `<g class="dispute-casing">${edges.join('')}</g><g class="dispute-line">${edges.join('')}</g>`
    + `<image class="shade" href="shade/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<g class="markers" id="mk-${id}">${markers.join('')}</g></g>${scale}${inset}</svg>`;

  // Projected extent in metres for gdalwarp (d3 flips y).
  const k = proj.scale(), [tx, ty] = proj.translate();
  const te = [(0 - tx) / k, (ty - H) / k, (W - tx) / k, ty / k].map((v) => v * R_EARTH);
  const proj4 = `+proj=laea +lat_0=${center[1]} +lon_0=${center[0]} +R=${R_EARTH} +units=m +no_defs`;
  return { id, iso: lang.iso, pov, svg, claimed: claimed.map((d) => d.name), touched, fine, scaleKm: nice, caption, bytes: Buffer.byteLength(svg), proj4, te, center, rotate: proj.rotate(), scale: k, translate: [tx, ty], spec, stretch };
}

const cases = read('data/cases.json');
const results = [];
for (const c of cases.gallery) results.push({ ...render(c, cases.pov), section: 'gallery', lang: c });
// Neutral only (decided 2026-11-17); the de facto and US views stay in render() for comparison runs.
for (const c of cases.disputes) results.push({ ...render(c, 'neutral'), section: 'disputes', lang: c });
fs.writeFileSync(`${S}/data/rendered.json`, JSON.stringify(results));
for (const r of results) console.log(r.id.padEnd(16), String(r.bytes).padStart(7), r.caption);
