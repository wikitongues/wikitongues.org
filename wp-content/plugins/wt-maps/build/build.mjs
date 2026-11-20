// Run every build step, in order, for the same maps.
//
//   node build.mjs [--only=key1,key2]
//
// Before: `node fetch.mjs` (once) and `wp wt-maps export` (whenever places or
// language links change). Each step reads the frames render.mjs staged, so the
// SVG and its three images always share a projection; mask.mjs publishes each
// finished map to maps/. Ends with a size report against the page-weight budget.
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';
import { BUILD, MAPS, framesToBuild, readIndex } from './common.mjs';

const args = process.argv.slice(2).filter((a) => a.startsWith('--only='));
for (const step of ['render', 'relief', 'dem', 'seafloor', 'mask']) {
  console.log(`\n== ${step}`);
  execFileSync(process.execPath, [path.join(BUILD, `${step}.mjs`), ...args], { stdio: 'inherit' });
}

// Budget (plan 3.2): SVG ≤ 20 KB gzipped, images ≤ 30 KB each.
const IMG_BUDGET = 30 * 1024;
console.log('\n== sizes (SVG gz · shade · sea · relief)');
const index = readIndex();
let total = 0, overBudget = 0;
for (const { key } of framesToBuild()) {
  const { names, svg_gzip: gz } = index.maps[key];
  const kb = (dir) => fs.statSync(path.join(MAPS, dir, `${key}.webp`)).size;
  const imgs = ['shade', 'sea', 'relief'].map(kb);
  const flag = gz > 20 * 1024 || imgs.some((b) => b > IMG_BUDGET) ? '  OVER BUDGET' : '';
  total += gz + imgs.reduce((a, b) => a + b, 0);
  if (flag) overBudget++;
  console.log(key.padEnd(18), [gz, ...imgs].map((b) => `${(b / 1024).toFixed(1)}K`.padStart(6)).join(' '), names.join(' + ') + flag);
}
console.log(`\n${framesToBuild().length} maps, ${(total / 1048576).toFixed(1)} MB with images; ${overBudget} over budget.`);
