// Reproject the equirectangular relief raster into each map's frame by
// inverting the same d3 projection the SVG uses, so they line up exactly.
import fs from 'node:fs';
import { fromSpec } from './projection.mjs';

const S = process.argv[2];
const SW = 10800, SH = 5400, OW = 800, OH = 600, W = 400, H = 300;
const src = fs.readFileSync(`${S}/raster/hyp.rgb`);
const rendered = JSON.parse(fs.readFileSync(`${S}/data/rendered.json`, 'utf8'));
const seen = new Set();

const sample = (lon, lat, out, o) => {
  // bilinear in source pixel space (pixel centres at +0.5)
  let fx = ((lon + 180) / 360) * SW - 0.5, fy = ((90 - lat) / 180) * SH - 0.5;
  const x0 = Math.floor(fx), y0 = Math.max(0, Math.min(SH - 2, Math.floor(fy)));
  const tx = fx - x0, ty = Math.max(0, Math.min(1, fy - y0));
  const xa = ((x0 % SW) + SW) % SW, xb = (xa + 1) % SW;
  for (let c = 0; c < 3; c++) {
    const p = (x, y) => src[(y * SW + x) * 3 + c];
    const v = (p(xa, y0) * (1 - tx) + p(xb, y0) * tx) * (1 - ty) + (p(xa, y0 + 1) * (1 - tx) + p(xb, y0 + 1) * tx) * ty;
    out[o + c] = v;
  }
};

for (const r of rendered) {
  if (seen.has(r.iso)) continue;
  seen.add(r.iso);
  const proj = fromSpec(r.spec);
  const out = Buffer.alloc(OW * OH * 3);
  for (let y = 0; y < OH; y++) for (let x = 0; x < OW; x++) {
    const ll = proj.invert([(x + 0.5) * (W / OW), (y + 0.5) * (H / OH)]);
    const o = (y * OW + x) * 3;
    if (!ll || !isFinite(ll[0])) { out[o] = out[o + 1] = out[o + 2] = 200; continue; }
    sample(ll[0], ll[1], out, o);
  }
  fs.writeFileSync(`${S}/raster/${r.iso}.rgb`, out);
  console.log(r.iso);
}
