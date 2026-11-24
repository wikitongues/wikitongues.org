// Render one SVG per map set, from build/cache/sets.json (`wp wt-maps export`).
//
//   node render.mjs [--only=key1,key2]
//
// Writes cache/stage/<key>.svg and the set's frame in cache/stage/frames.json:
// its places, its projection (the raster steps reproject into exactly this
// frame), the contested areas it claims or reveals, and its size. mask.mjs
// publishes the SVG to maps/ once its images exist.
//
// One SVG serves every language with that set of places, so it carries no
// primary/secondary styling. Each highlighted shape has `data-place` (the
// place's record ID) and each claimed contested area `data-claimants` (the
// record IDs of the places claiming it); PHP adds the classes per language.
// Image hrefs are relative to maps/ (`shade/<key>.webp`); PHP makes them absolute.
//
// Ported from the prototype (docs/maps-prototype/build/render.mjs, PR #669),
// with the neutral view only (decided 2026-11-17) and no caption.
import fs from 'node:fs';
import path from 'node:path';
import zlib from 'node:zlib';
import * as d3 from 'd3-geo';
import { choose, maxStretch, W, H } from './projection.mjs';
import { CACHE, STAGE, FRAMES, readJson, writeJson, readFrames, onlyKeys } from './common.mjs';
import { claimResolver } from './claims.mjs';

const R_EARTH = 6371000;
const layer = (n, res = '50m') => readJson(path.join(CACHE, 'ne', `ne_${res}_admin_0_${n}.geojson`)).features;

const countries = layer('countries');
const units = layer('map_units');
const brk = layer('breakaway_disputed_areas');
// 1:10m outlines for zoomed-in maps (small territories); 1:50m is too coarse there.
// Disputed areas stay 1:50m: none falls inside a zoomed frame.
const countries10 = layer('countries', '10m');
const units10 = layer('map_units', '10m');
const subunits = layer('map_subunits');
const subunits10 = layer('map_subunits', '10m');
// 1:110m land, no borders: all the locator globe needs, and a fraction of the bytes.
const land110 = readJson(path.join(CACHE, 'ne', 'ne_110m_land.geojson')).features;

const input = readJson(path.join(CACHE, 'sets.json'));

const byCode = (fs_, key) => Object.fromEntries(fs_.map((f) => [f.properties[key], f]));
const idx = {
  countries: byCode(countries, 'ADM0_A3'),
  units: byCode(units, 'GU_A3'),
  breakaway: byCode(brk, 'BRK_A3'),
  subunits: byCode(subunits, 'SU_A3'),
};
const idx10 = {
  countries: byCode(countries10, 'ADM0_A3'),
  units: byCode(units10, 'GU_A3'),
  breakaway: idx.breakaway,
  subunits: byCode(subunits10, 'SU_A3'),
};
// Frames tighter than this radius draw from 1:10m.
const FINE_BELOW_DEG = 6;

// Disputed overlays: every NE breakaway area, plus the map units that are
// themselves contested states or areas.
const DISPUTED_UNITS = ['KOS', 'SOL', 'CYN', 'GAZ', 'WEB', 'SAH', 'TWN'];
const POV_KEYS = Object.keys(brk[0].properties).filter((k) => /^ADM0_A3(_[A-Z]{2})?$/.test(k));
const disputed = [
  ...brk.map((f) => ({ code: f.properties.BRK_A3, name: f.properties.BRK_NAME, f })),
  ...DISPUTED_UNITS.map((c) => ({ code: c, name: idx.units[c].properties.NAME, f: idx.units[c] })),
].map((d) => {
  const p = d.f.properties;
  const claimants = new Set(POV_KEYS.map((k) => p[k]).filter((v) => typeof v === 'string' && /^[A-Z]{3}$/.test(v) && !/^[BC]\d\d$/.test(v)));
  return { ...d, defacto: p.ADM0_A3, claimants };
});

const refsOf = (placeId) => {
  const place = input.places[placeId];
  if (!place) throw new Error(`Place ${placeId} is not in sets.json`);
  return place.shapes.map((s) => s.split(':'));
};
const geomFor = (refs, ix = idx) => refs.map(([l, c]) => {
  const f = ix[l][c] || idx[l][c];
  if (!f) throw new Error(`Natural Earth has no ${l}:${c}`);
  return f;
});
// Whether a highlighted place claims a contested area (claims.mjs): a country's
// claim belongs to its part nearest the area.
const placeClaims = claimResolver(subunits);

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

const esc = (s) => String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
const list = (a) => (a.length < 3 ? a.join(' and ') : `${a.slice(0, -1).join(', ')}, and ${a.at(-1)}`);

// Area of the convex hull of points (monotone chain), in square units.
function hullArea(pts) {
  const p = [...pts].sort((a, b) => a[0] - b[0] || a[1] - b[1]);
  if (p.length < 3) return 0;
  const cross = (o, a, b) => (a[0] - o[0]) * (b[1] - o[1]) - (a[1] - o[1]) * (b[0] - o[0]);
  const half = (q) => { const h = []; for (const x of q) { while (h.length > 1 && cross(h[h.length - 2], h[h.length - 1], x) <= 0) h.pop(); h.push(x); } h.pop(); return h; };
  const h = [...half(p), ...half([...p].reverse())];
  let a = 0;
  for (let i = 0; i < h.length; i++) { const [x1, y1] = h[i], [x2, y2] = h[(i + 1) % h.length]; a += x1 * y2 - x2 * y1; }
  return Math.abs(a) / 2;
}

function render(key, placeIds) {
  const hl = placeIds.map((id) => ({ id, refs: refsOf(id), name: input.places[id].name }));

  // Contested areas these places claim. They are treated like the place itself:
  // always inside the frame, and revealed by the fog. Leaving one out (Crimea fogged
  // on a Ukrainian page) would be a statement. The rule is symmetric: a Russian page
  // frames and reveals Crimea too, hatched.
  const isSelf = (d) => hl.some(({ refs }) => refs.some(([l, c]) => c === d.code || (l !== 'countries' && c === d.defacto)));
  const claimed = disputed.filter((d) => !isSelf(d) && hl.some(({ refs }) => placeClaims(refs, d)));
  const claimedSet = new Set(claimed);

  // Frame on the main body of every highlighted place, plus the areas it claims.
  // Framed on 1:10m outlines: 1:50m drops most small islands (it has one of Tuvalu's nine atolls).
  const focus = { type: 'FeatureCollection', features: [
    ...hl.flatMap(({ refs }) => geomFor(refs, idx10).flatMap(framingParts)),
    ...claimed.map((d) => d.f),
  ] };
  // A set may adjust its frame (`frame` in sets.json, continent maps): `center`
  // fixes the view's centre ([lon, lat]); `radius` (degrees) frames a circle
  // round it instead of fitting the places, which still fit with equal margins
  // without it; `roll` turns the view (degrees, positive is clockwise); `crop`
  // ([x0, y0, x1, y1], fractions of the frame) keeps part of the fitted view;
  // `include` ([[lon, lat], …]) adds points to the fit, for outlying parts the
  // frame leaves out (Spain's Canary Islands on Europe's map).
  const opts = input.sets[key] ?? {};
  const fopt = opts.frame ?? {};
  if (fopt.include) focus.features.push(...fopt.include.map((c) => ({ type: 'Feature', properties: {}, geometry: { type: 'Point', coordinates: c } })));
  const center = fopt.center ?? d3.geoCentroid(focus);
  let frame = fopt.radius ? d3.geoCircle().center(center).radius(fopt.radius)() : focus;
  // Minimum frame radius ~45 km: small countries zoom until they read as a shape.
  const MIN_DEG = 0.4;
  const focusDeg = (maxRadius(focus, center) * 180) / Math.PI;
  if (focusDeg < MIN_DEG) frame = d3.geoCircle().center(center).radius(MIN_DEG)();
  const fine = Math.max(focusDeg, MIN_DEG) < FINE_BELOW_DEG;
  const ix = fine ? idx10 : idx;
  const base = fine ? countries10 : countries;

  const { proj, spec } = choose(frame, center, fopt.roll ?? 0);
  if (fopt.crop) {
    // Scale the kept rectangle up to the whole frame: screen' = (screen - corner) × z.
    const [x0, y0, x1] = fopt.crop, z = 1 / (x1 - x0), [tx, ty] = proj.translate();
    proj.scale(proj.scale() * z).translate([(tx - x0 * W) * z, (ty - y0 * H) * z]);
    spec.scale = proj.scale(); spec.translate = proj.translate();
  }
  proj.clipExtent([[0, 0], [W, H]]);
  // Drop points closer than TOL px to the last kept one, rings under MIN_RING px,
  // then Douglas–Peucker at EPS px: sidebar resolution.
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
        if (b.length < 3 || span < MIN_RING) return;
        const s = dp(b, EPS);
        if (s.length < 3) return;
        sink.lineStart(); s.forEach(([x, y]) => sink.point(x, y)); sink.lineEnd();
      },
      polygonStart() { sink.polygonStart(); }, polygonEnd() { sink.polygonEnd(); },
      sphere() { sink.sphere && sink.sphere(); },
    };
  };
  const path_ = d3.geoPath({ stream: (s) => proj.stream(thin(s)) }).digits(1);
  const pathEl = (f, cls, extra = '') => { const d = path_(f); return d ? `<path class="${cls}" d="${d}"${extra}/>` : ''; };

  // Base: every country, de facto. A country that is itself highlighted (Peru, or
  // Greenland drawn from its map unit) is drawn once, in the highlight layer.
  const hlCodes = new Set(hl.flatMap(({ refs }) => refs.map(([, c]) => c)));
  const land = base.filter((f) => !hlCodes.has(f.properties.ADM0_A3))
    .map((f) => pathEl(f, 'land', ` data-a3="${f.properties.ADM0_A3}"`)).join('');

  const markers = [];
  const set = input.sets[key] ?? {};
  // Each highlighted feature: its place, whether it is too small to read as a
  // shape (its largest island under 150 square units, some 6 px across at the
  // sidebar's 192 px width), its parts' centres and its area.
  const feats = hl.map(({ id, refs }) => geomFor(refs, ix).map((f) => {
    const g = f.geometry, polys = g.type === 'Polygon' ? [g.coordinates] : g.coordinates;
    return {
      id, f,
      small: Math.max(...polys.map((c) => path_.area({ type: 'Polygon', coordinates: c }))) < 150,
      centroids: polys.map((c) => proj(d3.geoCentroid({ type: 'Polygon', coordinates: c })))
        .filter((p) => p && p[0] >= -50 && p[0] <= W + 50 && p[1] >= -50 && p[1] <= H + 50),
      area: path_.area(f),
    };
  }));
  // A set with `groups` (place → group number in sets.json: a continent's
  // sub-regions) is drawn as groups: each outlined as one shape, no rings, every
  // small place a dot, and island groups in a halo (plan 3.2, step 1c). A set
  // with `halo` (a sub-region map) whose land is under 15% of the area its places
  // span is drawn as one island group, with its borders kept and no outline: the
  // Caribbean, Micronesia, Melanesia and Polynesia.
  let groups = set.groups ?? null;
  const outlined = groups !== null;
  if (!groups && set.halo) {
    const all = feats.flat(), span = hullArea(all.flatMap((x) => x.centroids));
    if (span > 0 && all.reduce((t, x) => t + x.area, 0) < 0.15 * span) groups = Object.fromEntries(hl.map(({ id }) => [id, 1]));
  }
  // `rings: detached` (sub-region maps): a small place that touches another place
  // on the map is part of the main body and needs no ring (Moldova and
  // Transnistria in Eastern Europe). Touching: within 1.5 units.
  const project = (f) => { const out = []; d3.geoStream(f, proj.stream({ point(x, y) { out.push([x, y]); }, lineStart() {}, lineEnd() {}, polygonStart() {}, polygonEnd() {}, sphere() {} })); return out; };
  const detachedOnly = set.rings === 'detached' && !groups;
  const vertsBy = detachedOnly ? feats.map((list) => list.flatMap((x) => project(x.f))) : [];
  const touches = (i, f) => {
    const mine = project(f);
    return vertsBy.some((verts, j) => j !== i && verts.some(([x, y]) => mine.some(([u, v]) => Math.abs(u - x) < 1.5 && Math.abs(v - y) < 1.5 && Math.hypot(u - x, v - y) < 1.5)));
  };
  const dotsBy = {}, ptsBy = {}, areaBy = {};
  const hlParts = feats.map((list, i) => list.map(({ id, f, small, centroids, area }) => {
    if (groups) {
      areaBy[id] = (areaBy[id] ?? 0) + area;
      (ptsBy[id] ??= []).push(...centroids);
      if (small) {
        // A dot per island, one per 3 units: a ring at continent scale is clutter.
        const dots = [];
        for (const p of centroids) if (p[0] >= 0 && p[0] <= W && p[1] >= 0 && p[1] <= H && !dots.some((q) => Math.hypot(q[0] - p[0], q[1] - p[1]) < 3)) dots.push(p);
        dotsBy[id] = (dotsBy[id] ?? '') + dots.map(([x, y]) => `<circle class="dot" data-place="${id}" cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="1"/>`).join('');
      }
    } else if (small && !(detachedOnly && touches(i, f))) {
      // Too small to read as a shape: add rings. Guam (43) had none, so the
      // Chamorro and Carolinian maps looked the same. Judged by the largest
      // island, not the total, so a chain of specks (the Northern Mariana
      // Islands: twelve islands, none over 15 units) is ringed too.
      // One ring per island group, so a scattered archipelago (Tuvalu) shows its
      // spread instead of one ring in empty sea. Groups closer than 12 units merge.
      const pts = [];
      for (const p of centroids) if (p[0] >= 0 && p[0] <= W && p[1] >= 0 && p[1] <= H && !pts.some((q) => Math.hypot(q[0] - p[0], q[1] - p[1]) < 12)) pts.push(p);
      const r = pts.length > 1 ? 4.5 : 7;
      for (const [x, y] of pts) markers.push(`<circle class="marker" data-place="${id}" cx="${x.toFixed(1)}" cy="${y.toFixed(1)}" r="${r}"/>`);
    }
    return pathEl(f, 'hl', ` data-place="${id}"`);
  }).join(''));

  let highlight = hlParts.join(''), groupDefs = '', groupHalos = '', groupEdges = '';
  if (groups) {
    const nums = [...new Set(Object.values(groups))].sort((a, b) => a - b);
    const members = (n) => hl.filter(({ id }) => groups[id] === n);
    // Each group's shapes in one <g>, so a filter can outline their union and the
    // borders inside a sub-region can be hidden.
    highlight = nums.map((n) => `<g${outlined ? ` class="grp grp-${n}"` : ''} id="grp-${n}-${key}">${hl.map(({ id }, i) => (groups[id] === n ? hlParts[i] : '')).join('')}</g>`).join('')
      + hl.map(({ id }, i) => (groups[id] === undefined ? hlParts[i] : '')).join('')
      + `<g class="dots">${nums.map((n) => `<use href="#dots-${n}-${key}"/>`).join('')}</g>`;
    groupDefs = nums.map((n) => `<g id="dots-${n}-${key}">${members(n).map(({ id }) => dotsBy[id] ?? '').join('')}</g>`).join('')
      // The outline: the group grown a unit, less the group.
      + `<filter id="ge-${key}" filterUnits="userSpaceOnUse" x="-10" y="-10" width="${W + 20}" height="${H + 20}">`
      + '<feMorphology in="SourceAlpha" operator="dilate" radius="1.1" result="d"/><feComposite in="d" in2="SourceAlpha" operator="out" result="o"/>'
      + '<feFlood class="ge-flood" result="f"/><feComposite in="f" in2="o" operator="in"/></filter>'
      // The halo: grown, blurred and thresholded to a rounded field, with a thin edge.
      + `<filter id="gh-${key}" filterUnits="userSpaceOnUse" x="-20" y="-20" width="${W + 40}" height="${H + 40}">`
      + '<feMorphology in="SourceAlpha" operator="dilate" radius="5" result="d"/><feGaussianBlur in="d" stdDeviation="3.5" result="b"/>'
      + '<feComponentTransfer in="b" result="s"><feFuncA type="linear" slope="12" intercept="-3"/></feComponentTransfer>'
      + '<feMorphology in="s" operator="erode" radius=".8" result="e"/><feComposite in="s" in2="e" operator="out" result="o"/>'
      + '<feFlood class="gh-fill" result="ff"/><feComposite in="ff" in2="s" operator="in" result="fill"/>'
      + '<feFlood class="gh-edge" result="fe"/><feComposite in="fe" in2="o" operator="in" result="edge"/>'
      + '<feMerge><feMergeNode in="fill"/><feMergeNode in="edge"/></feMerge></filter>';
    if (outlined) groupEdges = `<g class="grp-edges">${nums.map((n) => `<use href="#grp-${n}-${key}" filter="url(#ge-${key})"/>`).join('')}</g>`;
    // An island group gets one halo: its land covers under 15% of the area its
    // places span (the convex hull of their parts' centres). On the 2026-11-24
    // continents the island groups measured 0–11% (Micronesia, Polynesia,
    // Melanesia, the Caribbean) and every other 21% or more (Southern Europe).
    // A count of small places failed both ways: at continent scale much of the
    // mainland is "small", and Cuba isn't. The islands are joined along a minimum
    // spanning tree by thin corridors, so the halo is a single shape however far
    // apart they are (Polynesia), and stays narrow.
    const bridges = (n) => {
      const pts = members(n).flatMap(({ id }) => ptsBy[id] ?? []);
      if (pts.length < 2) return '';
      const inTree = new Set([0]), out = [];
      const best = pts.map((p) => [Math.hypot(p[0] - pts[0][0], p[1] - pts[0][1]), 0]);
      while (inTree.size < pts.length) {
        let j = -1;
        for (let i = 0; i < pts.length; i++) if (!inTree.has(i) && (j < 0 || best[i][0] < best[j][0])) j = i;
        inTree.add(j);
        const a = pts[best[j][1]], b = pts[j];
        out.push(`<line x1="${a[0].toFixed(1)}" y1="${a[1].toFixed(1)}" x2="${b[0].toFixed(1)}" y2="${b[1].toFixed(1)}"/>`);
        for (let i = 0; i < pts.length; i++) {
          if (inTree.has(i)) continue;
          const d = Math.hypot(pts[i][0] - b[0], pts[i][1] - b[1]);
          if (d < best[i][0]) best[i] = [d, j];
        }
      }
      return `<g stroke="#000" stroke-width=".6" stroke-linecap="round">${out.join('')}</g>`;
    };
    const islands = nums.filter((n) => {
      const m = members(n), span = hullArea(m.flatMap(({ id }) => ptsBy[id] ?? []));
      return span > 0 && m.reduce((t, { id }) => t + (areaBy[id] ?? 0), 0) < 0.15 * span;
    });
    groupHalos = `<g class="grp-halos">${islands.map((n) => `<g filter="url(#gh-${key})"><use href="#grp-${n}-${key}"/><use href="#dots-${n}-${key}"/>${bridges(n)}</g>`).join('')}</g>`;
  }

  // The fog never cuts through a contested area: any that comes within the reveal
  // (about 30 units of a place or a claimed area) is revealed whole, in its own
  // neutral hatch (the West Bank and Gaza on a Hebrew page).
  const REACH = 30, cell = REACH;
  const grid = new Map();
  const ptsOf = (f) => { const out = []; d3.geoStream(f, proj.stream({ point(x, y) { out.push([x, y]); }, lineStart() {}, lineEnd() {}, polygonStart() {}, polygonEnd() {}, sphere() {} })); return out; };
  for (const f of [...hl.flatMap(({ refs }) => geomFor(refs, ix)), ...claimed.map((d) => d.f)]) {
    for (const [x, y] of ptsOf(f)) { const k = `${Math.floor(x / cell)},${Math.floor(y / cell)}`; if (!grid.has(k)) grid.set(k, []); grid.get(k).push([x, y]); }
  }
  const nearReveal = (f) => ptsOf(f).some(([x, y]) => {
    if (x < -REACH || y < -REACH || x > W + REACH || y > H + REACH) return false;
    const cx = Math.floor(x / cell), cy = Math.floor(y / cell);
    for (let i = -1; i <= 1; i++) for (let j = -1; j <= 1; j++) for (const [px, py] of grid.get(`${cx + i},${cy + j}`) || []) if (Math.hypot(px - x, py - y) <= REACH) return true;
    return false;
  });

  const touched = [], edges = [], claims = [];
  const disp = disputed.map((d) => {
    let cls, extra = ` data-area="${esc(d.name)}"`;
    if (isSelf(d)) cls = 'edge'; // already filled in the highlight layer; only its dashed edge here
    else {
      const by = hl.filter(({ refs }) => placeClaims(refs, d)).map(({ id }) => id);
      cls = by.length ? 'hatch hatch-claimed' : 'hatch hatch-land';
      if (by.length) extra += ` data-claimants="${by.join(' ')}"`;
    }
    const el = pathEl(d.f, `disputed ${cls}`, extra);
    const dd = el.match(/ d="([^"]+)"/);
    if (dd) edges.push(`<path d="${dd[1]}"/>`);
    if (claimedSet.has(d) || (!isSelf(d) && el && nearReveal(d.f))) {
      if (!claimedSet.has(d)) touched.push(d.name);
      claims.push(el); return '';
    }
    return el;
  }).join('');

  // Abstract scale bar: one round length, true at the centre of the map.
  const c0 = proj.invert([W / 2, H / 2]), c1 = proj.invert([W / 2 + 1, H / 2]);
  const kmPerUnit = d3.geoDistance(c0, c1) * (R_EARTH / 1000);
  const nice = [1, 2, 5].flatMap((m) => [1, 10, 100, 1000, 10000].map((e) => m * e)).sort((a, b) => a - b)
    .filter((km) => km / kmPerUnit <= 80).at(-1);
  const len = nice / kmPerUnit;
  // Hidden where shapes stretch more than 5% anywhere in the frame (continent scale).
  const stretch = maxStretch(proj);
  let scale = stretch > 5 ? '' : `<g class="scale" aria-hidden="true"><path class="scale-bar" d="M14 286H${(14 + len).toFixed(1)}"/>`
    + `<text class="scale-label" x="${(20 + len).toFixed(1)}" y="290">${nice.toLocaleString('en')} km</text></g>`;

  // Locator globe on frames with a corner radius under 30°, or on any frame whose
  // set asks for it (`globe` in sets.json: region maps, where a sea of islands
  // has no other context), in whichever of three corners the places and claims
  // crowd least (the scale bar has bottom-left).
  let inset = '';
  if (spec.cornerDeg < 30 || input.sets[key]?.globe === true) {
    const GR = 44, PADI = 10;
    const corners = [[W - PADI - GR, PADI + GR], [PADI + GR, PADI + GR], [W - PADI - GR, H - PADI - GR]];
    const busy = [...hl.flatMap(({ refs }) => geomFor(refs, ix)), ...claimed.map((d) => d.f)].flatMap(ptsOf);
    const crowd = ([cx, cy]) => busy.filter(([x, y]) => Math.hypot(x - cx, y - cy) < GR + 6).length;
    let [gx, gy] = corners.reduce((a, b) => (crowd(b) < crowd(a) ? b : a));
    // The outline count misses a corner that is quiet but still on a place (Libya).
    // When the globe would cover highlighted land, take the corner that covers
    // least, sampled every 4 units. A globe a region map asked for on a wide frame
    // (over 30°, so the map already shows where it is) is left off when every
    // corner would cover a place: Northern Europe, where all three hid Norway,
    // Finland, Iceland or the Baltics.
    const shapes = hl.flatMap(({ refs }) => geomFor(refs, ix));
    const covered = ([cx, cy]) => {
      let n = 0;
      for (let x = cx - GR; x <= cx + GR; x += 4) for (let y = cy - GR; y <= cy + GR; y += 4) {
        if (Math.hypot(x - cx, y - cy) > GR) continue;
        const ll = proj.invert([x, y]);
        if (ll && shapes.some((f) => d3.geoContains(f, ll))) n++;
      }
      return n;
    };
    if (covered([gx, gy]) > 0) [gx, gy] = corners.reduce((a, b) => (covered(b) < covered(a) ? b : a));
    // Still on a place: try the scale bar's corner, and move the bar to the
    // bottom right (Northern Europe, a small frame, so its globe stays).
    const bl = [PADI + GR, H - PADI - GR];
    if (covered([gx, gy]) > 0 && covered(bl) < covered([gx, gy])) {
      [gx, gy] = bl;
      if (scale) scale = `<g class="scale" aria-hidden="true"><path class="scale-bar" d="M${(W - 14 - len).toFixed(1)} 286H${W - 14}"/>`
        + `<text class="scale-label" x="${(W - 20 - len).toFixed(1)}" y="290" text-anchor="end">${nice.toLocaleString('en')} km</text></g>`;
    }
    if (spec.cornerDeg < 30 || covered([gx, gy]) === 0) {
    const globe = d3.geoOrthographic().rotate([-center[0], -center[1]]).scale(GR).translate([gx, gy]).clipAngle(90);
    const gpath = d3.geoPath({ stream: (st) => globe.stream(thin(st)) }).digits(1);
    const gland = land110.map((f) => gpath(f)).filter(Boolean).join('');
    const [mx, my] = globe(center);
    // A ring on small frames; on larger ones the map's own view outlined on the globe.
    const view = [];
    for (let i = 0; i <= 16; i++) view.push([(i / 16) * W, 0]);
    for (let i = 1; i <= 12; i++) view.push([W, (i / 12) * H]);
    for (let i = 15; i >= 0; i--) view.push([(i / 16) * W, H]);
    for (let i = 11; i >= 1; i--) view.push([0, (i / 12) * H]);
    const vpts = view.map((p) => proj.invert(p)).filter(Boolean).map((ll) => globe(ll)).filter(Boolean);
    const span = vpts.length ? Math.max(...vpts.map((p) => Math.hypot(p[0] - mx, p[1] - my))) : 0;
    const markShape = (cls) => (span > 6
      ? `<path class="${cls}" d="M${vpts.map((p) => p.map((v) => v.toFixed(1)).join(' ')).join('L')}Z"/>`
      : `<circle class="${cls}" cx="${mx.toFixed(1)}" cy="${my.toFixed(1)}" r="4.5"/>`);
    const mark = markShape('globe-casing') + markShape(span > 6 ? 'globe-view' : 'globe-mark');
    inset = `<g class="inset" aria-hidden="true"><circle class="globe-halo" cx="${gx.toFixed(1)}" cy="${gy.toFixed(1)}" r="${GR + 1.5}"/>`
      + `<circle class="globe-sea" cx="${gx.toFixed(1)}" cy="${gy.toFixed(1)}" r="${GR}"/><path class="globe-land" d="${gland}"/>`
      + `${mark}<circle class="globe-rim" cx="${gx.toFixed(1)}" cy="${gy.toFixed(1)}" r="${GR}"/></g>`;
    }
  }

  const names = list(hl.map(({ name }) => name).sort());
  const id = key;
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${W} ${H}" role="img" aria-labelledby="t-${id} d-${id}" class="wt-map__svg" data-map="${key}">`
    + `<title id="t-${id}">Map of ${esc(names)}</title><desc id="d-${id}">Map centred on ${esc(names)}.</desc>`
    // Fog mask: the highlighted shapes (and their rings), grown and blurred by the
    // page's shared #fog filter. Only the focused styles apply it, through --fog.
    + `<defs><mask id="fog-${id}" mask-type="alpha" style="mask-type:alpha" maskUnits="userSpaceOnUse" x="0" y="0" width="${W}" height="${H}"><g mask="url(#fog-edge)"><g filter="url(#fog)"><use href="#hls-${id}"/><use href="#cl-${id}"/><use href="#mk-${id}"/></g></g></mask>${groupDefs}`
    // The coloured relief as a fill, for the styles that show it in the places.
    + `<pattern id="rp-${id}" patternUnits="userSpaceOnUse" width="${W}" height="${H}"><image href="relief/${key}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/></pattern></defs>`
    + `<g class="world" style="--fog:url(#fog-${id});--relief:url(#rp-${id})">`
    + `<rect class="sea" width="${W}" height="${H}"/>`
    + `<image class="seashade" href="sea/${key}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + `<image class="relief" href="relief/${key}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + groupHalos
    + `<g class="lands">${land}</g><g class="hls" id="hls-${id}">${highlight}</g><g class="disputes">${disp}</g><g class="claims" id="cl-${id}">${claims.join('')}</g>`
    + `<g class="dispute-casing">${edges.join('')}</g><g class="dispute-line">${edges.join('')}</g>`
    + `<image class="shade" href="shade/${key}.webp" width="${W}" height="${H}" preserveAspectRatio="none"/>`
    + groupEdges
    + `<g class="markers" id="mk-${id}">${markers.join('')}</g></g>${scale}${inset}</svg>`;

  const round = (v, n = 6) => (Array.isArray(v) ? v.map((x) => round(x, n)) : Number(v.toFixed(n)));
  return {
    svg,
    entry: {
      places: placeIds,
      names: hl.map(({ name }) => name).sort(),
      spec: { ...spec, rotate: round(spec.rotate), translate: round(spec.translate, 3), scale: round(spec.scale, 3), cornerDeg: round(spec.cornerDeg, 2) },
      center: round(center),
      fine,
      stretch: round(stretch, 1),
      scale_km: stretch > 5 ? null : nice,
      claimed: claimed.map((d) => d.name).sort(),
      revealed: touched.sort(),
      svg_bytes: Buffer.byteLength(svg),
      svg_gzip: zlib.gzipSync(svg, { level: 9 }).length,
    },
  };
}

// --estimate renders every requested set in memory and reports the totals,
// writing nothing: the count-before-generating step of the release checklist.
const estimate = process.argv.includes('--estimate');
const only = onlyKeys();
const keys = only ?? Object.keys(input.sets);
const frames = readFrames();
fs.mkdirSync(STAGE, { recursive: true });

const SVG_BUDGET = 20 * 1024;
let raw = 0, gz = 0;
const over = [];
for (const key of keys) {
  const set = input.sets[key];
  if (!set) throw new Error(`No set ${key} in sets.json`);
  const { svg, entry } = render(key, set.places);
  raw += entry.svg_bytes; gz += entry.svg_gzip;
  if (entry.svg_gzip > SVG_BUDGET) over.push(`${key} (${entry.names.join(' + ')}): ${(entry.svg_gzip / 1024).toFixed(1)} KB gz`);
  if (estimate) continue;
  fs.writeFileSync(path.join(STAGE, `${key}.svg`), svg);
  frames[key] = { ...entry, natural_earth: input.natural_earth };
  console.log(key.padEnd(18), `${(entry.svg_gzip / 1024).toFixed(1)} KB gz`.padStart(11), entry.names.join(' + '), entry.claimed.length ? `(claims: ${entry.claimed.join(', ')})` : '');
}

console.log(`${keys.length} maps: SVGs ${(raw / 1048576).toFixed(2)} MB raw, ${(gz / 1048576).toFixed(2)} MB gzipped; median ${(gz / keys.length / 1024).toFixed(1)} KB gz.`);
if (over.length) console.log(`Over the ${SVG_BUDGET / 1024} KB budget:\n  ${over.join('\n  ')}`);
if (!estimate) writeJson(FRAMES, frames);
