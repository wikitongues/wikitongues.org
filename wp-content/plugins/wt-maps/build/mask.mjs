// Cut each map's rasters to its vector coastline, encode them as WebP (the
// coloured relief and the hillshade cut to the land, the ocean floor to the
// sea), then publish the map: its images, its SVG and its index entry, in that
// order, so maps/ never holds an SVG whose images are missing.
//
//   node mask.mjs [--only=key1,key2]   → maps/<key>.svg, maps/relief|shade|sea/<key>.webp, maps/index.json
//
// The land mask is rasterized from the map's own SVG, so the images can't drift
// from the outlines. An SVG and its images come from one projection and must
// ship together.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { CACHE, MAPS, STAGE, INDEX, framesToBuild, readIndex, writeJson } from './common.mjs';

const R = path.join(CACHE, 'raster');
for (const dir of ['relief', 'shade', 'sea']) fs.mkdirSync(path.join(MAPS, dir), { recursive: true });
fs.mkdirSync(path.join(R, 'mask'), { recursive: true });

const magick = (...args) => execFileSync('magick', args);

const BUDGET = 30 * 1024;
/**
 * Encode one WebP at the first quality that fits the budget: each quality at
 * `size`, then each again at 600×450. Returns the bytes written; the last try
 * is kept when nothing fits, and build.mjs reports it as over budget.
 */
function fit(input, dir, key, qualities, alphaQuality, size) {
  const out = path.join(MAPS, dir, `${key}.webp`);
  const tries = [...qualities.map((q) => [q, size]), ...(size === '600x450' ? [] : qualities.map((q) => [q, '600x450']))];
  for (const [q, sz] of tries) {
    const resize = sz === '800x600' ? [] : ['-resize', sz];
    magick(...input, ...resize, '-strip', '-quality', String(q), '-define', `webp:alpha-quality=${alphaQuality}`, out);
    const bytes = fs.statSync(out).size;
    if (bytes <= BUDGET) return bytes;
  }
  return fs.statSync(out).size;
}

const index = readIndex();
for (const { key, natural_earth: version, ...entry } of framesToBuild()) {
  const svg = fs.readFileSync(path.join(STAGE, `${key}.svg`), 'utf8');
  const paths = ['lands', 'hls', 'disputes', 'claims'].flatMap((g) => {
    // Up to the next top-level layer: `hls` may hold nested groups (continent maps,
    // `groups`), and `lands` may carry a modifier (`lands--regions`). Every layer is
    // in every map, so a miss means the SVG changed shape: stop rather than cut
    // the images to a partial coastline.
    const m = svg.match(new RegExp(`<g class="${g}(?: [^"]*)?">(.*?)</g>(?:<g class="(?:hls|disputes|claims)"|<image class="shade")`));
    if (!m) throw new Error(`${key}: no <g class="${g}"> layer in the SVG`);
    return [...m[1].matchAll(/ d="([^"]+)"/g)].map((x) => `<path d="${x[1]}"/>`);
  }).join('');
  const maskSvg = path.join(R, 'mask', `${key}.svg`), mask = path.join(R, 'mask', `${key}.png`);
  fs.writeFileSync(maskSvg, `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" width="800" height="600">`
    + `<rect width="400" height="300" fill="#000"/><g fill="#fff" stroke="#fff" stroke-width=".4">${paths}</g></svg>`);
  magick('-background', 'black', maskSvg, '-colorspace', 'gray', '-resize', '800x600!', mask);

  // Each image starts at the quality the prototype chose and steps down, then
  // shrinks to 2× a 300px sidebar, until it fits the 30 KB budget (plan 3.2).
  // The same inputs always stop on the same step, so rebuilds stay identical.
  const cut = (raw, invert) => [...raw, invert ? ['(', mask, '-negate', ')'] : mask].flat()
    .concat(['-alpha', 'off', '-compose', 'CopyOpacity', '-composite']);
  const sizes = {
    relief: fit(cut(['-size', '800x600', '-depth', '8', `rgb:${path.join(R, 'relief', `${key}.rgb`)}`]), 'relief', key, [70, 60, 50, 40, 30], 60, '800x600'),
    // The hillshade is detailed, so it always ships at 600×450.
    shade: fit(cut(['-size', '800x600', '-depth', '8', `gray:${path.join(R, 'shade', `${key}.gray`)}`]), 'shade', key, [30, 25, 20, 15], 50, '600x450'),
    sea: fit(cut(['-size', '800x600', '-depth', '8', `gray:${path.join(R, 'sea', `${key}.gray`)}`], true), 'sea', key, [70, 60, 50, 40, 30], 60, '800x600'),
  };

  // Publish: the images are in place, so the SVG and its entry can follow.
  fs.copyFileSync(path.join(STAGE, `${key}.svg`), path.join(MAPS, `${key}.svg`));
  index.natural_earth = version;
  index.maps[key] = { ...entry, images: sizes };
  index.maps = Object.fromEntries(Object.entries(index.maps).sort(([a], [b]) => a.localeCompare(b)));
  writeJson(INDEX, index);
  console.log('published', key);
}
