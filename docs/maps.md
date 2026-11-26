# Maps (`wt-maps`)

Locator maps for language pages (plan 3.2, step 1; shipped 2026-11-23), territory pages and sub-region pages (step 1b, 2026-11-23). This file describes what is built and the decisions behind it ([Design decisions](#design-decisions)). The data model they follow, territories, origins and subdivisions, is [plan 6.8](../plan.md#68-language-geography-across-airtable-make-and-wordpress). Step 2, interactive maps on territory and region pages, is still in the plan.

Built: the shape table, the `doctor` and `export` commands, the build (317 maps: 270 for languages and the Language Rights report, 47 more for territory, sub-region and continent pages), the renderer `wt_map()` with the `[wt_map]` shortcode, the style (Light full only since 2026-11-24), the settings page, and the sidebar map with its caption. The claims table, undisputed parts and the contested-area display (claims spec, on production 2026-11-26). Not yet built (plan 3.2): claims on hover, credits, and the review tooling.

## Map sets and the build

A map is built once per **set of places**, not per language. A language's set is its focus places when it has a focus, otherwise its published territory links, and when it has none of those, its nations of origin (Eteocypriot: no territory links, origin Cyprus). The origins fallback is temporary: plan 6.8 has the sync write territories as presence plus origins, after which every origin is a territory and the fallback is removed. The places are kept as record IDs, because the sync creates places separately on each environment, so their post IDs differ. A set with one place is keyed by that place's record ID (`recguDPgV3cIXLO34`). A set with several places is keyed `set-` plus 12 hex characters of the SHA-1 of the sorted IDs (`wt_maps_set_key()`, in PHP only). A language whose set includes a place with no shape gets no map, never a partial one.

```bash
cd wp-content/plugins/wt-maps/build
npm ci
node fetch.mjs                      # once: Natural Earth (at the table's version) into build/cache/
wp --allow-root wt-maps export      # read-only: writes build/cache/sets.json
node render.mjs --estimate          # count and size every set, writing nothing
node build.mjs --only=<key>,<key>   # or no --only for every set
npm test                            # node --test: the build's own checks (build/test/)
```

| Step | Writes |
|---|---|
| `seed-claims.mjs` | Layer 1 of `data/claims.json`: Natural Earth's claims, by record ID. Dry run by default; `--write` saves. Run after a Natural Earth upgrade or a shape-table change |
| `render.mjs` | `build/cache/stage/<key>.svg`, and the set's frame in `stage/frames.json` (places, projection, contested areas claimed and revealed, size) |
| `relief.mjs` | the coloured relief reprojected into the frame (cache) |
| `dem.mjs` | the land hillshade from AWS Terrain Tiles, at a zoom matched to the frame (tiles cached in `build/cache/dem/`) |
| `seafloor.mjs` | the ocean-floor shading (cache) |
| `mask.mjs` | `maps/relief/`, `maps/shade/` and `maps/sea/<key>.webp`, cut to the SVG's own coastline, and then **publishes** the map: it copies the SVG to `maps/` and adds its entry to `maps/index.json` |

Every raster step reads the projection that `render.mjs` staged, so an SVG and its images always line up. The azimuthal is clipped 100° from its centre (`CLIP_DEG`), and its inverse returns nothing past the clip, so on wide frames the images stop where the shapes do instead of painting the far hemisphere round the rim. A map reaches `maps/` only after its three images are written, so an interrupted or unfinished build never leaves an SVG without its terrain: the site would show such a map with no relief and, in the focused styles, with no fill. A rebuild with the same inputs writes byte-identical files.

`maps/` is committed and deployed. `build/` is committed but not deployed (`.rsync-filter`), and `build/cache/` is gitignored.

**What the renderer adds** (`WT\Maps\Renderer`, see below). The SVG is shared by every language with that set, so it has no primary or secondary styling. Highlighted shapes and rings have `data-place` (the record ID), and so do the neighbouring countries on the base map that are places in the shape table (see [Hover labels and clicks](#hover-labels-and-clicks)). A contested area that a place claims has `hatch-claimed` and `data-claimants` (the record IDs claiming it). PHP adds the `primary`/`secondary` classes per language, makes the relative image hrefs (`shade/<key>.webp`) absolute, and writes the language's own name and `<desc>`. The page needs the shared hatch definitions (`wt_maps_defs()`).

**Sizes (2026-11-19, local data).** There are 269 maps for 8,073 languages; 70 have several places. 491 of those languages have no territory link and are drawn from their nations of origin; 16 languages have neither and get no map. With images the maps come to 13.0 MB, and the SVGs alone to 1.71 MB gzipped (median 5 KB). A full build took about 50 minutes on the first run, mostly fetching terrain tiles; with the tiles cached, about half that. The publish step encodes each image at the first quality that fits the 30 KB budget, stepping down and then shrinking to 600×450, and records the bytes in `index.json`. Two maps are still over budget. Tajikistan's hillshade is 32.9 KB even at the lowest step. French's six-place set (Andorra, Djibouti, France, Lebanon, Martinique, Monaco) has a 20.9 KB SVG.

**Sets for other pages.** Pages that aren't languages can ask for a set through the `wt_maps_extra_sets` filter (label → place record IDs). `export` and `doctor` add them to the plan with `wt_maps_add_extra_sets()`: a set no language uses has a language count of 0 and lists its page labels, and a set with a place that has no shape is reported as unmapped. The Language Rights report adds one set per region ([language-rights.md](language-rights.md#links-between-reports-and-territories)). The theme adds one per territory and one per archive sub-region (see [Territory and region pages](#territory-and-region-pages)). `wt_map_places()` renders any set by record IDs, with its own title; `wt_map()` uses it for languages.

**Rebuilding.** `node build.mjs` rebuilds every set; `--only=<keys>` limits it. Unchanged maps come out byte-identical, so the diff shows only what changed. A map no language uses any more stays in `maps/` until it is removed by hand: `wp wt-maps doctor` lists those, and lists any set in use that has no built map.

## The shape table

`wp-content/plugins/wt-maps/data/shapes.json` says which Natural Earth shapes draw each place. It is committed with the plugin, so a change to it, the maps rebuilt from it, and the `doctor` output land in one PR.

It is keyed on **Airtable record IDs**, never names. Every place carries one in `_airtable_record_id`: the nations' territory pages (stamped from the Nations table), ISO-coded territories and subdivisions (both synced from the Territories table). The IDs are the same on every environment, so the table needs no database write and no per-environment backfill. `name` in each entry is a label for people; nothing matches on it.

```json
{
	"natural_earth": "5.1.2",
	"places": {
		"recJdmrV257IxkekS": { "name": "Hawaii", "shapes": [ "subunits:USH" ] },
		"rec…":              { "name": "Palestine", "shapes": [ "units:GAZ", "units:WEB" ], "note": "Two NE units" }
	},
	"focus": {
		"recUC4moS37PaSogS": { "name": "Hawaiian", "places": [ "recJdmrV257IxkekS" ] }
	}
}
```

**Shape references** are `layer:CODE`:

| Layer | Natural Earth file | Code field | Examples |
|---|---|---|---|
| `countries` | `admin_0_countries` | `ADM0_A3` | `AFG`, `USA` |
| `units` | `admin_0_map_units` | `GU_A3` | `FXX` (metropolitan France), `GAZ`, `GUM`, `GRL` |
| `subunits` | `admin_0_map_subunits` | `SU_A3` | `USH` (Hawaii), `USK` (Alaska), `WLS`, `SCT` |
| `breakaway` | `admin_0_breakaway_disputed_areas` | `BRK_A3` | `B35` (Abkhazia) |

A place with several references is drawn as all of them. A place missing from the table has no map, never a wrong one. Subdivisions are in the table only when a map should draw them; at launch that is Hawaii.

**Focus** is the curated list of languages whose map draws part of a nation: the listed places are drawn in place of the language's territory links (Hawaiian → Hawaii, not the whole United States). Launch set: Hawaiian → Hawaii, Chamorro → Guam and the Northern Mariana Islands, Carolinian → the Northern Mariana Islands. It is a list rather than a rule over each language's `subdivisions` field, because Airtable's Subject [Territories] still mixes origin with official-language links (French links 15 places); separating them is plan 6.8 phase C, after which the focus can come from the field.

The first table (2026-11-19) came from the prototype's matching (`docs/maps-prototype/build/match.py`: 189 territories by name, the rest from its override table), plus Greenland, Guam, the Northern Mariana Islands and Hawaii. Every code was checked against the Natural Earth 5.1.2 files.

PHP reads it through `wt_maps_shape_table()` (a `WT\Maps\Shape_Table`) and `wt_map_shapes( $post_id )`, which looks the post up by its record ID.

## `wp wt-maps doctor`

Read-only. Compares the table with the posts and lists:

- problems in the file (keys that aren't record IDs, malformed references, empty entries, focus places with no shapes);
- published territories with no shape, which show no map;
- table entries that match no place post;
- table entries whose territory isn't published (informational: a new territory stays a draft until it has a region);
- focus languages that don't exist.

It exits with an error when anything but the informational list is non-empty. Run it after any territory change and before a maps release.

## Changing a place's shape

1. Find the code in the Natural Earth 5.1.2 layer (see the table above).
2. Edit `data/shapes.json`: key on the place's Airtable record ID (on its post as `_airtable_record_id`, or in Airtable).
3. Run `wp wt-maps doctor` and `composer test` (a test checks the committed table is clean).
4. Rebuild the affected maps in the same PR, once the build exists.

## Rendering

`wt_map( $args )` returns a language's map as `<figure class="wt-map">`, or `''` when no map has been built for its set of places. The first map on a page is preceded by the shared `<svg class="wt-map-defs">` (the red contested-area hatch, `#hatch-claim`), which every map's own `--claim` pattern draws over its relief. A map that shows an active contested area (one a highlighted place claims, or is) carries one line under it, `.wt-map__note`: "Borders and contested areas are shown for reference and do not imply a position on sovereignty." (`Renderer::shows_contested()`).

| Argument | Default | |
|---|---|---|
| `language` | the current post | language post ID |
| `territories` | the language's focus, else its territory links, else its nations of origin | place post IDs to draw instead |
| `primary` | its `nations_of_origin` | post IDs to highlight as primary |
| `class` | | extra class on the figure |

`[wt_map language="123" territories="1,2" primary="1"]` takes the same arguments, with comma-separated IDs.

**Roles.** Places that are also countries of origin are primary and the rest secondary. If none is a country of origin, all are primary (Chamorro: nation United States, map of Guam and the Northern Mariana Islands). A contested area's class follows its strongest claimant on the map (`hatch-primary`, `hatch-secondary`), but both draw the same: the claims spec draws active areas alike for primary and secondary places.

`WT\Maps\Renderer::decorate()` adds the roles, the absolute image URLs, and the language's accessible name ("Where Hawaiian is spoken", from `standard_name`, as `aria-label` on the `<svg>`, with the `<desc>` as `aria-describedby`) and `<desc>`. The `<title>` is left empty, because browsers show an SVG title as a tooltip over every shape (removed 2026-11-24, as it talked over the hover labels); all through `WP_HTML_Tag_Processor`. The result is cached in the object cache (group `wt_maps`), keyed on the set, the roles, the text, the class, `Renderer::VERSION` and the SVG's modification time. Bump `Renderer::VERSION` when `decorate()` changes its output.

**Placement.** `modules/languages/meta--languages-single.php` prints `wt_maps_sidebar_map()` in the "Where it's spoken" section, under its heading and above the territories of origin and "also spoken in". The section isn't a mobile accordion, so the map stays open on phones.

**Territory and region pages** (`includes/template/place-maps.php` in the theme). Approved 2026-11-23; continent maps approved and built 2026-11-24 (plan 3.2 step 1c).

| Page | Map | Where |
|---|---|---|
| Territory | `wt_territory_map()`: the territory's one-place set, primary. The same file a language spoken only there uses, so 198 of 217 were already built. Then `wt_territory_region_map()`: its sub-region's map, the whole region active, with no new file | Sidebar: the territory's map under the title; the sub-region's map under the "Other territories in…" heading, above the list |
| Sub-region | `wt_region_map()`: every published territory in the term, all primary, framed together. Key: the hash of their record IDs | Sidebar, under the title, as on territory pages |
| Continent | `wt_region_map()` with `wt_continent_set()`: every territory in its sub-regions, all primary, in a fixed frame (`wt_continent_frames()`), grouped by sub-region. Key: the hash of the record IDs plus `-continent` | Sidebar, under the title |

`wt_place_map_sets()` registers both kinds through `wt_maps_extra_sets`, rebuilt from the posts on every export ("Territory: Guam", "Region: Polynesia"). Region terms have no record ID, so a region's set is named by what it draws: when a territory joins or leaves a sub-region its key changes, `doctor` lists the new set as unbuilt, and the old map is left over until removed by hand. Rebuild with `wp wt-maps export`, then `node build.mjs --only=<key>`. No visible caption: the page heading names the place. The SVG title (the hover tooltip) is "Map of Guam", "Map of Polynesia". Languages aren't drawn (they have no coordinates); the languages gallery below lists them. Subdivisions get maps when they get pages (6.8 phase B); Hawaii's set already exists. **Sizes (2026-11-23, local data):** the 41 added maps (19 territories, 22 sub-regions) took 2.7 MB, bringing `maps/` from 16.4 to 19.1 MB, and about 8 minutes to build with terrain tiles cached. Over budget: Andorra's hillshade (33.4 KB) and the Eastern Europe SVG (20.1 KB gzipped). **Australia & New Zealand** became a sub-region of Oceania on 2026-11-24 (it had been a top-level term since the import): its map is the 42nd.

**Sub-region maps, rings and halos** (2026-11-24). Region sets carry `halo: true` and `rings: detached` (`wt_place_map_sets()`). An island sub-region (land under 15% of the area its places span, the continent rule) is drawn as one island group: a halo, dots, no rings, with its country borders kept and no outline (the Caribbean, Micronesia, Melanesia, Polynesia). Elsewhere a small place gets a ring only when it doesn't touch another place on the map (within 1.5 units): Moldova and Transnistria in Eastern Europe are part of the main body.

**Locator globe placement** (2026-11-24). The corner is still picked by how few outline points fall near it, but when the globe would cover highlighted land (sampled every 4 units) it takes the corner that covers least, then the scale bar's bottom-left, moving the bar to the bottom right (Northern Europe). A globe a region map asked for on a frame over 30° is left off when every corner would cover a place. Changed two maps beyond the regions: Libya and Somaliland.

**Continent maps** (step 1c, 2026-11-24). Drawn as groups, one per sub-region, so a continent reads as four to six shapes:
- **Sets.** `wt_place_map_sets()` adds "Continent: Europe" with `view: continent`, a `frame` from `wt_continent_frames()`, and `groups` (record ID → its sub-region's position by slug). The view goes into the key (`wt_maps_set_key( $places, 'continent' )`), so a language whose map draws the same places never gets the continent's drawing.
- **Build** (`render.mjs`). A set with `groups` wraps each group's shapes in a `<g>`. The borders inside a group are stroked in the fill, so they vanish; the borders between groups and round them are one path of lines (`.grp-mesh`) at the weight of a territory border in that style (2026-11-24, replacing a thicker filter outline). Natural Earth neighbours share border vertices, so an edge used by two groups is between them, one used twice by the same group is inside it, and one used once is a coast or a border with a country off the map. No rings: every place under the ring threshold is a dot. A group whose land covers under 15% of the area its places span (the convex hull of their parts' centres) is an island group and gets one halo: a narrow field grown from its islands, joined along a minimum spanning tree so it is one shape (Polynesia), drawn under the land with a thin edge. Measured 2026-11-24: island groups 0–11% (Micronesia, Polynesia, Melanesia, the Caribbean), all others 21% or more. A count of small places failed both ways: at continent scale much of the mainland is under the ring threshold, and Cuba isn't.
- **Styles.** `.grp`, `.grp-mesh`, `.gh-fill`, `.gh-edge` and `.dot` in `wt-maps.styl`. The continent's own region borders are `--primary` at .7; with `regions` (continent maps, 2026-11-24) the land round the continent is grouped by sub-region too (`.lands--regions`: its country borders are stroked in the land colour, so they vanish) and its region borders are `.grp-mesh--out`, in the land-border colour at .6. So a continent map shows region borders only. Halo edges and dots are deep blue (`--edge`) with a parchment halo.
- **Frames** (`frame` in sets.json, tuned by eye 2026-11-24). `center` fixes the view's centre; without `radius` the territories are fitted round it with equal margins (Oceania); `radius` frames a circle instead, for continents too tall for 4:3 (Africa, the Americas); `roll` turns the view (Asia, −10°, so Japan isn't pushed up the right edge); `crop` keeps part of the fitted view (Europe: the middle 5/6, 20% closer); `include` adds points to the fit (Europe: Spain's Canary Islands, which a fit on main bodies leaves off the west edge). Europe is otherwise fitted, so Russia, continental in size, is drawn whole and the map reaches the Pacific, with even margins left and right.
- **Sizes.** Europe's SVG is over the 20 KB budget.
- **Australia & New Zealand** is a sub-region of Oceania (moved 2026-11-24, a term edit per environment; see plan 3.2).

**Caption.** The sidebar map has a visible `<figcaption class="wt-map__caption">` (the `caption` argument of `wt_map()`, or `caption="1"` on the shortcode). It is `Renderer::caption()`: the description, but it reads "Map showing Guam and the Northern Mariana Islands.", and origins come first, in the order the language lists them, then the other places alphabetically; past three places in a role the rest are counted instead of listed ("Map showing Spain, with 20 other places also marked."; "Map showing A, B, C, and 4 other places."), since the sidebar lists them all just below. The `<desc>` keeps the full list. Place names go through the theme's `wt_prefix_the()` ("the Northern Mariana Islands"), and sorting ignores the "the".

**Settings → Maps** (`wt_maps` option): turn the sidebar map off, and turn hover labels and clicks off. Both take effect without a deploy, and both default to on.

**Style.** One: Light full (2026-11-24; the other three are in the [archive](plan-archive.md#2026-11-24-maps-light-full-only)). `stylus/require/wt-maps.styl` in the theme. The tokens use the site palette (`$parchment`, `$blue()`, `$yellow`) and sit on both the figure and the defs, because hatch patterns take their styles from where they are defined.

**Tests.** PHP: `MapSetsTest`, `ShapeTableTest`, `PlaceMapsTest`, `InteractiveTest` (links, subdivisions, the JSON) and `RendererTest` (roles, description, caption, `decorate()`, the globe scale). Core isn't in the repository, so the `decorate()` tests run only where a local WordPress install sits at the repository root, and skip in CI. Build: `npm test` in `build/` runs `node --test` (`test/claims.test.mjs`, `test/claims-table.test.mjs`), in CI since 2026-11-23. `ClaimsReportTest` covers `doctor`'s claims checks, including that the committed table is clean. Not tested yet: framing, the globe and ring triggers, the raster steps; `doctor` checks the built output against the data, not the drawing.

## Hover labels and clicks

Plan 3.2, step 2a. Every map on a page labels a place when the pointer is over it and opens the place's page on click: language, territory and region pages alike. Off in Settings → Maps, the maps are still images, and without JavaScript they are the same images.

**Data.** The build tags every shape that stands for a place with `data-place`: the highlighted places and their rings, and on the base map each neighbouring country that is in the shape table. A place drawn as a whole country (`countries:PER`), or from a map unit that is its own country (`units:GRL`), tags that country. A country with a place drawn from one of its map units (France: `units:FXX`) is drawn unit by unit, each tagged with its own place, so French Guiana leads to French Guiana. `export` writes every shape-table place to `sets.json` for this, and a place added to the table tags its neighbours' maps only once they are rebuilt.

At render, `wt_map_places()` reads the tagged record IDs (`wt_maps_tagged_places()`) and resolves them with the place names in the one query it already ran (`wt_maps_place_links()`), by `_airtable_record_id`. The figure carries the result as `data-wt-map` JSON: record ID → `n` (label), `u` (URL), `v` (the page's name when it isn't the place's own). A subdivision has no page, so it leads to its nation's page and is labelled "Part of the United States". A place with no published page gets its shape-table name and no link. **Continent maps link sub-regions, never territories** (2026-11-24): the theme passes the groups (`wt_continent_link_groups()`): the continent's own sub-regions, then every other sub-region, so the countries round the continent open theirs (Turkey on Europe's map: Western Asia). The JSON maps each place on the map to its group number with the group's label and page (groups with nothing on the map are left out), and the script outlines the whole sub-region round its edge (a dilate filter, so the borders inside it don't show). A continent's island halos (the Caribbean, Micronesia, Melanesia, Polynesia) answer for their region too: a filter's glow takes no pointer, so the build adds an unpainted `.halo-hit` path through each halo's islands and corridors (`data-group`), under the land, which the interaction CSS strokes about as wide as the halo. On the island sub-region maps (the Caribbean, Micronesia, Melanesia, Polynesia; 2026-11-24) each **territory** gets its own hit path (`data-place`) through its islands, since its islands are dots a pixel or two wide: hovering the sea round Kiribati labels and opens Kiribati. The widest territories are drawn first, so a compact one keeps its edge where two overlap. The script anchors the label on a hit path but doesn't outline it.

**Script.** `assets/wt-maps.js` (deferred, enqueued only with a map). Mouse: hover shows the label and an outline drawn above every layer; click opens, Cmd/Ctrl-click opens a new tab. Touch: the first tap shows the label with an "Open" link; a second tap on the same place opens it. A contested area (`data-area`) shows its Natural Earth name and "Contested area", and never links. The SVG keeps `role="img"` and takes no keyboard focus: the place links next to the map are the keyboard and screen reader path, and hovering or focusing one outlines its place. The label is `aria-hidden`, since it repeats that list.

**Styles.** `stylus/require/wt-maps-interactive.styl`, apart from the map's look: raster layers, the scale and the globe take no pointer, and rings answer inside the ring. The outline is brand blue on a parchment casing.

**Cost (local data, 2026-11-24).** The links share the place-names query, so a map adds at most 2 queries (priming the neighbours' post caches; French and Ukrainian). The rebuild with neighbour tags added a median 83 bytes gzipped per SVG, 1.7 KB on French's six-place set; it pushed Russian's set (20.0 KB) and the Asia continent map (20.6 KB) just over the 20 KB budget. The JSON is 0.15–0.3 KB gzipped with places only, 0.5 KB for Ukrainian's neighbours, 4 KB for French's. The script is 2.7 KB gzipped.

## Design decisions

From plan 3.2 (decided 2026-11-16 – 19), kept here as the reference for changes. Where a later decision in [plan 6.8](../plan.md#68-language-geography-across-airtable-make-and-wordpress) differs, 6.8 wins.

### Decided

| Question | Decision |
|---|---|
| Imagery or vector | **Vector** country shapes, colored by CSS, with a raster terrain layer cut to the coast. Satellite tiles are licensed, heavy, can't highlight a single country, and lose their detail at sidebar width. |
| Rendering | **Ahead of time, no JavaScript.** SVG files made by a build script and placed inline by PHP. No tile server, no API key, no request to a third party. |
| Where the code lives | **A plugin**, `wt-maps`, following the `wt-gallery` pattern: a PHP API plus a shortcode. **In this repo** (2026-11-17), with the generated maps committed beside it, so a territory change, its rebuilt maps and the `doctor` output land in one PR. |
| Style | **Light full only** (2026-11-24, with organizational buy-in). It was a setting among four (2026-11-17: Light full, Light focused, Dark full, Dark focused; Light focused at launch, Light full from 2026-11-23). The other three, their tokens, the fog and their hatches are retired; the [archive](plan-archive.md#2026-11-24-maps-light-full-only) keeps their look notes. |
| Dark mode | **Not supported** (2026-11-17): the site has no dark mode, so the maps have one look, on parchment. |
| Territory coverage | **A cheap, steady review process over a complete list at launch** (2026-11-17). Launched with a set of content changes (the [archive](plan-archive.md) lists them); add the remaining ISO 3166-1 entries and map focuses later, one small reviewed change at a time (see "Territory review process"). |
| Labels and caption | **No labels, and no caption from the plugin.** Any text around the map is composed by the theme template (2026-11-17); the summary sentence above it already says where the language is spoken. The SVG carries the same text as its accessible description. |
| Projection | **Lambert azimuthal equal-area, centered on each map's territories.** Areas are exact, and shapes stretch only with distance from the center: typically 1–5% over the highlighted territory. An Albers equal-area conic was tested for continent-sized countries and rejected, because over each country's own land the azimuthal distorts less every time (Russia: 3.0% median and 12.6% worst, against 3.4% and 28.8%; Canada: 6.4% worst, against 36.6%). Large figures only appear in the empty corners of very wide frames. A Waterman butterfly suits a whole-world map, not a locator. |
| Disputed borders | **Neutral.** Since the claims spec (2026-11-26), an active contested area (one the language's territories claim) shows their coloured relief under a brand-red hatch (`$red`, 1.8 lines on 5 units) with a dashed edge, so a Ukrainian page shows Crimea as contested; every other contested area is drawn as plain land with an ordinary border, assigned to no one. Before, every area was hatched, and the hatch took the highlight colour when claimed. Committed 2026-11-17: the plugin draws only this view, with no switch. See "Contested areas are never hidden" under Look for how framing and the fog treat them. Natural Earth's default (control today) and its US view were compared in the prototype and dropped; its UN view is empty. |
| Link from territory to shape | **A shape table in the plugin** (`wt-maps/data/shapes.json`), keyed on Airtable record IDs and holding Natural Earth codes (`units:GAZ`, `units:WEB` for Palestine). Replaced the planned ACF "Map shape" field (2026-11-19), so a shape change is reviewed with its rebuilt maps. |
| France | **Metropolitan France only** (`FXX`), because its overseas departments are territories of their own. |
| What counts as a territory | **An officially assigned ISO 3166-1 place** (6.8, 2026-11-19). Greenland, Guam, the Northern Mariana Islands and the French overseas departments are territories; Hawaiʻi, Alaska, Wales and Scotland are subdivisions, and so are non-ISO states such as Abkhazia and Kosovo. |
| A language spoken in part of a territory | **Its subdivisions** (6.8). For each territory, the map draws the subdivisions the language lists there, else the whole territory (Hawaiian → Hawaii; Tibetan → Tibet AR and four Indian states). The plugin translates each subdivision to a Natural Earth code through the shape table. Until the province-level layer and the subdivision data land (6.8 Steps), a curated focus list in the shape table covers the launch cases. |
| Languages in several territories | **Fit the frame to all of them.** The prototype showed this works (Russian), so there are no separate region maps for languages. |

### Which countries to highlight

On local data, 7,536 language pages list exactly one territory, 44 list more than one, and none list zero.

- **Primary:** territories that are also the language's origins (`nations_of_origin` links). This is the same split the language page makes between "traditionally spoken in" and "also spoken in". If no territory is also an origin, every territory is primary.
- **Secondary:** the other territories, highlighted more lightly.
- **Context:** the neighboring countries.
- Don't add a "primary territory" field: `nations_of_origin` is already structured links (#671).

### Look

- **Terrain shading** on top, at a resolution matched to each frame. The land hillshade is computed from AWS Terrain Tiles (open elevation data, terrarium encoding; credit its sources per the Tilezen attribution list). The build picks the tile zoom from the frame's ground distance per pixel: zoom 3 for Russia at 13 km a pixel, zoom 10 for Malta at 195 m. It samples the tiles through the inverted d3 projection and shades from the north-west at 45°. Vertical exaggeration grows with pixel size (×2 for Malta, ×18 for Russia). Flat ground maps to neutral gray, and the result is cut to the vector coastline and blended with `mix-blend-mode: hard-light` (soft-light was tried and disappears on light fills). Natural Earth's SR_50M, used first, is fixed at about 3.7 km a pixel and went soft on small frames. The images ship at 600×450, quality 30: every test map is under 30 KB. Tiles are cached by the build, never fetched by the site.
- **Sea floor stays on Natural Earth** (GRAY_50M_SR_OB). The tiles' bathymetry has steps of several hundred metres between sources (near the antimeridian: Fiji, Tuvalu), and hillshading turns them into straight seams. The sea is background, so its softer resolution suits it.
- **Ocean floor, kept 2026-11-16.** Natural Earth's GRAY_50M_SR_OB carries seafloor relief: continental shelves, trenches, ridges and fracture zones. The build maps deep ocean to neutral gray, cuts the image to the sea, and blends it over the sea blue with `hard-light` at 65% opacity, fainter than the land shading so the coast stays the strongest edge. It adds 5–29 KB per page at 2×, less at the shipping size.
- **Secondary territories** are told apart by treatment, not tint: a pale fill with a parchment outline. Tints alone gave 1.48:1 between primary and secondary.
- **Contrast floor:** every mark that carries meaning reaches 3:1 against what it sits on (WCAG non-text contrast), and none relies on colour alone.
- **No compass.** A north arrow was tried and removed: readers assume north is up, and on these maps it is along the central meridian, so the arrow repeats that on most maps and leans on the wide ones (Russia about 30°), where it reads as a mistake. The interactive maps in step 2 can use MapLibre's north indicator, which turns with the map.
- **Scale bar, added 2026-11-17.** An abstract bar, no ticks: one round length (1, 2 or 5 × a power of ten km) at most 80 units long, labelled in km, bottom left. Measured at the centre of the map; an equal-area projection keeps area, not distance, so it is a guide to size, not a ruler (Russia's edges are off by up to 12%). Decorative: `aria-hidden`, since the description already names the place. Hidden where shapes stretch more than 5% anywhere in the frame (Russia, China, Greenland): OS leaves scale bars off continent-scale maps, because the scale isn't the same across them.
- **Contested areas are never hidden, cut or cropped.** Leaving one out reads as a political statement (Crimea cut off on a Ukrainian page says it isn't Ukraine's concern), so three rules apply:
  1. **A contested area that one of the language's territories claims** (the claims table says so; see [Claims table](#claims-table)) is treated like the territory: always inside the frame. Natural Earth names claimants by country, so a country's claim belongs to its part nearest the area (`build/claims.mjs`, 2026-11-23): the Lawa Headwaters on the Suriname border are French Guiana's, not metropolitan France's, Réunion's or Mayotte's. A place drawn as the whole country (Russia, the United Kingdom) holds all its claims. Before this, France's claim pulled South America into 15 maps (France alone, Réunion, Mayotte, Southern Africa, Western Europe, the Caribbean, and French-speaking sets). Ukrainian: Crimea, Donetsk, Luhansk. Moroccan: Western Sahara. Kashmiri: all of Kashmir and the India–China border areas. Hebrew: the Golan Heights. Yue: Taiwan and the India–China border areas. Russian: Crimea and the Kurils.
  2. **A contested area near the places is drawn whole**, as plain land since the claims spec: within about 30 units of a territory or a claimed area it goes in the claims layer (Transnistria on a Ukrainian page, the West Bank and Gaza on a Hebrew page). This was the fog's reveal in the focused styles; the build still records it as `revealed`, for the contested-areas diff.
  3. **The rule is symmetric.** It follows claims, not control, so a Russian page shows Crimea hatched as contested exactly as a Ukrainian page does. Nothing is drawn as belonging to either side.
  - **Who claims what comes from the claims table** (below), whose first layer is Natural Earth 5.1.2's, with our reviews on top (Russia's 2022 claims to the four oblasts, the end of Nagorno-Karabakh).
- **Locator globe on region maps, 2026-11-23.** A set can ask for the globe whatever its frame (`globe: true` from `wt_maps_extra_sets`); every sub-region map does, since Polynesia or Micronesia is islands in an empty sea. Languages and territories keep the corner-radius rule. On territory pages the globe is drawn 20% larger: `wt_map_places( array( 'inset' => 1.2 ) )` scales it from its frame corner at render time, so the shared SVG is unchanged.
- **Locator globe, added 2026-11-17.** OS's guidance is that a locator map shows the area within its wider region. For readers who don't know where a mid-sized country is, any frame with a corner radius under 30° gets an orthographic globe about 65px across at a 300px sidebar (radius 44 in the 400-unit frame; enlarged from about 50px on 2026-11-17 after Kristen found it hard to see). That covers Malta, Fiji, Tuvalu, Hawaiʻi, Guam, Georgia, Israel, Ukraine, Somalia, Morocco, Peru and Greenland; it leaves out India (35°), China and Russia, which are their own context. The globe is centred on the map. Small frames get a ring on the spot; larger ones get the map's own view outlined, as OS advises for marking an inset's area. It takes whichever of the top-right, top-left and bottom-right corners the language's territories and claims crowd least (the scale bar has bottom-left), and is `aria-hidden`. Drawn from Natural Earth 1:110m land with no borders: 2–4 KB raw. **One treatment in every style** (chosen 2026-11-17 from five variants compared at sidebar width): the brand globe, with the muted blue sea and `$blue(50)` land on a parchment halo. Small places get a filled yellow dot with a thin dark edge, the least noisy mark at about 50px. Larger frames get their view outlined in yellow on a dark underline. On the light map the globe flips its shades: a pale sea (`#d6cdee`) and brand-blue land, with a thin deep-blue rim. That keeps the classic dark-land figure and sits in the light page's key, while the colours and marks stay shared. Rejected: a grey globe with a blue mark, which was dark on dark for inland places, and a cased ring, which was too noisy on the Hebrew map.
- **Shaded (Light full), revised 2026-11-17.** The language's territories show the coloured relief in place of a blue tint, with a brand-blue edge, dashed for secondary. **Softened 2026-11-24:** the hillshade is 40% opacity with a half-strength sepia tone (`sepia(.5)`; full sepia was too brown), so land outside the places reads as warm parchment rather than black shadow. The places keep their depth from the relief's own shading (HYP_50M_SR).
- **Tokens** in a `wt-maps` Stylus partial built from the site palette, so the colors live in one place.
- **Size:** full sidebar width (about 230–390px), with a fixed 4:3 aspect ratio so the layout doesn't shift as the page loads.

## Changing territories

### Territory-change checklist

A territory touches up to four places. Change all of them together, or the map, the summary sentence or the territory pages drift apart.

1. **The territory post** (WordPress → Territories). Rename the title; leave the slug alone unless the URL must change, and if it does, add a redirect from the old slug. Unpublish rather than delete, so the old URL can redirect.
2. **Language links.** Edit the language's Territories field, or the territory's Languages field. The relationship is two-way, so either side updates both.
3. **The territory in Airtable.** `nations_of_origin` holds links to territory posts, matched by Airtable record ID (#687). Rename the Airtable record and the WordPress territory together; the Airtable change propagates to every linked language.
4. **The shape table** (`wt-maps/data/shapes.json`): the Natural Earth code for the territory's record ID. A territory without one shows no map, never a wrong one.

### Territory review process

Coverage grows by small changes, not a big push, so the process has to stay cheap enough to run for a single territory and safe enough to run without a cartographer.

- **One request, one record.** A territory or map-focus request is a GitHub issue from a template: the place, the languages it affects, the Natural Earth code, and a source for the claim if it is contested. The issue is the queue and the audit trail.
- **One change, one PR.** The WordPress edits and the Airtable edits follow the checklist above. The regenerated maps and the `doctor` output then go in a single PR, because the build lives in this repo.
- **The review is reading two reports.** `wp wt-maps doctor` must be clean (no missing shapes, no unmatched `nations_of_origin`, no languages left without a territory by the change). The PR shows the contested-areas diff: every contested area a changed map now reveals, frames or hatches, against the last release. A change that touches no contested area needs one reviewer; one that does needs a second (the site lead or Kristen, whoever didn't make the change), and a source in the issue.
- **Batch the slow parts.** Data syncs and production writes happen at most once a month, for whatever has been merged since. The claims table gets its yearly review then too, owned by Frederico (since 2026-11-25), and after any news that moves a border.
- **Backlog to start from:** ISO 3166-1 places with no territory page yet; languages with neither territories nor origins, which show no map (16 on local data); and subdivisions for Alaskan languages, Welsh and Scottish Gaelic.

The plugin adds a check that turns this list into a report: `wp wt-maps doctor` lists territories without a shape, shape-table entries that match no place, languages whose places include one with no shape, and map sets with no built map. Run it after any territory change.

## Claims table

Who claims each contested area lives in `data/claims.json`, not in Natural Earth's point-of-view fields. **To change it, follow [maps-claims-guide.md](maps-claims-guide.md)**: the pages, buttons, reviewers and how to work with Claude. The rules below come from the claims spec (approved 2026-11-25; its history and decisions are in the [archive](plan-archive.md)); `build/claims-table.mjs` is the code.

**The rules**
- A *claim* is a government's formal, current assertion of sovereignty in an official act (law, official map, UN statement). A claim not pressed is *dormant*, one given up or overtaken is *ended*; neither counts, but both stay in the table as the record.
- Only the claims table says who claims an area, and every assertion in it names its source. *Control* (who administers an area) is not stored and never drawn.
- Every territory is drawn as its *undisputed part*: its shape minus every contested area. A *wholly contested place* (Taiwan, Kosovo, Western Sahara, Somaliland, Northern Cyprus, Abkhazia, South Ossetia, Transnistria) is drawn as itself, and the other side's claim is a row.
- Symmetric: every claimant sees an area the same way. On a given map an area is *active* when a place on the map claims it or is it; every other area is *inactive*.
- One row and one shape per area. Keyed on record IDs; a second build is a no-op.
- Left out on purpose: East Jerusalem (no Natural Earth shape; drawing one is a political call), lines of control, historical and maritime claims, per-viewer points of view, and labels that name claimants or say who administers an area.

- **Layers.** The table is an ordered list of layers. Layer 1 is Natural Earth's: one row per contested area (its code, name, and claimants as record IDs), written by `build/seed-claims.mjs` and never edited by hand. A country's claim goes to its place nearest the area (`claims.mjs`), and a place never claims itself. Each later layer is a review: a list of changes (`add` or `drop` claimants, or a `status`), each with a `reason` and a `source`, applied in order. A review never edits layer 1, so a Natural Earth upgrade replaces layer 1 alone.
- **Statuses.** `claimed` (the default) draws as contested. `ended` draws the area as part of its territory. `duplicate`, with `of`, draws it once, as the area it repeats.
- **The build** computes the effective claims and never stores them. An area with no row stops it: run `node seed-claims.mjs --write`.
- **`doctor`** fails on a claimant that is not in the shape table, a change with no reason, or one naming an area layer 1 lacks. It lists changes that change nothing, which is how an override shows it has gone stale after an upgrade.
- **Undisputed parts** (`build/undisputed.mjs`, rule 3). Before anything is drawn or framed, the build cuts every contested area out of every Natural Earth shape, at both scales, with `polygon-clipping`. Natural Earth's shapes follow control (Russia's takes in Crimea, Morocco's most of Western Sahara), so without this a claimant's outline took sides. A shape keeps an area it stands for: its own code, or a place drawn from it that is the area (Taiwan, Palestine's West Bank and Gaza). An overlap under a fifth of the area is a sliver where 1:10m and 1:50m borders disagree, and is left alone. Natural Earth's W. Sahara country is all contested, so it ends up empty; the area is drawn on top.
- **Editing.** Frederico Andrade edits and reviews the table, in the repo, and owns the yearly review. A change reaches the site through a build PR.

**Review layer `wikitongues-2026-11-25`:**

| Change | Areas | Why |
|---|---|---|
| Taiwan dropped | Arunachal Pradesh and the six other India–China border areas | The Republic of China's constitutional claim, carried by Natural Earth's Taiwan view, is dormant. Taiwan's map now frames on Taiwan |
| Duplicate of B19 | SAH | Natural Earth's W. Sahara map unit is only the Polisario-held zone east of the Berm. The Western Sahara place is drawn from B19, the whole territory |
| Duplicate of CYN, of SOL | B20, B30 | Same shapes as the Northern Cyprus and Somaliland map units |

**Kashmir names:** Natural Earth's "Jammu and Kashmir" (B05) is only the Indian-administered part, and its "Gilgit-Baltistan" (B08) is all of the Pakistan-administered part, Azad Kashmir included. Both are claimed by India and Pakistan. B08's label is open (plan 3.2, the Kashmir naming question).

**Areas from admin-1:** a review change with `"shape": "admin1:<ISO 3166-2 code>"` and a `"name"` adds a contested area Natural Earth has no admin-0 shape for, from `ne_10m_admin_1_states_provinces` (fetched by `fetch.mjs`; read only when the table names one). The area's code is the ISO code. The 2022 oblasts are added this way (UA-14, UA-09, UA-23, UA-65), and Natural Earth's 2014 areas inside them (C02, C03) are marked duplicates of them.
