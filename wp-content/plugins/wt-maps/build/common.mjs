// Paths and arguments shared by the build steps.
//
// Inputs (build/cache/, gitignored): Natural Earth layers and rasters from
// fetch.mjs, sets.json from `wp wt-maps export`, and intermediate rasters.
//
// Staging (build/cache/stage/): render.mjs writes each SVG and its frame (its
// projection) here, and the raster steps read the frames from here.
//
// Output (maps/, committed and deployed): mask.mjs publishes a map only once
// its SVG and all three terrain images exist, then records it in index.json.
// So maps/ never holds an SVG without its images, however a build ends.
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

export const STAGE = path.join(CACHE, 'stage');
export const FRAMES = path.join(STAGE, 'frames.json');
export const INDEX = path.join(MAPS, 'index.json');
export const readFrames = () => (fs.existsSync(FRAMES) ? readJson(FRAMES) : {});
export const readIndex = () => (fs.existsSync(INDEX) ? readJson(INDEX) : { natural_earth: '', maps: {} });

/** `--only=key1,key2` → those keys; otherwise null (every set). */
export function onlyKeys(argv = process.argv.slice(2)) {
  const a = argv.find((x) => x.startsWith('--only='));
  return a ? a.slice(7).split(',').map((s) => s.trim()).filter(Boolean) : null;
}

/** The staged frames a raster step should process: --only, or all of them. */
export function framesToBuild() {
  const frames = readFrames();
  const keys = onlyKeys() ?? Object.keys(frames);
  return keys.map((key) => {
    if (!frames[key]) throw new Error(`No rendered map for ${key}; run render.mjs first.`);
    return { key, ...frames[key] };
  });
}
