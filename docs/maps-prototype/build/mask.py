"""Cut each relief image to the vector land shapes, so the sea shows the
page's own --sea colour instead of the raster's baked-in water."""
import json, re, subprocess, sys
S = sys.argv[1]
done = set()
for r in json.load(open(f'{S}/data/rendered.json')):
    iso = r['iso']
    if iso in done:
        continue
    done.add(iso)
    paths = ''.join(
        f'<path d="{d}"/>'
        for g in ('lands', 'hls', 'disputes')
        for d in re.findall(r'd="([^"]+)"', re.search(rf'<g class="{g}">(.*?)</g>', r['svg']).group(1))
    )
    svg = (f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 300" width="800" height="600">'
           f'<rect width="400" height="300" fill="#000"/><g fill="#fff" stroke="#fff" stroke-width=".4">{paths}</g></svg>')
    open(f'{S}/raster/{iso}-mask.svg', 'w').write(svg)
    subprocess.run(['magick', '-background', 'black', f'{S}/raster/{iso}-mask.svg', '-colorspace', 'gray', '-resize', '800x600!', f'{S}/raster/{iso}-mask.png'], check=True)
    subprocess.run(['magick', '-size', '800x600', '-depth', '8', f'rgb:{S}/raster/{iso}.rgb', f'{S}/raster/{iso}-mask.png',
                    '-alpha', 'off', '-compose', 'CopyOpacity', '-composite', '-quality', '70', '-define', 'webp:alpha-quality=60',
                    f'{S}/site/relief/{iso}.webp'], check=True)
    print(iso, end=' ')
