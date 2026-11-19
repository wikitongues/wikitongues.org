# Maps (`wt-maps`)

Locator maps for language pages, plan item 3.2. The full spec, with the style, projection and contested-area decisions, is the 3.2 section of `plan.md`. This file describes what is built.

Built so far: the shape table, the `doctor` and `export` commands, the build (all 237 maps, 2026-11-19), the renderer `wt_map()` with the `[wt_map]` shortcode, the styles, the settings page, and the sidebar placement. Not yet built: the claims table, credits, and the review tooling.

## Map sets and the build

A map is built once per **set of places**, not per language. A language's set is its focus places when it has a focus, and otherwise its published territory links. The places are kept as record IDs, because the sync creates places separately on each environment, so their post IDs differ. A set with one place is keyed by that place's record ID (`recguDPgV3cIXLO34`). A set with several places is keyed `set-` plus 12 hex characters of the SHA-1 of the sorted IDs (`wt_maps_set_key()`, in PHP only). A language whose set includes a place with no shape gets no map, never a partial one.

```bash
cd wp-content/plugins/wt-maps/build
npm ci
node fetch.mjs                      # once: Natural Earth (at the table's version) into build/cache/
wp --allow-root wt-maps export      # read-only: writes build/cache/sets.json
node render.mjs --estimate          # count and size every set, writing nothing
node build.mjs --only=<key>,<key>   # or no --only for every set
```

| Step | Writes |
|---|---|
| `render.mjs` | `build/cache/stage/<key>.svg`, and the set's frame in `stage/frames.json` (places, projection, contested areas claimed and revealed, size) |
| `relief.mjs` | the coloured relief reprojected into the frame (cache) |
| `dem.mjs` | the land hillshade from AWS Terrain Tiles, at a zoom matched to the frame (tiles cached in `build/cache/dem/`) |
| `seafloor.mjs` | the ocean-floor shading (cache) |
| `mask.mjs` | `maps/relief/`, `maps/shade/` and `maps/sea/<key>.webp`, cut to the SVG's own coastline, and then **publishes** the map: it copies the SVG to `maps/` and adds its entry to `maps/index.json` |

Every raster step reads the projection that `render.mjs` staged, so an SVG and its images always line up. A map reaches `maps/` only after its three images are written, so an interrupted or unfinished build never leaves an SVG without its terrain: the site would show such a map with no relief and, in the focused styles, with no fill. A rebuild with the same inputs writes byte-identical files.

`maps/` is committed and deployed. `build/` is committed but not deployed (`.rsync-filter`), and `build/cache/` is gitignored.

**What the renderer adds** (`WT\Maps\Renderer`, see below). The SVG is shared by every language with that set, so it has no primary or secondary styling. Highlighted shapes and rings have `data-place` (the record ID). A contested area that a place claims has `hatch-claimed` and `data-claimants` (the record IDs claiming it). PHP adds the `primary`/`secondary` classes per language, makes the relative image hrefs (`shade/<key>.webp`) absolute, and writes the language's own `<title>` and `<desc>`. The page needs the shared `#fog`, `#fog-edge` and hatch definitions, which are in the prototype template for now.

**Sizes (2026-11-19, local data).** There are 237 maps for 7,582 languages; 40 have several places, and 507 languages have no territory link. With images they come to 10.8 MB, and the SVGs alone to 1.39 MB gzipped (median 6 KB). A full build took about 50 minutes on the first run, mostly fetching terrain tiles; with the tiles cached, about half that. The publish step encodes each image at the first quality that fits the 30 KB budget, stepping down and then shrinking to 600×450, and records the bytes in `index.json`. Two maps are still over budget. Tajikistan's hillshade is 32.9 KB even at the lowest step. French's six-place set (Andorra, Djibouti, France, Lebanon, Martinique, Monaco) has a 20.9 KB SVG.

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

`wt_map( $args )` returns a language's map as `<figure class="wt-map wt-map--<style>">`, or `''` when no map has been built for its set of places. The first map on a page is preceded by the shared `<svg class="wt-map-defs">` (hatches, the fog filter and its edge fade), which every map's CSS refers to by ID.

| Argument | Default | |
|---|---|---|
| `language` | the current post | language post ID |
| `territories` | the language's focus, else its territory links | place post IDs to draw instead |
| `primary` | its `nations_of_origin` | post IDs to highlight as primary |
| `class` | | extra class on the figure |
| `style` | the setting | `light-focused`, `light-full`, `dark-full`, `dark-focused` |

`[wt_map language="123" territories="1,2" primary="1" style="dark-full"]` takes the same arguments, with comma-separated IDs.

**Roles.** Places that are also countries of origin are primary and the rest secondary. If none is a country of origin, all are primary (Chamorro: nation United States, map of Guam and the Northern Mariana Islands). A contested area's hatch follows its strongest claimant on the map.

`WT\Maps\Renderer::decorate()` adds the roles, the absolute image URLs, and the language's `<title>` ("Where Hawaiian is spoken", from `standard_name`) and `<desc>`, all through `WP_HTML_Tag_Processor`. The result is cached in the object cache (group `wt_maps`), keyed on the set, the roles, the text, the class, `Renderer::VERSION` and the SVG's modification time. Bump `Renderer::VERSION` when `decorate()` changes its output.

**Placement.** `modules/languages/meta--languages-single.php` prints `wt_maps_sidebar_map()` in the "Where it's spoken" section, under its heading and above the territories of origin and "also spoken in". The section isn't a mobile accordion, so the map stays open on phones.

**Caption.** The sidebar map has a visible `<figcaption class="wt-map__caption">` (the `caption` argument of `wt_map()`, or `caption="1"` on the shortcode). It is `Renderer::caption()`: the description, but a role with more than three places is counted instead of listed ("Map centred on Spain, with 20 other places also marked."), since the sidebar lists them all just below. The `<desc>` keeps the full list. Place names go through the theme's `wt_prefix_the()` ("the Northern Mariana Islands"), and sorting ignores the "the".

**Settings → Maps** (`wt_maps` option): turn the sidebar map off, and pick the style. Both take effect without a deploy. The defaults are on and Light focused.

**Styles.** `stylus/require/wt-maps.styl` in the theme. The rules were translated mechanically from the prototype page's CSS, so each style can be compared rule for rule with what was reviewed. The tokens use the site palette (`$parchment`, `$blue()`, `$yellow`) and sit on both the figure and the defs, because hatch patterns take their styles from where they are defined.

**Tests.** `RendererTest` covers the roles, the description, the caption and `decorate()`. Core isn't in the repository, so `decorate()` is tested only where a local WordPress install sits at the repository root, and the test skips in CI.
