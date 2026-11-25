// node --test (from build/): undisputed parts. See undisputed.mjs.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import * as d3 from 'd3-geo';
import { subtractAreas } from '../undisputed.mjs';

// A box from [lon0, lat0] to [lon1, lat1], wound clockwise as d3-geo expects.
const box = (x0, y0, x1, y1) => ({ type: 'Polygon', coordinates: [[[x0, y0], [x0, y1], [x1, y1], [x1, y0], [x0, y0]]] });
const feature = (code, geometry) => ({ type: 'Feature', properties: { ADM0_A3: code }, geometry });
const area = (code, geometry) => ({ code, f: feature(code, geometry) });
const km2 = (f) => d3.geoArea(f) * 6371 * 6371;
const never = () => false;

test('a contested area inside a shape is cut out of it', () => {
  const russia = feature('RUS', box(30, 44, 40, 50));
  const crimea = area('B89', box(33, 44, 36, 46));
  const before = km2(russia);
  assert.equal(subtractAreas([russia], [crimea], never), 1);
  // Within 2%: the boxes' edges are straight in degrees but great circles on the sphere.
  assert.ok(Math.abs(before - km2(russia) - km2(crimea.f)) < before * 0.02, 'shape lost the area');
  assert.ok(km2(russia) < before && km2(russia) > 0, 'wound for d3: a small area, not the rest of the sphere');
});

test('a shape that only touches an area, or is far from it, is left alone', () => {
  const ukraine = feature('UKR', box(22, 46, 33, 52));
  const crimea = area('B89', box(33, 44, 36, 46));
  const g = ukraine.geometry;
  assert.equal(subtractAreas([ukraine], [crimea], never), 0);
  assert.equal(ukraine.geometry, g);
});

test('a shape keeps an area it stands for', () => {
  const taiwan = feature('TWN', box(120, 22, 122, 25));
  const area_ = area('TWN', box(120, 22, 122, 25));
  const standsFor = (f, a) => f.properties.ADM0_A3 === a.code;
  assert.equal(subtractAreas([taiwan], [area_], standsFor), 0);
});

test('a shape that is all contested ends up empty, not the whole sphere', () => {
  const zone = feature('SAH', box(-12, 21, -8, 27));
  const westernSahara = area('B19', box(-17, 20, -8, 28));
  subtractAreas([zone], [westernSahara], never);
  assert.deepEqual(zone.geometry, { type: 'MultiPolygon', coordinates: [] });
});

test('a sliver where two scales disagree is not cut', () => {
  const syria = feature('SYR', box(35.9, 32, 42, 37));
  const golan = area('B16', box(35.6, 32.6, 36, 33.3)); // overlaps Syria by 0.1° of its 0.4°
  assert.equal(subtractAreas([syria], [golan], never), 1, 'a quarter of the area: a real overlap');
  const syria2 = feature('SYR', box(35.98, 32, 42, 37)); // 0.02° of 0.4°: a sliver
  assert.equal(subtractAreas([syria2], [golan], never), 0);
});

test('a second run changes nothing', () => {
  const russia = feature('RUS', box(30, 44, 40, 50));
  const crimea = area('B89', box(33, 44, 36, 46));
  subtractAreas([russia], [crimea], never);
  assert.equal(subtractAreas([russia], [crimea], never), 0);
});
