// node --test (from build/): the claims table's layers. See claims-table.mjs.
import { test } from 'node:test';
import assert from 'node:assert/strict';
import { effectiveClaims } from '../claims-table.mjs';

const layer1 = {
  id: 'natural-earth',
  rows: {
    B00: { name: 'Arunachal Pradesh', claimants: ['CHN', 'IND', 'TWN'] },
    B19: { name: 'W. Sahara', claimants: ['MAR'] },
    SAH: { name: 'W. Sahara', claimants: ['MAR'] },
    B38: { name: 'Artsakh', claimants: ['AZE'] },
  },
};
const review = (...changes) => ({ id: 'review', changes });
const reason = 'test';

test('layer 1 alone is the table', () => {
  const { areas, noops } = effectiveClaims({ layers: [layer1] });
  assert.deepEqual([...areas.get('B00').claimants], ['CHN', 'IND', 'TWN']);
  assert.equal(areas.get('B00').status, 'claimed');
  assert.equal(noops.length, 0);
});

test('a review drops and adds claimants without editing layer 1', () => {
  const table = { layers: [layer1, review({ area: 'B00', drop: ['TWN'], reason }, { area: 'B19', add: ['SAH'], reason })] };
  const { areas } = effectiveClaims(table);
  assert.deepEqual([...areas.get('B00').claimants], ['CHN', 'IND']);
  assert.deepEqual([...areas.get('B19').claimants], ['MAR', 'SAH']);
  assert.deepEqual(layer1.rows.B00.claimants, ['CHN', 'IND', 'TWN'], 'layer 1 untouched');
});

test('later layers apply after earlier ones', () => {
  const table = { layers: [layer1, review({ area: 'B00', drop: ['TWN'], reason }), review({ area: 'B00', add: ['TWN'], reason })] };
  assert.ok(effectiveClaims(table).areas.get('B00').claimants.has('TWN'));
});

test('status: ended and duplicate', () => {
  const table = { layers: [layer1, review({ area: 'B38', status: 'ended', reason }, { area: 'SAH', status: 'duplicate', of: 'B19', reason })] };
  const { areas } = effectiveClaims(table);
  assert.equal(areas.get('B38').status, 'ended');
  assert.equal(areas.get('SAH').status, 'duplicate');
  assert.equal(areas.get('SAH').of, 'B19');
});

test('a change that changes nothing is reported, not applied twice', () => {
  const { noops } = effectiveClaims({ layers: [layer1, review({ area: 'B19', drop: ['TWN'], reason }, { area: 'B00', add: ['IND'], reason })] });
  assert.deepEqual(noops.map((n) => n.area), ['B19', 'B00']);
});

test('a review must name a known area and give a reason', () => {
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'X99', drop: ['TWN'], reason })] }), /not in the table/);
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'B00', drop: ['TWN'] })] }), /no reason/);
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'B00', status: 'paused', reason })] }), /unknown status/);
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'SAH', status: 'duplicate', of: 'X99', reason })] }), /unknown area/);
});

test('a change with a shape adds an area, which later changes can claim or replace', () => {
  const table = { layers: [layer1, review(
    { area: 'UA-14', shape: 'admin1:UA-14', name: 'Donetsk Oblast', add: ['RUS', 'UKR'], reason },
    { area: 'B38', status: 'duplicate', of: 'UA-14', reason },
  )] };
  const { areas, noops } = effectiveClaims(table);
  assert.deepEqual([...areas.get('UA-14').claimants], ['RUS', 'UKR']);
  assert.equal(areas.get('UA-14').shape, 'admin1:UA-14');
  assert.equal(areas.get('B38').of, 'UA-14');
  assert.equal(noops.length, 0);
});

test('a shape needs a name, an admin-1 code, and a new area code', () => {
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'UA-14', shape: 'admin1:UA-14', reason })] }), /needs a name/);
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'UA-14', shape: 'admin0:UKR', name: 'x', reason })] }), /admin1:/);
  assert.throws(() => effectiveClaims({ layers: [layer1, review({ area: 'B00', shape: 'admin1:IN-AR', name: 'x', reason })] }), /already exists/);
});

test('an empty table has no areas', () => {
  assert.equal(effectiveClaims({ layers: [] }).areas.size, 0);
});
