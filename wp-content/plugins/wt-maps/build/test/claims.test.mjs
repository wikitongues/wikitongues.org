// node --test (from build/): who claims a contested area. See claims.mjs.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { claimResolver } from '../claims.mjs';

// A 1° square at [lon, lat], wound clockwise as d3-geo expects.
const square = (lon, lat) => ({ type: 'Polygon', coordinates: [[[lon, lat], [lon, lat + 1], [lon + 1, lat + 1], [lon + 1, lat], [lon, lat]]] });
const subunit = (ADM0_A3, GU_A3, SU_A3, at) => ({ type: 'Feature', properties: { ADM0_A3, GU_A3, SU_A3 }, geometry: square(...at) });

const claims = claimResolver([
  subunit('FRA', 'FXX', 'FXX', [2, 46]),
  subunit('FRA', 'GUF', 'GUF', [-53, 4]),
  subunit('FRA', 'REU', 'REU', [55, -21]),
  subunit('RUS', 'RUS', 'RUE', [60, 55]),
  subunit('RUS', 'RUS', 'RUC', [34, 45]),
  subunit('UKR', 'UKR', 'UKR', [31, 49]),
]);
const area = (code, at, ...claimants) => ({ code, f: { type: 'Feature', geometry: square(...at) }, claimants: new Set(claimants) });

const lawa = area('B10', [-54.5, 3], 'FRA');
const crimea = area('B20', [34, 45], 'RUS', 'UKR');

test("a country's claim belongs to its part nearest the area", () => {
  assert.equal(claims([['units', 'GUF']], lawa), true, 'French Guiana');
  assert.equal(claims([['units', 'REU']], lawa), false, 'Réunion');
  assert.equal(claims([['units', 'FXX']], lawa), false, 'metropolitan France');
});

test('a place drawn as the whole country holds all its claims', () => {
  assert.equal(claims([['countries', 'FRA']], lawa), true);
  assert.equal(claims([['countries', 'RUS']], crimea), true, 'Russia');
  assert.equal(claims([['countries', 'UKR']], crimea), true, 'Ukraine: the rule is symmetric');
});

test('a place that is not a claimant claims nothing', () => {
  assert.equal(claims([['countries', 'UKR']], lawa), false);
  assert.equal(claims([['units', 'GUF'], ['units', 'REU']], crimea), false);
});
