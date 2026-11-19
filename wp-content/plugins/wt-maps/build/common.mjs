// Paths and arguments shared by the build steps.
//
// Inputs (build/cache/, gitignored): Natural Earth layers and rasters from
// fetch.mjs, sets.json from `wp wt-maps export`, and intermediate rasters.
// Output (maps/, committed and deployed): one SVG per set, its three terrain
// images, and index.json with each map's projection, so the raster steps and
// the contested-areas review read the same frame the SVG was drawn in.
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

export const BUILD = path.dirname(fileURLToPath(import.meta.url));
export const PLUGIN = path.dirname(BUILD);
export const CACHE = path.join(BUILD, 'cache');
export const MAPS = path.join(PLUGIN, 'maps');

export const readJson = (p) => JSON.parse(fs.readFileSync(p, 'utf8'));
export const writeJson = (p, v) => {
  fs.mkdirSync(path.dirname(p), { recursive: true });
  fs.writeFileSync(p, JSON.stringify(v, null, 1) + '\n');
};

export const INDEX = path.join(MAPS, 'index.json');
export const readIndex = () => (fs.existsSync(INDEX) ? readJson(INDEX) : { natural_earth: '', maps: {} });

/** `--only=key1,key2` → those keys; otherwise null (every set). */
export function onlyKeys(argv = process.argv.slice(2)) {
  const a = argv.find((x) => x.startsWith('--only='));
  return a ? a.slice(7).split(',').map((s) => s.trim()).filter(Boolean) : null;
}

/** The index entries a raster step should process: --only, or all of them. */
export function framesToBuild() {
  const index = readIndex();
  const only = onlyKeys();
  const keys = only ?? Object.keys(index.maps);
  return keys.map((key) => {
    if (!index.maps[key]) throw new Error(`No rendered map for ${key}; run render.mjs first.`);
    return { key, ...index.maps[key] };
  });
}
