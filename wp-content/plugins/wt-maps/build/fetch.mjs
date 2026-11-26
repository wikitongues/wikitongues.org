// Download the source data into build/cache/. Safe to rerun: files already
// there are kept, so a rebuild uses exactly the data it was built from.
//
// Vectors: Natural Earth GeoJSON at the release tag the shape table names.
// Rasters: Natural Earth's 1:50m coloured relief (HYP_50M_SR) and ocean-floor
// shading (GRAY_50M_SR_OB), converted to raw bytes for the raster steps.
// The land hillshade comes from AWS Terrain Tiles, fetched per frame by dem.mjs.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { CACHE, PLUGIN, readJson } from './common.mjs';

const version = readJson(path.join(PLUGIN, 'data/shapes.json')).natural_earth;
const NE = path.join(CACHE, 'ne');
const RASTER = path.join(CACHE, 'raster');
fs.mkdirSync(NE, { recursive: true });
fs.mkdirSync(RASTER, { recursive: true });

const VECTORS = [
  '50m_admin_0_countries', '50m_admin_0_map_units', '50m_admin_0_map_subunits', '50m_admin_0_breakaway_disputed_areas',
  '10m_admin_0_countries', '10m_admin_0_map_units', '10m_admin_0_map_subunits',
  // Admin-1, for contested areas Natural Earth has no admin-0 shape for (the 2022 oblasts).
  '10m_admin_1_states_provinces',
  '110m_land',
];

const get = (url, out) => {
  if (fs.existsSync(out)) return;
  console.log('fetch', url);
  execFileSync('curl', ['-sSfL', '-o', `${out}.part`, url]);
  fs.renameSync(`${out}.part`, out);
};

for (const name of VECTORS) {
  get(`https://raw.githubusercontent.com/nvkelso/natural-earth-vector/v${version}/geojson/ne_${name}.geojson`, path.join(NE, `ne_${name}.geojson`));
}

// Raster name → [raw file, ImageMagick output format].
const RASTERS = { HYP_50M_SR: ['hyp.rgb', 'rgb'], GRAY_50M_SR_OB: ['ob.gray', 'gray'] };
for (const [name, [raw, fmt]] of Object.entries(RASTERS)) {
  const out = path.join(RASTER, raw);
  if (fs.existsSync(out)) continue;
  const zip = path.join(RASTER, `${name}.zip`);
  get(`https://naciscdn.org/naturalearth/50m/raster/${name}.zip`, zip);
  const dir = path.join(RASTER, name);
  execFileSync('unzip', ['-oq', zip, '-d', dir]);
  const tif = fs.readdirSync(dir, { recursive: true }).find((f) => f.endsWith(`${name}.tif`));
  const args = [path.join(dir, tif)];
  if (fmt === 'gray') args.push('-colorspace', 'gray');
  execFileSync('magick', [...args, '-depth', '8', `${fmt}:${out}`]);
  console.log('converted', name);
}
console.log(`Natural Earth ${version} ready in ${path.relative(PLUGIN, CACHE)}/`);
