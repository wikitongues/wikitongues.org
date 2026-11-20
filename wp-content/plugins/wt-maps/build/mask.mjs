// Cut each map's rasters to its vector coastline and encode them as WebP:
// the coloured relief and the hillshade to the land, the ocean floor to the sea.
//
//   node mask.mjs [--only=key1,key2]   → maps/relief|shade|sea/<key>.webp
//
// The land mask is rasterized from the map's own SVG, so the images can't drift
// from the outlines. An SVG and its images come from one projection and must
// ship together.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { CACHE, MAPS, framesToBuild } from './common.mjs';

const R = path.join(CACHE, 'raster');
for (const dir of ['relief', 'shade', 'sea']) fs.mkdirSync(path.join(MAPS, dir), { recursive: true });
fs.mkdirSync(path.join(R, 'mask'), { recursive: true });

const magick = (...args) => execFileSync('magick', args);

for (const { key } of framesToBuild()) {
  const svg = fs.readFileSync(path.join(MAPS, `${key}.svg`), 'utf8');
  const paths = ['lands', 'hls', 'disputes', 'claims'].flatMap((g) => {
    const m = svg.match(new RegExp(`<g class="${g}"[^>]*>(.*?)</g>`));
    return m ? [...m[1].matchAll(/ d="([^"]+)"/g)].map((x) => `<path d="${x[1]}"/>`) : [];
  }).join('');
  const maskSvg = path.join(R, 'mask', `${key}.svg`), mask = path.join(R, 'mask', `${key}.png`);
  fs.writeFileSync(maskSvg, `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" width="800" height="600">`
    + `<rect width="400" height="300" fill="#000"/><g fill="#fff" stroke="#fff" stroke-width=".4">${paths}</g></svg>`);
  magick('-background', 'black', maskSvg, '-colorspace', 'gray', '-resize', '800x600!', mask);

  magick('-size', '800x600', '-depth', '8', `rgb:${path.join(R, 'relief', `${key}.rgb`)}`, mask,
    '-alpha', 'off', '-compose', 'CopyOpacity', '-composite', '-strip', '-quality', '70', '-define', 'webp:alpha-quality=60',
    path.join(MAPS, 'relief', `${key}.webp`));
  // The hillshade is detailed, so it ships at 2× a 300px sidebar (600×450) and
  // quality 30, which keeps the heaviest map near 30 KB.
  magick('-size', '800x600', '-depth', '8', `gray:${path.join(R, 'shade', `${key}.gray`)}`, mask,
    '-alpha', 'off', '-compose', 'CopyOpacity', '-composite', '-resize', '600x450', '-strip', '-quality', '30', '-define', 'webp:alpha-quality=50',
    path.join(MAPS, 'shade', `${key}.webp`));
  magick('-size', '800x600', '-depth', '8', `gray:${path.join(R, 'sea', `${key}.gray`)}`, '(', mask, '-negate', ')',
    '-alpha', 'off', '-compose', 'CopyOpacity', '-composite', '-strip', '-quality', '70', '-define', 'webp:alpha-quality=60',
    path.join(MAPS, 'sea', `${key}.webp`));
  console.log('mask', key);
}
