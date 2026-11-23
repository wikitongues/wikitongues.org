// Land hillshade per map frame, computed from an elevation model fetched at a
// resolution matched to the frame. Replaces hillshade.mjs, whose Natural Earth
// raster is fixed at ~3.7 km a pixel and goes soft on small frames (Hawaiʻi, Malta).
// The sea floor stays on Natural Earth (seafloor.mjs): the tiles' bathymetry has
// steps of several hundred metres between sources (near the antimeridian, Fiji and
// Tuvalu), which hillshading turns into straight seams. The sea is background, so
// its softer resolution is fine.
//
// Source: AWS Terrain Tiles (terrarium encoding; SRTM, GMTED, ETOPO1 and others,
// land and bathymetry in one grid). Attribution: see
// https://github.com/tilezen/joerd/blob/master/docs/attribution.md
//
// Output (same files as before, so mask.py is unchanged):
//   raster/sr/{iso}.gray  land hillshade, flat ground = 128 (neutral under hard-light)
import fs from 'node:fs';
import { execFileSync } from 'node:child_process';
import * as d3 from 'd3-geo';
import { fromSpec } from './projection.mjs';

const S = process.argv[2];
const OW = 800, OH = 600, W = 400, H = 300, R = 6371008.8;
const MAX_Z = 12, MAX_TILES = 160;
const LIGHT = (() => { // from the north-west, 45° up: (east, north, up)
  const az = (315 * Math.PI) / 180, alt = (45 * Math.PI) / 180;
  return [Math.cos(alt) * Math.sin(az), Math.cos(alt) * Math.cos(az), Math.sin(alt)];
})();
const FLAT = LIGHT[2];

const tileCache = new Map();
function tile(z, x, y) {
  const k = `${z}/${x}/${y}`;
  if (tileCache.has(k)) return tileCache.get(k);
  const dir = `${S}/dem/tiles/${z}/${x}`, raw = `${dir}/${y}.rgb`;
  if (!fs.existsSync(raw)) {
    fs.mkdirSync(dir, { recursive: true });
    const png = `${dir}/${y}.png`;
    if (!fs.existsSync(png)) execFileSync('curl', ['-sfL', '-o', png, `https://s3.amazonaws.com/elevation-tiles-prod/terrarium/${z}/${x}/${y}.png`]);
    execFileSync('magick', [png, '-depth', '8', `rgb:${raw}`]);
  }
  const b = fs.readFileSync(raw);
  const e = new Float32Array(256 * 256);
  for (let i = 0; i < e.length; i++) e[i] = b[i * 3] * 256 + b[i * 3 + 1] + b[i * 3 + 2] / 256 - 32768;
  tileCache.set(k, e);
  return e;
}

const merc = (lon, lat, z) => {
  const n = 2 ** z * 256, s = Math.sin((Math.max(-85.05, Math.min(85.05, lat)) * Math.PI) / 180);
  return [((lon + 180) / 360) * n, (0.5 - Math.log((1 + s) / (1 - s)) / (4 * Math.PI)) * n];
};

function elevAt(px, py, z) {
  const n = 2 ** z * 256;
  const get = (x, y) => {
    x = ((Math.floor(x) % n) + n) % n; y = Math.max(0, Math.min(n - 1, Math.floor(y)));
    return tile(z, x >> 8, y >> 8)[(y & 255) * 256 + (x & 255)];
  };
  const x0 = Math.floor(px - 0.5), y0 = Math.floor(py - 0.5), tx = px - 0.5 - x0, ty = py - 0.5 - y0;
  return (get(x0, y0) * (1 - tx) + get(x0 + 1, y0) * tx) * (1 - ty) + (get(x0, y0 + 1) * (1 - tx) + get(x0 + 1, y0 + 1) * tx) * ty;
}

const seen = new Set();
for (const r of JSON.parse(fs.readFileSync(`${S}/data/rendered.json`, 'utf8'))) {
  if (seen.has(r.iso)) continue;
  seen.add(r.iso);
  const proj = fromSpec(r.spec);

  // Metres per output pixel at the centre, and the matching tile zoom.
  const c0 = proj.invert([W / 2, H / 2]), c1 = proj.invert([W / 2 + 1, H / 2]);
  const mpp = (d3.geoDistance(c0, c1) * R * W) / OW;
  const lls = new Array(OW * OH);
  for (let y = 0; y < OH; y++) for (let x = 0; x < OW; x++) lls[y * OW + x] = proj.invert([(x + 0.5) * (W / OW), (y + 0.5) * (H / OH)]);
  let z = Math.max(0, Math.min(MAX_Z, Math.ceil(Math.log2((156543.03 * Math.cos((c0[1] * Math.PI) / 180)) / mpp))));
  const tilesAt = (zz) => {
    const t = new Set(), n = 2 ** zz;
    for (let i = 0; i < lls.length; i += 7) { const ll = lls[i]; if (!ll) continue; const [px, py] = merc(ll[0], ll[1], zz); t.add(`${((Math.floor(px / 256) % n) + n) % n}/${Math.max(0, Math.min(n - 1, Math.floor(py / 256)))}`); }
    return t.size;
  };
  while (z > 0 && tilesAt(z) > MAX_TILES) z--;

  const elev = new Float32Array(OW * OH);
  for (let i = 0; i < lls.length; i++) { const ll = lls[i]; elev[i] = ll ? elevAt(...merc(ll[0], ll[1], z), z) : 0; }

  // Vertical exaggeration grows with ground distance per pixel, so continent-
  // sized frames aren't flat and island-sized ones aren't overcooked.
  const ex = Math.max(1.5, Math.min(18, (mpp / 40) ** 0.5));
  const land = Buffer.alloc(OW * OH);
  for (let y = 0; y < OH; y++) for (let x = 0; x < OW; x++) {
    const i = y * OW + x;
    const e = (xx, yy) => elev[Math.max(0, Math.min(OH - 1, yy)) * OW + Math.max(0, Math.min(OW - 1, xx))];
    const dzdx = (e(x + 1, y) - e(x - 1, y)) / (2 * mpp);
    const dzdn = (e(x, y - 1) - e(x, y + 1)) / (2 * mpp); // north = up the screen
    const nx = -ex * dzdx, ny = -ex * dzdn, len = Math.hypot(nx, ny, 1);
    const shade = Math.max(0, (nx * LIGHT[0] + ny * LIGHT[1] + LIGHT[2]) / len);
    land[i] = Math.max(0, Math.min(255, Math.round((128 * shade) / FLAT)));
  }
  fs.mkdirSync(`${S}/raster/sr`, { recursive: true });
  fs.writeFileSync(`${S}/raster/sr/${r.iso}.gray`, land);
  console.log(r.iso, `z${z}`, `${Math.round(mpp)} m/px`, `×${ex.toFixed(1)}`, `${tileCache.size} tiles`);
  tileCache.clear();
}
