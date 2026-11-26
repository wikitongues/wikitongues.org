// Undisputed parts (claims spec rule 3): every territory is drawn as its shape
// minus every contested area, for its fill, outline, frame and hover area.
//
// Natural Earth's country shapes follow control: Russia's takes in Crimea,
// Israel's the Golan, Morocco's most of Western Sahara. Subtracting the
// contested areas makes every claimant's outline stop at the same line, so
// the map draws no area as belonging to either side. The areas themselves are
// drawn on top, as their own shapes.
//
// A shape is never cut by an area it stands for: Taiwan's own shape keeps
// Taiwan, and Palestine's units keep the West Bank and Gaza.
import polygonClipping from 'polygon-clipping';
import * as d3 from 'd3-geo';

const polysOf = (g) => (g.type === 'Polygon' ? [g.coordinates] : g.type === 'MultiPolygon' ? g.coordinates : []);

// Plain longitude/latitude bounds: enough to skip pairs that cannot overlap.
// (Natural Earth splits shapes at the antimeridian, so no box wraps.)
function bounds(g) {
  let x0 = Infinity, y0 = Infinity, x1 = -Infinity, y1 = -Infinity;
  for (const poly of polysOf(g)) for (const [x, y] of poly[0]) {
    if (x < x0) x0 = x; if (x > x1) x1 = x; if (y < y0) y0 = y; if (y > y1) y1 = y;
  }
  return [x0, y0, x1, y1];
}
const overlaps = (a, b) => a[0] <= b[2] && b[0] <= a[2] && a[1] <= b[3] && b[1] <= a[3];

// polygon-clipping winds rings by its own convention; d3-geo reads a ring
// wound the wrong way as the rest of the sphere. So each output polygon is
// wound the way d3-geo needs: its area is less than a hemisphere.
const toD3 = (multi) => multi.map((poly) => {
  const exterior = { type: 'Polygon', coordinates: [poly[0]] };
  return d3.geoArea(exterior) > 2 * Math.PI ? poly.map((ring) => [...ring].reverse()) : poly;
});

/**
 * Cut the contested areas out of these features, in place.
 *
 * @param {object[]} features Natural Earth features (countries, map units, subunits).
 * @param {{f: object}[]} areas The contested areas drawn on the maps.
 * @param {(f: object, area: object) => boolean} standsFor Whether a feature is, or
 *   is part of, the place the area itself is (it keeps the area).
 * @returns {number} How many features changed.
 */
export function subtractAreas(features, areas, standsFor) {
  const areaFeats = new Set(areas.map((a) => a.f));
  const boxes = areas.map((a) => bounds(a.f.geometry));
  const areaSizes = areas.map((a) => d3.geoArea(a.f));
  let changed = 0;
  for (const f of features) {
    if (areaFeats.has(f) || !f.geometry || !polysOf(f.geometry).length) continue;
    const box = bounds(f.geometry);
    const mine = polysOf(f.geometry);
    // A real overlap, not a sliver where two scales' borders disagree (a 1:10m
    // neighbour against a 1:50m area): at least a fifth of the area.
    const cut = areas.filter((a, i) => overlaps(box, boxes[i]) && !standsFor(f, a)
      && d3.geoArea({ type: 'MultiPolygon', coordinates: toD3(polygonClipping.intersection(mine, polysOf(a.f.geometry))) }) > 0.2 * areaSizes[i]);
    if (!cut.length) continue;
    const after = toD3(polygonClipping.difference(mine, ...cut.map((a) => polysOf(a.f.geometry))));
    f.geometry = { type: 'MultiPolygon', coordinates: after }; // empty when the shape was all contested (Natural Earth's W. Sahara)
    changed++;
  }
  return changed;
}
