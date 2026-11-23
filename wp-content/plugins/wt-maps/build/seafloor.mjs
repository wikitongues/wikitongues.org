// Ocean-floor shading per map frame from Natural Earth's GRAY_50M_SR_OB. Deep
// ocean (gray 89) maps to neutral 128 so hard-light leaves it alone; shelves
// lighten, trenches darken. GAIN exaggerates the subtle seafloor relief a little.
//
//   node seafloor.mjs [--only=key1,key2]   → cache/raster/sea/<key>.gray (800×600)
import fs from 'node:fs';
import path from 'node:path';
import { fromSpec, W, H } from './projection.mjs';
import { CACHE, framesToBuild } from './common.mjs';

const SW = 10800, SH = 5400, OW = 800, OH = 600, FLAT = 89, GAIN = 1.6;
const src = fs.readFileSync(path.join(CACHE, 'raster', 'ob.gray'));
const OUT = path.join(CACHE, 'raster', 'sea');
fs.mkdirSync(OUT, { recursive: true });

for (const { key, spec } of framesToBuild()) {
  const proj = fromSpec(spec);
  const out = Buffer.alloc(OW * OH);
  for (let y = 0; y < OH; y++) for (let x = 0; x < OW; x++) {
    const ll = proj.invert([(x + 0.5) * (W / OW), (y + 0.5) * (H / OH)]);
    const o = y * OW + x;
    if (!ll || !isFinite(ll[0])) { out[o] = 128; continue; }
    const fx = ((ll[0] + 180) / 360) * SW - 0.5, fy = ((90 - ll[1]) / 180) * SH - 0.5;
    const x0 = Math.floor(fx), y0 = Math.max(0, Math.min(SH - 2, Math.floor(fy)));
    const tx = fx - x0, ty = Math.max(0, Math.min(1, fy - y0));
    const xa = ((x0 % SW) + SW) % SW, xb = (xa + 1) % SW;
    const p = (xx, yy) => src[yy * SW + xx];
    const v = (p(xa, y0) * (1 - tx) + p(xb, y0) * tx) * (1 - ty) + (p(xa, y0 + 1) * (1 - tx) + p(xb, y0 + 1) * tx) * ty;
    out[o] = Math.max(0, Math.min(255, Math.round(128 + (v - FLAT) * GAIN)));
  }
  fs.writeFileSync(path.join(OUT, `${key}.gray`), out);
  console.log('seafloor', key);
}
