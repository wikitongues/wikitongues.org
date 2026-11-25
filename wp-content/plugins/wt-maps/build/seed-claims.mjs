// Write layer 1 of the claims table: Natural Earth's claims, by record ID.
//
//   node seed-claims.mjs           # dry run: print what would change
//   node seed-claims.mjs --write   # replace layer 1 in data/claims.json
//
// Natural Earth names a claimant by country code in its point-of-view fields;
// the table names places by record ID (data/shapes.json). A country's claim
// belongs to its place nearest the area (claims.mjs): the Lawa Headwaters are
// French Guiana's, not metropolitan France's. A place is never listed as
// claiming itself (Taiwan's own area).
//
// Only layer 1 is touched; the review layers after it are kept as they are.
// Run it again after a Natural Earth upgrade or a shape-table change; with
// nothing new it reports no changes.
import path from 'node:path';
import { CACHE, PLUGIN, readJson, writeJson } from './common.mjs';
import { claimResolver } from './claims.mjs';
import { neAreas, isSelf, CLAIMS_FILE } from './contested.mjs';
import { readClaims } from './claims-table.mjs';

const write = process.argv.includes('--write');
const layer = (n) => readJson(path.join(CACHE, 'ne', `ne_50m_admin_0_${n}.geojson`)).features;
const brk = layer('breakaway_disputed_areas');
const units = Object.fromEntries(layer('map_units').map((f) => [f.properties.GU_A3, f]));
const resolve = claimResolver(layer('map_subunits'));
const shapes = readJson(path.join(PLUGIN, 'data', 'shapes.json'));
const places = Object.entries(shapes.places).map(([id, p]) => ({ id, refs: p.shapes.map((s) => s.split(':')) }));

const rows = {};
const unplaced = [];
for (const d of neAreas(brk, units).sort((a, b) => a.code.localeCompare(b.code))) {
  const area = { ...d, claimants: d.neClaimants };
  const ids = places.filter(({ refs }) => !isSelf(refs, d) && resolve(refs, area)).map(({ id }) => id).sort();
  // A claimant code no place draws: listed, so the gap is seen rather than lost.
  for (const c of d.neClaimants) {
    if (!places.some(({ refs }) => resolve(refs, { ...area, claimants: new Set([c]) }))) {
      unplaced.push(`${d.code} ${d.name}: ${c}`);
    }
  }
  rows[d.code] = { name: d.name, claimants: ids };
}

const layer1 = {
  id: 'natural-earth',
  author: 'Natural Earth',
  source: `Natural Earth ${shapes.natural_earth}, admin-0 point-of-view fields (ADM0_A3 and ADM0_A3_xx), resolved to places by seed-claims.mjs`,
  rows,
};

const table = readClaims(CLAIMS_FILE);
const before = table.layers?.[0]?.rows ?? {};
const changes = [];
for (const code of new Set([...Object.keys(before), ...Object.keys(rows)])) {
  const a = JSON.stringify(before[code] ?? null), b = JSON.stringify(rows[code] ?? null);
  if (a !== b) changes.push(`${code}: ${a} → ${b}`);
}

console.log(`${Object.keys(rows).length} areas; ${changes.length} change(s) to layer 1.`);
changes.forEach((c) => console.log(`  ${c}`));
if (unplaced.length) console.log(`Claimants with no place in the shape table:\n  ${unplaced.join('\n  ')}`);
if (write && changes.length) {
  writeJson(CLAIMS_FILE, {
    about: 'Who claims each contested area (plan 3.2, claims spec; docs/maps.md). Layer 1 is Natural Earth\'s, written by build/seed-claims.mjs: never edit it by hand. Each later layer is a review whose changes apply in order, each with a reason and a source.',
    layers: [layer1, ...(table.layers ?? []).slice(1)],
  });
  console.log(`Wrote ${CLAIMS_FILE}.`);
} else if (!write) {
  console.log('Dry run: pass --write to save.');
}
