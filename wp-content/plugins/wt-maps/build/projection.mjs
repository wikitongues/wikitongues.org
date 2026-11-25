// One place that turns a stored projection spec into a d3 projection, used by
// the SVG renderer and both raster builds so they always agree.
import * as d3 from 'd3-geo';

export const W = 400, H = 300;
const PAD = [[W * 0.12, H * 0.12], [W * 0.88, H * 0.88]];
// Conic switch: off. Tested 2026-11-16 — over each large country's own land
// the centred azimuthal beats an Albers conic every time (Russia worst 12.6%
// vs 28.8%, Canada 6.4% vs 36.6%). Set CONIC_ABOVE to re-test.
export const CONIC_ABOVE_DEG = Number(process.env.CONIC_ABOVE ?? Infinity);
// The azimuthal is clipped this far from its centre, in degrees.
export const CLIP_DEG = 100;

export function fromSpec(s) {
  if (s.type === 'conic') {
    return d3.geoConicEqualArea().parallels(s.parallels).rotate(s.rotate).center(s.center)
      .scale(s.scale).translate(s.translate);
  }
  const p = d3.geoAzimuthalEqualArea().rotate(s.rotate).clipAngle(CLIP_DEG).scale(s.scale).translate(s.translate);
  // clipAngle stops the SVG shapes at the clip, but invert() still returns
  // points out to the antipode. The raster steps invert every pixel, so without
  // this they paint a stretched far hemisphere round the rim of wide frames.
  // null is what they already treat as off the globe.
  const centre = [-s.rotate[0], -s.rotate[1]], clip = (CLIP_DEG * Math.PI) / 180;
  const invert = p.invert;
  p.invert = (xy) => {
    const ll = invert(xy);
    return ll && d3.geoDistance(ll, centre) <= clip ? ll : null;
  };
  return p;
}

function cornerRadius(proj, center) {
  let m = 0;
  for (let i = 0; i <= 8; i++) for (const [x, y] of [[i * W / 8, 0], [i * W / 8, H], [0, i * H / 8], [W, i * H / 8]]) {
    const ll = proj.invert([x, y]);
    if (ll) m = Math.max(m, d3.geoDistance(center, ll));
  }
  return (m * 180) / Math.PI;
}

/** Pick and fit a projection for a frame; returns { proj, spec }. */
export function choose(frame, center, roll = 0) {
  const rotate = roll ? [-center[0], -center[1], roll] : [-center[0], -center[1]];
  const az = d3.geoAzimuthalEqualArea().rotate(rotate).clipAngle(CLIP_DEG).fitExtent(PAD, frame);
  const r = cornerRadius(az, center);
  let proj = az, spec = { type: 'azimuthal', rotate: az.rotate() };
  if (r > CONIC_ABOVE_DEG) {
    // Standard parallels at 1/6 and 5/6 of the frame's latitude span.
    const [[, s], [, n]] = d3.geoBounds(frame);
    const span = n - s;
    const parallels = [s + span / 6, n - span / 6];
    const rotate = [-center[0], 0], c = [0, (s + n) / 2];
    proj = d3.geoConicEqualArea().parallels(parallels).rotate(rotate).center(c).fitExtent(PAD, frame);
    spec = { type: 'conic', parallels, rotate, center: c };
  }
  spec.scale = proj.scale(); spec.translate = proj.translate(); spec.cornerDeg = r;
  return { proj, spec };
}

/** Largest shape stretch inside the frame (Tissot a/b − 1), sampled on a grid. */
export function maxStretch(proj) {
  let worst = 0;
  const e = 1e-4;
  for (let y = 0; y <= H; y += H / 12) for (let x = 0; x <= W; x += W / 16) {
    const ll = proj.invert([x, y]);
    if (!ll || Math.abs(ll[1]) > 89) continue;
    const [lon, lat] = ll, cl = Math.cos((lat * Math.PI) / 180);
    const p0 = proj([lon, lat]), pe = proj([lon + e / cl, lat]), pn = proj([lon, lat + e]);
    if (!p0 || !pe || !pn) continue;
    // Jacobian columns per unit ground distance east and north
    const a = [(pe[0] - p0[0]), (pe[1] - p0[1])], b = [(pn[0] - p0[0]), (pn[1] - p0[1])];
    const E = a[0] ** 2 + a[1] ** 2, G = b[0] ** 2 + b[1] ** 2, F = a[0] * b[0] + a[1] * b[1];
    const tr = E + G, det = Math.sqrt(Math.max(0, E * G - F * F));
    const s1 = Math.sqrt((tr + Math.sqrt(Math.max(0, tr * tr - 4 * det * det))) / 2);
    const s2 = det / s1;
    worst = Math.max(worst, s1 / s2 - 1);
  }
  return worst * 100;
}
