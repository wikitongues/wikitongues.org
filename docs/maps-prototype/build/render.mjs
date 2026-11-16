// Prototype of the wt-maps build step: one SVG per test language (and per
// disputed-border view), plus the projection parameters gdalwarp needs to
// reproject the relief raster into exactly the same frame.
import fs from 'node:fs';
import * as d3 from 'd3-geo';
import { choose, maxStretch } from './projection.mjs';

const S = process.argv[2];
const W = 400, H = 300, R_EARTH = 6371000;
const read = (p) => JSON.parse(fs.readFileSync(`${S}/${p}`, 'utf8'));
const layer = (n) => read(`ne/ne_50m_admin_0_${n}.geojson`).features;

const countries = layer('countries');
const units = layer('map_units');
const brk = layer('breakaway_disputed_areas');
const matches = read('data/matches.json');
const byTitle = Object.fromEntries(matches.map((m) => [m.title, m]));

const idx = {
  countries: Object.fromEntries(countries.map((f) => [f.properties.ADM0_A3, f])),
  units: Object.fromEntries(units.map((f) => [f.properties.GU_A3, f])),
  breakaway: Object.fromEntries(brk.map((f) => [f.properties.BRK_A3, f])),
};

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

const geomFor = (refs) => refs.map(([l, c]) => idx[l][c]).filter(Boolean);
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
  return parts.filter((p) => p === L || p.a >= 0.15 * L.a || d3.geoDistance(p.c, L.c) <= rL * 1.5 + 0.035).map((p) => p.f);
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

const THE = new Set(['United States', 'Russian Federation', 'Netherlands', 'Philippines', 'Bahamas', 'Gambia', 'Maldives', 'Marshall Islands', 'Solomon Islands', 'Cook Islands', 'Comoros', 'Seychelles', 'Democratic Republic of the Congo', 'Republic of the Congo', 'Central African Republic', 'Czech Republic', 'Dominican Republic', 'United Kingdom', 'United Arab Emirates', 'Sahrawi Arab Democratic Republic', 'Cape Verde Islands']);
const place = (t) => (THE.has(t) ? `the ${t}` : t);
const list = (a) => (a.length < 3 ? a.join(' and ') : `${a.slice(0, -1).join(', ')}, and ${a.at(-1)}`);

function render(lang, pov = 'defacto') {
  const prim = lang.primary.map((t) => byTitle[t]);
  const sec = lang.secondary.map((t) => byTitle[t]);
  const hl = [...prim.map((m) => ({ m, cls: 'primary' })), ...sec.map((m) => ({ m, cls: 'secondary' }))];

  // Frame on the main body of every highlighted territory.
  const focus = { type: 'FeatureCollection', features: hl.flatMap(({ m }) => geomFor(m.refs).flatMap(framingParts)) };
  const center = d3.geoCentroid(focus);
  let frame = focus;
  const MIN_R = (3.2 * Math.PI) / 180; // never zoom tighter than ~350 km radius
  if (maxRadius(focus, center) < MIN_R) frame = d3.geoCircle().center(center).radius(3.2)();

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
  const land = countries.filter((f) => !hlCountry.has(f.properties.ADM0_A3))
    .map((f) => pathEl(f, 'land', ` data-a3="${f.properties.ADM0_A3}"`)).join('');

  // Highlights
  const hiCodes = { primary: new Set(), secondary: new Set() };
  const markers = [];
  const highlight = hl.map(({ m, cls }) => {
    ownerCodes(m.refs).forEach((c) => hiCodes[cls].add(c));
    return geomFor(m.refs).map((f) => {
      const area = path.area(f);
      if (area < 40) { // too small to read as a shape: add a ring
        const c = proj(d3.geoCentroid(f));
        if (c) markers.push(`<circle class="marker ${cls}" cx="${c[0].toFixed(1)}" cy="${c[1].toFixed(1)}" r="7"/>`);
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
    return pathEl(d.f, `disputed ${cls}`, ` data-area="${d.name}"`);
  }).join('');

  const capPrim = list(lang.primary.map(place));
  const caption = `Spoken in ${capPrim}.` + (lang.secondary.length ? ` Also in ${list(lang.secondary.map(place))}.` : '');
  const desc = `Map centred on ${list(lang.primary.map(place))}${lang.secondary.length ? `, with ${list(lang.secondary.map(place))} also marked` : ''}.`;

  const id = `${lang.iso}-${pov}`;
  const svg = `<svg viewBox="0 0 ${W} ${H}" role="img" aria-labelledby="t-${id} d-${id}" class="wt-map__svg"><title id="t-${id}">Where ${lang.name} is spoken</title><desc id="d-${id}">${desc}</desc>`
    + `<rect class="sea" width="${W}" height="${H}"/>`
    + `<image class="seashade" href="sea/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<image class="relief" href="relief/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<g class="lands">${land}</g><g class="hls">${highlight}</g><g class="disputes">${disp}</g>`
    + `<image class="shade" href="shade/${lang.iso}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<g class="markers">${markers.join('')}</g></svg>`;

  // Projected extent in metres for gdalwarp (d3 flips y).
  const k = proj.scale(), [tx, ty] = proj.translate();
  const te = [(0 - tx) / k, (ty - H) / k, (W - tx) / k, ty / k].map((v) => v * R_EARTH);
  const proj4 = `+proj=laea +lat_0=${center[1]} +lon_0=${center[0]} +R=${R_EARTH} +units=m +no_defs`;
  return { id, iso: lang.iso, pov, svg, caption, bytes: Buffer.byteLength(svg), proj4, te, center, rotate: proj.rotate(), scale: k, translate: [tx, ty], spec, stretch: maxStretch(proj) };
}

const cases = read('data/cases.json');
const results = [];
for (const c of cases.gallery) results.push({ ...render(c, cases.pov), section: 'gallery', lang: c });
for (const c of cases.disputes) for (const pov of ['defacto', 'us', 'neutral']) results.push({ ...render(c, pov), section: 'disputes', lang: c });
fs.writeFileSync(`${S}/data/rendered.json`, JSON.stringify(results));
for (const r of results) console.log(r.id.padEnd(16), String(r.bytes).padStart(7), r.caption);
