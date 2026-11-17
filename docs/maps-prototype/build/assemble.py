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

def card(r):
    gz = len(gzip.compress(r['svg'].encode()))
    rk = relief_kb(r['iso'])
    sp = f'{S}/site/shade/{r["iso"]}.webp'
    sk = os.path.getsize(sp) if os.path.exists(sp) else 0
    cls = lambda over: ' class="over"' if over else ''
    return f'''<article class="card"><span class="eyebrow">{e(r["iso"])}</span><h3>{e(r["lang"]["name"])}</h3>{fig(r)}
<p class="why">{e(r["lang"]["why"])}</p>
<p class="stats"><span{cls(gz > 20480)}>SVG {kb(gz)} gz</span> · <span{cls(sk > 30720)}>shaded +{kb(sk)}</span> · <span{cls(rk > 30720)}>relief +{kb(rk)}</span></p></article>'''

cards = [card(r) for r in R if r['section'] == 'gallery']
# Disputed borders: the neutral view only, as cards like the gallery.
rows = [card(r) for r in R if r['section'] == 'disputes']

STATUS = {  # title -> (pill class, label, note)
    'Sahrawi Arab Democratic Republic': ('p-fix', 'Rename', 'Renaming to "Western Sahara", the UN territory name; SADR is the name of the government in exile. Decided 2026-11-17.'),
    'Nagorno-Karabakh': ('p-fix', 'Fold', 'Removing it from Armenian and unpublishing it: Azerbaijan has controlled the area since September 2023, and the republic dissolved in January 2024. Decided 2026-11-17.'),
    'France': ('p-confirm', 'Decided', 'Metropolitan France only, because Guadeloupe, Martinique, Réunion, Mayotte and French Guiana are territories of their own here.'),
    'United States': ('p-confirm', 'Decided', 'Hawaiian keeps the United States as its territory, with a map focus of Hawaiʻi (USH), so its map shows only the islands.'),
    'Denmark': ('p-fix', 'Add Greenland', 'Greenland is an autonomous territory with its own ISO code; adding it as a territory and moving Greenlandic there from Denmark. A territory type field (sovereign state, autonomous territory and so on) ships with the maps.'),
    'Federated Staes of Micronesia': ('p-fix', 'Fix title', 'Typo: “Staes”.'),
    'Naoero': ('p-confirm', 'Keep', 'The official English name since Nauru changed it in August 2026.'),
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

DEC = [  # (pill, label, question, answer); everything here was decided 2026-11-17
    ('p-ok', 'Decided', 'Map style', 'A WordPress setting, picked in the plugin; every style’s images ship, so switching needs no rebuild. Kristen leans Dark full, then Light full.'),
    ('p-ok', 'Decided', 'Caption', 'None from the plugin. The theme template composes any text around the map; the SVG carries a description for screen readers.'),
    ('p-ok', 'Decided', 'Disputed borders', 'Neutral only. Disputed areas are hatched and assigned to no one, and claimed areas are always framed and revealed.'),
    ('p-ok', 'Decided', 'Where the link lives', 'An ACF field on territories, “Map shape”, holding Natural Earth codes (<span class="mono">GAZ,WEB</span> for Palestine), backfilled from this table, with <span class="mono">iso_code</span> and <span class="mono">wikidata_id</span> filled in the same pass.'),
    ('p-ok', 'Decided', 'Nagorno-Karabakh', 'Fold it: remove it from Armenian and unpublish the territory.'),
    ('p-ok', 'Decided', 'Western Sahara', 'Rename “Sahrawi Arab Democratic Republic” to “Western Sahara”, the UN territory name, in WordPress and Airtable.'),
    ('p-ok', 'Decided', 'Places below country level', 'Greenland, Guam and the Northern Mariana Islands become territories. Hawaiian keeps the United States, with a map focus of Hawaiʻi.'),
    ('p-ok', 'Decided', 'Territory types', 'A type field on territories (sovereign state, autonomous territory, dependency, breakaway), shipped with the maps and shared with the language rights reports.'),
    ('p-ok', 'Decided', 'Several territories', 'Fit the frame to all of them (see Russian); no region maps.'),
    ('p-ok', 'Decided', 'France', 'Metropolitan France only, because its overseas departments are territories of their own.'),
]
dec = ''.join(f'<div class="dec"><span class="pill {p}">{lab}</span><span class="q">{e(q)}</span><p class="a">{a}</p></div>' for p, lab, q, a in DEC)

out = tpl.replace('{{CARDS}}', '\n'.join(cards)).replace('{{DISPUTES}}', '\n'.join(rows)).replace('{{HAND}}', ''.join(hand)).replace('{{ALL}}', allrows).replace('{{DECISIONS}}', dec)
open(f'{S}/site/maps.html', 'w').write(out)
print(len(out), 'bytes')
