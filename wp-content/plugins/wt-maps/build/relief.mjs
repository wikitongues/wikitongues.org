// Reproject Natural Earth's coloured relief (HYP_50M_SR) into each map's frame
// by inverting the same d3 projection the SVG uses, so they line up exactly.
//
//   node relief.mjs [--only=key1,key2]   → cache/raster/relief/<key>.rgb (800×600)
import fs from 'node:fs';
import path from 'node:path';
import { fromSpec, W, H } from './projection.mjs';
import { CACHE, framesToBuild } from './common.mjs';

const SW = 10800, SH = 5400, OW = 800, OH = 600;
const src = fs.readFileSync(path.join(CACHE, 'raster', 'hyp.rgb'));
const OUT = path.join(CACHE, 'raster', 'relief');
fs.mkdirSync(OUT, { recursive: true });

const sample = (lon, lat, out, o) => {
  // Bilinear in source pixel space (pixel centres at +0.5).
  const fx = ((lon + 180) / 360) * SW - 0.5, fy = ((90 - lat) / 180) * SH - 0.5;
  const x0 = Math.floor(fx), y0 = Math.max(0, Math.min(SH - 2, Math.floor(fy)));
  const tx = fx - x0, ty = Math.max(0, Math.min(1, fy - y0));
  const xa = ((x0 % SW) + SW) % SW, xb = (xa + 1) % SW;
  for (let c = 0; c < 3; c++) {
    const p = (x, y) => src[(y * SW + x) * 3 + c];
    out[o + c] = (p(xa, y0) * (1 - tx) + p(xb, y0) * tx) * (1 - ty) + (p(xa, y0 + 1) * (1 - tx) + p(xb, y0 + 1) * tx) * ty;
  }
};

for (const { key, spec } of framesToBuild()) {
  const proj = fromSpec(spec);
  const out = Buffer.alloc(OW * OH * 3);
  for (let y = 0; y < OH; y++) for (let x = 0; x < OW; x++) {
    const ll = proj.invert([(x + 0.5) * (W / OW), (y + 0.5) * (H / OH)]);
    const o = (y * OW + x) * 3;
    if (!ll || !isFinite(ll[0])) { out[o] = out[o + 1] = out[o + 2] = 200; continue; }
    sample(ll[0], ll[1], out, o);
  }
  fs.writeFileSync(path.join(OUT, `${key}.rgb`), out);
  console.log('relief', key);
}
