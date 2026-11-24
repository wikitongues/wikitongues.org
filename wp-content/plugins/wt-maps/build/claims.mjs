// Which highlighted place claims a contested area.
//
// Natural Earth names a claimant by its country code (FRA), but a map draws
// places at a finer level: metropolitan France (units:FXX), Réunion (REU) and
// French Guiana (GUF) are territories of their own. So a country's claim
// belongs to its part nearest the contested area: the Lawa Headwaters, on the
// Suriname border, are French Guiana's, not Réunion's or metropolitan
// France's. Matching on the country code alone pulled them into the frames of
// the French map and of Southern Africa (Réunion, Mayotte).
//
// A place that draws a whole country (countries:RUS, countries:GBR) holds all
// of that country's claims, as before: Crimea and the Kurils stay on Russia's
// map, the Falklands on the United Kingdom's.
import * as d3 from 'd3-geo';

const parts = (f) => {
  const g = f.geometry;
  return (g.type === 'Polygon' ? [g.coordinates] : g.coordinates)
    .map((c) => d3.geoCentroid({ type: 'Polygon', coordinates: c }));
};

/**
 * @param {object[]} subunits Natural Earth admin_0_map_subunits features.
 * @returns {(refs: string[][], area: {f: object, code: string, claimants: Set<string>}) => boolean}
 *   Whether a place, drawn from these `[layer, code]` refs, claims the area.
 */
export function claimResolver(subunits) {
  const byCountry = new Map();
  for (const f of subunits) {
    const a3 = f.properties.ADM0_A3;
    if (!byCountry.has(a3)) byCountry.set(a3, []);
    byCountry.get(a3).push({ p: f.properties, centroids: parts(f) });
  }

  // The claimant's subunit nearest the area, or null when Natural Earth has
  // none for that code (a breakaway state claiming itself).
  const holders = new Map();
  const holder = (area, claimant) => {
    const k = `${area.code}|${claimant}`;
    if (!holders.has(k)) {
      const c = d3.geoCentroid(area.f);
      let best = null, bd = Infinity;
      for (const s of byCountry.get(claimant) ?? []) {
        const d = Math.min(...s.centroids.map((q) => d3.geoDistance(c, q)));
        if (d < bd) { bd = d; best = s.p; }
      }
      holders.set(k, best);
    }
    return holders.get(k);
  };

  const covers = ([layer, code], h, claimant) => {
    if (!h) return code === claimant;
    if (layer === 'countries') return h.ADM0_A3 === code;
    if (layer === 'units') return h.GU_A3 === code;
    if (layer === 'subunits') return h.SU_A3 === code;
    return code === claimant; // breakaway: a breakaway state's own claims
  };

  return (refs, area) => [...area.claimants].some((c) => {
    const h = holder(area, c);
    return refs.some((ref) => covers(ref, h, c));
  });
}
