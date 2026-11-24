// node --test: the raster inverse stops at the azimuthal clip, like the shapes.
import test from 'node:test';
import assert from 'node:assert/strict';
import * as d3 from 'd3-geo';
import { fromSpec, CLIP_DEG, W, H } from '../projection.mjs';

// Centred on 0°, 0° with the frame centre at the projection centre.
const spec = { type: 'azimuthal', rotate: [0, 0, 0], scale: 60, translate: [W / 2, H / 2] };
const raw = d3.geoAzimuthalEqualArea().rotate(spec.rotate).scale(spec.scale).translate(spec.translate);

test('a point inside the clip inverts', () => {
  const ll = fromSpec(spec).invert(raw([60, 10]));
  assert.ok(ll);
  assert.ok(Math.abs(ll[0] - 60) < 1e-6 && Math.abs(ll[1] - 10) < 1e-6);
});

test('a point past the clip returns null', () => {
  const far = [CLIP_DEG + 30, 0];
  assert.ok(raw.invert(raw(far)), 'the unclipped inverse would return it');
  assert.equal(fromSpec(spec).invert(raw(far)), null);
});

test('conic specs are unchanged', () => {
  const c = { type: 'conic', parallels: [20, 50], rotate: [-100, 0], center: [0, 35], scale: 300, translate: [W / 2, H / 2] };
  assert.ok(fromSpec(c).invert([0, 0]));
});
