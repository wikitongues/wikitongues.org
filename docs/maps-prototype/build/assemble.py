import json, gzip, os, sys, html
S = sys.argv[1]
R = json.load(open(f'{S}/data/rendered.json'))
M = json.load(open(f'{S}/data/matches.json'))
tpl = open(f'{S}/build/template.html').read()
e = html.escape

def kb(n): return f'{n/1024:.1f} KB'
def relief_kb(iso):
    p = f'{S}/site/relief/{iso}.webp'
    return os.path.getsize(p) if os.path.exists(p) else 0

def fig(r, caption=True):
    cap = f'<figcaption>{e(r["caption"])}</figcaption>' if caption else ''
    return f'<figure class="wt-map">{r["svg"]}{cap}</figure>'

cards = []
for r in [x for x in R if x['section'] == 'gallery']:
    gz = len(gzip.compress(r['svg'].encode()))
    rk = relief_kb(r['iso'])
    sp = f'{S}/site/shade/{r["iso"]}.webp'
    sk = os.path.getsize(sp) if os.path.exists(sp) else 0
    cls = lambda over: ' class="over"' if over else ''
    cards.append(f'''<article class="card"><span class="eyebrow">{e(r["iso"])}</span><h3>{e(r["lang"]["name"])}</h3>{fig(r)}
<p class="why">{e(r["lang"]["why"])}</p>
<p class="stats"><span{cls(gz > 20480)}>SVG {kb(gz)} gz</span> · <span{cls(sk > 30720)}>shaded +{kb(sk)}</span> · <span{cls(rk > 30720)}>relief +{kb(rk)}</span></p></article>''')

POV = {'defacto': 'De facto', 'us': 'US view', 'neutral': 'Neutral'}
rows = []
for c in json.load(open(f'{S}/data/cases.json'))['disputes']:
    cells = ''.join(f'<div><span class="mlabel">{POV[p]}</span>{fig(next(x for x in R if x["id"] == c["iso"] + "-" + p), caption=False)}</div>' for p in ['defacto', 'us', 'neutral'])
    rows.append(f'<div class="drow"><div class="dlabel"><span class="eyebrow">{e(c["iso"])}</span><h3>{e(c["name"])}</h3><p>{e(c["why"])}</p></div>{cells}</div>')

STATUS = {  # title -> (pill class, label, note)
    'Sahrawi Arab Democratic Republic': ('p-decide', 'Decide', 'Our title names the state; the shape is all of Western Sahara, most of which Morocco administers. Rename the territory "Western Sahara"?'),
    'Nagorno-Karabakh': ('p-decide', 'Decide', 'Natural Earth 5.1.2 still draws the pre-2023 area. Azerbaijan has controlled it since September 2023, and the republic dissolved in January 2024.'),
    'France': ('p-decide', 'Confirm', 'Metropolitan France only, because Guadeloupe, Martinique, Réunion, Mayotte and French Guiana are territories of their own here.'),
    'United States': ('p-decide', 'Decide', 'Hawaiian is filed under the United States, so its map highlights the mainland. Hawaii isn’t a territory of its own.'),
    'Denmark': ('p-decide', 'Decide', 'Greenlandic is filed under Denmark. Greenland isn’t a territory, though Natural Earth has its shape.'),
    'Federated Staes of Micronesia': ('p-fix', 'Fix title', 'Typo: “Staes”.'),
    'Naoero': ('p-fix', 'Fix title?', 'Nauruan name for Nauru. Every other territory uses its English name.'),
    'Abkhazia': ('p-confirm', 'No ISO code', 'Breakaway from Georgia.'),
    'South Ossetia': ('p-confirm', 'No ISO code', 'Breakaway from Georgia.'),
    'Transnistria': ('p-confirm', 'No ISO code', 'Breakaway from Moldova.'),
    'Northern Cyprus': ('p-confirm', 'No ISO code', 'Recognized only by Turkey.'),
    'Somaliland': ('p-confirm', 'No ISO code', 'Self-governing; claimed by Somalia.'),
    'Kosovo': ('p-confirm', 'No ISO code', 'XKX is the widely used user-assigned code.'),
}
shape = lambda refs: ' + '.join(f'{c}' for _, c in refs) or '—'
layer = lambda refs: ', '.join(sorted({l for l, _ in refs}))
hand = []
order = list(STATUS) + [m['title'] for m in M if m['how'] == 'override' and m['title'] not in STATUS]
for t in order:
    m = next(x for x in M if x['title'] == t)
    pill, lab, note = STATUS.get(t, ('p-ok', 'OK', m['note'] or ''))
    hand.append(f'<tr><td>{e(t)}</td><td class="code">{e(shape(m["refs"]))} <span class="note">{e(layer(m["refs"]))}</span></td><td class="code">{e(m["iso"] or "—")}</td><td><span class="pill {pill}">{e(lab)}</span></td><td class="note">{e(note)}</td></tr>')

allrows = ''.join(f'<tr><td>{e(m["title"])}</td><td>{e(", ".join(m["region"]))}</td><td class="code">{m["langs"]}</td><td class="code">{e(shape(m["refs"]))}</td><td class="code">{e(m["iso"] or "—")}</td></tr>' for m in M)

DEC = [
    ('p-decide', 'Map style', 'Pick one in the toolbar. <strong>Flat</strong> costs about 3 KB per page. <strong>Relief</strong> adds 3–74 KB: Yue Chinese, Russian and Kashmiri are over the 30 KB budget at this resolution, and a 600px image at lower quality would fit. <strong>Outline</strong> is the lightest look.'),
    ('p-decide', 'Caption', 'The summary sentence above the map already says where a language is spoken, so a visible caption repeats it. The SVG carries the same text for screen readers either way.'),
    ('p-ok', 'Disputed borders', '<strong>Recommend neutral.</strong> Disputed areas are hatched and assigned to no one; the hatch takes the highlight colour when a claimant is the language’s territory. It takes no side and still shows a Ukrainian speaker that Crimea is in question.'),
    ('p-decide', 'Where the link lives', '<strong>Recommend</strong> an ACF field on territories, “Map shape”, holding Natural Earth codes (<span class="mono">GAZ,WEB</span> for Palestine), backfilled from this table. Six territories have no ISO code, so ISO alone can’t drive the map. Fill <span class="mono">iso_code</span> and <span class="mono">wikidata_id</span> in the same pass where they exist.'),
    ('p-decide', 'Nagorno-Karabakh', 'Keep the territory and the pre-2023 shape, since the languages were documented there, or fold it into Azerbaijan. It is an editorial call.'),
    ('p-decide', 'Sahrawi Arab Democratic Republic', 'Rename to Western Sahara to match what the map shows, or keep the name of the state.'),
    ('p-decide', 'Places below country level', 'Hawaiian points at the United States and Greenlandic at Denmark. Adding Greenland as a territory is cheap. Hawaii would need sub-national regions, which is out of scope here.'),
    ('p-ok', 'Several territories', 'The spec said to switch to a region map. Fitting all highlighted territories directly works (see Russian), so <strong>drop the region-map rule</strong>.'),
    ('p-confirm', 'France', 'Draw metropolitan France only, because its overseas departments are separate territories.'),
    ('p-fix', 'Title fixes', '“Federated Staes of Micronesia” is a typo. “Naoero” is the only territory titled with its endonym.'),
]
dec = ''.join(f'<div class="dec"><span class="pill {p}">{"Decide" if p=="p-decide" else "Recommend" if p=="p-ok" else "Confirm" if p=="p-confirm" else "Fix"}</span><span class="q">{e(q)}</span><p class="a">{a}</p></div>' for p, q, a in DEC)

out = tpl.replace('{{CARDS}}', '\n'.join(cards)).replace('{{DISPUTES}}', '\n'.join(rows)).replace('{{HAND}}', ''.join(hand)).replace('{{ALL}}', allrows).replace('{{DECISIONS}}', dec)
open(f'{S}/site/maps.html', 'w').write(out)
print(len(out), 'bytes')
