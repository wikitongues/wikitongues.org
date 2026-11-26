// node --test (from build/): areas the claims table adds from admin-1. See contested.mjs.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { shapedAreas } from '../contested.mjs';

const donetsk = { type: 'Feature', properties: { iso_3166_2: 'UA-14', adm0_a3: 'UKR' }, geometry: { type: 'Polygon', coordinates: [] } };
const table = (changes) => ({ layers: [{ id: 'natural-earth', rows: {} }, { id: 'review', changes }] });
const change = { area: 'UA-14', shape: 'admin1:UA-14', name: 'Donetsk Oblast', add: ['RUS'], reason: 'test' };

test('a shaped area takes its feature from admin-1, by ISO code', () => {
  const [a] = shapedAreas(table([change]), () => [donetsk]);
  assert.equal(a.code, 'UA-14');
  assert.equal(a.name, 'Donetsk Oblast');
  assert.equal(a.f, donetsk);
  assert.equal(a.defacto, 'UKR');
});

test('admin-1 is only read when the table names a shape', () => {
  assert.deepEqual(shapedAreas(table([{ area: 'B00', drop: ['TWN'], reason: 'x' }]), () => assert.fail('read admin-1')), []);
});

test('an unknown shape stops the build', () => {
  assert.throws(() => shapedAreas(table([{ ...change, shape: 'admin1:UA-99' }]), () => [donetsk]), /doesn't have/);
});
