# Maps prototype

The scripts behind the language-page map prototype (plan item 3.2, November 2026). They are the starting point for the `wt-maps` plugin's build step, not production code. This folder lives under `docs/`, so it is never deployed.

## What each script does

| Script | Input | Output |
|---|---|---|
| `build/match.py` | `data/territories.json`, Natural Earth layers | `data/matches.json`: each territory's Natural Earth shape codes and ISO code. 189 match by name; 26 come from the `OVERRIDES` table. |
| `build/render.mjs` | `data/matches.json`, `data/cases.json` | `data/rendered.json`: one SVG per test language (and per disputed-border view), plus its projection. |
| `build/projection.mjs` | | The shared projection: the renderer and both raster builds use it, so vectors and images line up. Also measures shape distortion. |
| `build/relief.mjs` | `HYP_50M_SR` as raw RGB | Colored relief reprojected into each map frame. |
| `build/hillshade.mjs` | `SR_50M` as raw gray | Grayscale hillshade per frame, remapped so flat ground is neutral gray (for the `hard-light` blend). |
| `build/seafloor.mjs` | `GRAY_50M_SR_OB` as raw gray | Ocean-floor shading per frame: deep ocean (gray 89) mapped to neutral, contrast ×1.6, then cut to the sea with the inverted land mask. |
| `build/mask.py` | rendered SVGs | Land masks rasterized from the SVGs; cuts the relief images to the coastline. |
| `build/assemble.py` | everything above, `build/template.html` | `site/maps.html`, the prototype page. |
| `build/measure.mjs` | Natural Earth countries | Azimuthal vs conic distortion over large countries' own land. |

## Running it

Everything runs from this folder (`S` below is its absolute path). `ne/`, `raster/` and `site/` are downloads and output, and are gitignored.

1. Download Natural Earth v5.1.2 1:50m GeoJSON from `nvkelso/natural-earth-vector` into `ne/`: `admin_0_countries`, `admin_0_map_units`, `admin_0_breakaway_disputed_areas`.
2. Download `HYP_50M_SR`, `SR_50M` and `GRAY_50M_SR_OB` from naturalearthdata.com into `raster/`, `raster/sr/` and `raster/ob/`, then convert them to raw bytes:
   `magick HYP_50M_SR.tif -depth 8 rgb:hyp.rgb` and `magick SR_50M.tif -colorspace gray -depth 8 gray:sr.gray`, and the same for `GRAY_50M_SR_OB.tif` → `ob.gray`.
3. `cd build && npm ci`
4. `data/territories.json` is exported from WordPress (titles, slugs, regions, language counts). Re-export it with `wp eval` if territories change.
5. Run, in order:
   `python3 build/match.py $S`, `node build/render.mjs $S`, `node build/relief.mjs $S`, `node build/hillshade.mjs $S`, `node build/seafloor.mjs $S`, `python3 build/mask.py $S`, then mask the hillshades the same way (see `mask.py`), then `python3 build/assemble.py $S`.

GDAL isn't needed: the rasters are reprojected by inverting the d3 projection directly.
