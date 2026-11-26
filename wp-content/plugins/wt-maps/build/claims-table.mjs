// The claims table (plan 3.2, claims spec; docs/maps.md): who claims each
// contested area, as ordered layers in data/claims.json.
//
// Layer 1 is Natural Earth's, generated from its point-of-view fields by
// seed-claims.mjs and never edited by hand. Each later layer is a review: a
// list of changes, each with its reason and source, applied in order on top of
// the layers before it. A review never edits an earlier layer, so upgrading
// Natural Earth replaces layer 1 alone and the reviews still apply.
//
// The effective claims (each area's claimants and status) are computed here,
// never stored. The build reads them; `doctor` checks them.
//
// Layer 1 row:  "B89": { "name": "Crimea", "claimants": ["rec…", "rec…"] }
// Review change: { "area": "B00", "drop": ["rec…"], "reason": "…", "source": "…" }
//   add:    record IDs that claim the area
//   drop:   record IDs whose claim no longer counts (dormant or ended)
//   status: "claimed" (the default), "ended" (drawn as part of its territory),
//           or "duplicate" with "of": the area whose shape this one repeats
//   shape:  adds an area Natural Earth has no contested-area shape for, from
//           another Natural Earth layer: "admin1:<ISO 3166-2 code>". Needs a
//           "name"; the area code is the ISO code ("UA-14"). Claimants start empty.
import fs from 'node:fs';

export const STATUSES = ['claimed', 'ended', 'duplicate'];
export const SHAPE_LAYERS = ['admin1'];

/** The table on disk, or an empty one. */
export function readClaims(file) {
  if (!fs.existsSync(file)) return { layers: [] };
  return JSON.parse(fs.readFileSync(file, 'utf8'));
}

/**
 * Apply the layers in order.
 *
 * @returns {{ areas: Map<string, {name: string, claimants: Set<string>, status: string, of?: string, shape?: string}>, noops: object[] }}
 *   areas: the effective claims by area code. noops: review changes that change
 *   nothing (a claimant already there or already gone), for `doctor` to list.
 */
export function effectiveClaims(table) {
  const areas = new Map();
  const noops = [];
  (table.layers ?? []).forEach((layer, i) => {
    if (i === 0) {
      for (const [code, row] of Object.entries(layer.rows ?? {})) {
        areas.set(code, { name: row.name, claimants: new Set(row.claimants), status: 'claimed' });
      }
      return;
    }
    for (const change of layer.changes ?? []) {
      const where = `${layer.id}: ${change.area}`;
      if (!change.reason) throw new Error(`${where} has no reason`);
      let changed = false;
      if (change.shape !== undefined) {
        const [shapeLayer, shapeCode] = String(change.shape).split(':');
        if (!SHAPE_LAYERS.includes(shapeLayer) || !shapeCode) throw new Error(`${where}: shape must be admin1:<ISO 3166-2 code>, not ${change.shape}`);
        if (!change.name) throw new Error(`${where}: a new area needs a name`);
        if (areas.has(change.area)) throw new Error(`${where}: area already exists; a shape can only add one`);
        areas.set(change.area, { name: change.name, claimants: new Set(), status: 'claimed', shape: change.shape });
        changed = true;
      }
      const a = areas.get(change.area);
      if (!a) throw new Error(`${where} is not in the table: add it with a shape first`);
      for (const id of change.add ?? []) {
        if (!a.claimants.has(id)) { a.claimants.add(id); changed = true; }
      }
      for (const id of change.drop ?? []) {
        if (a.claimants.delete(id)) changed = true;
      }
      if (change.status !== undefined) {
        if (!STATUSES.includes(change.status)) throw new Error(`${where}: unknown status ${change.status}`);
        if (change.status === 'duplicate') {
          if (!areas.has(change.of)) throw new Error(`${where}: duplicate of unknown area ${change.of}`);
          if (a.of !== change.of) { a.of = change.of; changed = true; }
        }
        if (a.status !== change.status) { a.status = change.status; changed = true; }
      }
      if (!changed) noops.push({ layer: layer.id, ...change });
    }
  });
  return { areas, noops };
}
