// Grayscale hillshade per map frame, remapped so flat ground (206 in SR_50M)
// is neutral 128: under soft-light it leaves our fills untouched on the
// flat and only shades slopes.
import fs from 'node:fs';
import { fromSpec } from './projection.mjs';

const S = process.argv[2];
const SW = 10800, SH = 5400, OW = 800, OH = 600, W = 400, H = 300, FLAT = 206;
const src = fs.readFileSync(`${S}/raster/sr/sr.gray`);
const seen = new Set();
for (const r of JSON.parse(fs.readFileSync(`${S}/data/rendered.json`, 'utf8'))) {
  if (seen.has(r.iso)) continue;
  seen.add(r.iso);
  const proj = fromSpec(r.spec);
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
    out[o] = Math.max(0, Math.min(255, Math.round((v * 128) / FLAT)));
  }
  fs.writeFileSync(`${S}/raster/sr/${r.iso}.gray`, out);
}
console.log([...seen].join(' '));
