// Natural Earth's contested areas, and the claims table's say on them.
//
// The areas are every NE breakaway area plus the map units that are themselves
// contested states or areas. Who claims each one comes from the claims table
// (claims-table.mjs, data/claims.json), not from Natural Earth directly:
// Natural Earth's point-of-view fields are only the table's first layer
// (seed-claims.mjs).
import path from 'node:path';
import { PLUGIN } from './common.mjs';
import { readClaims, effectiveClaims } from './claims-table.mjs';

export const CLAIMS_FILE = path.join(PLUGIN, 'data', 'claims.json');
export const DISPUTED_UNITS = ['KOS', 'SOL', 'CYN', 'GAZ', 'WEB', 'SAH', 'TWN'];

/**
 * Every Natural Earth contested area, with the codes NE's points of view name
 * as claimants (layer 1's raw input).
 *
 * @param {object[]} brk  ne_50m_admin_0_breakaway_disputed_areas features.
 * @param {object} unitsByCode  ne_50m_admin_0_map_units features by GU_A3.
 */
export function neAreas(brk, unitsByCode) {
  const pov = Object.keys(brk[0].properties).filter((k) => /^ADM0_A3(_[A-Z]{2})?$/.test(k));
  return [
    ...brk.map((f) => ({ code: f.properties.BRK_A3, name: f.properties.BRK_NAME, f })),
    ...DISPUTED_UNITS.map((c) => ({ code: c, name: unitsByCode[c].properties.NAME, f: unitsByCode[c] })),
  ].map((d) => {
    const p = d.f.properties;
    const neClaimants = new Set(pov.map((k) => p[k]).filter((v) => typeof v === 'string' && /^[A-Z]{3}$/.test(v) && !/^[BC]\d\d$/.test(v)));
    return { ...d, defacto: p.ADM0_A3, neClaimants };
  });
}

/** Whether a place, drawn from these refs, is the area itself (Taiwan's own map). */
export const isSelf = (refs, d) => refs.some(([l, c]) => c === d.code || (l !== 'countries' && c === d.defacto));

/**
 * The areas to draw, each with its effective claimants (record IDs). An area
 * the table has no row for stops the build; an ended area is drawn as part of
 * its territory, and a duplicate is drawn once, as the area it repeats.
 */
export function contestedAreas(areas, file = CLAIMS_FILE) {
  const { areas: claims } = effectiveClaims(readClaims(file));
  const missing = areas.filter((d) => !claims.has(d.code)).map((d) => `${d.code} ${d.name}`);
  if (missing.length) throw new Error(`No claims-table row for ${missing.join(', ')}; run seed-claims.mjs --write.`);
  return areas
    .map((d) => ({ ...d, claims: claims.get(d.code) }))
    .filter((d) => d.claims.status === 'claimed')
    .map((d) => ({ ...d, claimants: d.claims.claimants }));
}
