"""Match Wikitongues territories to Natural Earth shapes.

Shape reference = (layer, code):
  countries  -> ADM0_A3 in ne_50m_admin_0_countries
  units      -> GU_A3   in ne_50m_admin_0_map_units
  breakaway  -> BRK_A3  in ne_50m_admin_0_breakaway_disputed_areas
A territory can need several refs (Palestine = Gaza + West Bank).
"""
import json, sys, unicodedata, re

S = sys.argv[1]
ne = lambda n: json.load(open(f'{S}/ne/ne_50m_admin_0_{n}.geojson'))['features']
countries, units, brk = ne('countries'), ne('map_units'), ne('breakaway_disputed_areas')
terrs = json.load(open(f'{S}/data/territories.json'))

def norm(s):
    s = unicodedata.normalize('NFKD', s or '').encode('ascii', 'ignore').decode().lower()
    s = s.replace('&', 'and').replace('’', "'")
    s = re.sub(r'\b(the|of|islands?|is\.)\b', ' ', s)
    s = re.sub(r'[^a-z]+', ' ', s)
    return ' '.join(s.split())

NAME_KEYS = ['NAME', 'NAME_LONG', 'ADMIN', 'NAME_EN', 'FORMAL_EN', 'NAME_ALT', 'GEOUNIT', 'SUBUNIT', 'BRK_NAME', 'NAME_SORT']
index = {}
def add(layer, code, props):
    for k in NAME_KEYS:
        v = props.get(k)
        if isinstance(v, str) and v:
            index.setdefault(norm(v), []).append((layer, code))

for f in countries: add('countries', f['properties']['ADM0_A3'], f['properties'])
for f in units:     add('units', f['properties']['GU_A3'], f['properties'])

# Decisions that a name match can't make. Each one is shown in the prototype.
OVERRIDES = {
    # Name variants / typos in our data
    'Naoero': ([('countries', 'NRU')], 'Name is the Nauruan endonym; NE says Nauru', 'rename?'),
    'Federated Staes of Micronesia': ([('countries', 'FSM')], 'Typo in our title ("Staes")', 'fix title'),
    'Cape Verde Islands': ([('countries', 'CPV')], 'NE uses Cabo Verde', None),
    'Russian Federation': ([('countries', 'RUS')], None, None),
    'Czech Republic': ([('countries', 'CZE')], 'NE uses Czechia', None),
    'São Tomé e Príncipe': ([('countries', 'STP')], 'Title is Portuguese', None),
    'Eswatini': ([('countries', 'SWZ')], None, None),
    'Côte d’Ivoire': ([('countries', 'CIV')], None, None),
    'Republic of the Congo': ([('countries', 'COG')], None, None),
    'Democratic Republic of the Congo': ([('countries', 'COD')], None, None),
    'Vatican City': ([('countries', 'VAT')], None, None),
    'Wallis and Futuna': ([('units', 'WLF')], None, None),
    'United States': ([('countries', 'USA')], 'Includes Alaska, Hawaii; territories like Puerto Rico and Guam are separate NE units and not in our list', 'framing'),
    # France: our list has the overseas departments as their own territories,
    # so "France" should mean metropolitan France only.
    'France': ([('units', 'FXX')], 'Metropolitan France only — Guadeloupe, Martinique, Réunion, Mayotte, French Guiana are their own territories here', 'confirm'),
    # Breakaway states: NE draws these as disputed overlays, not countries
    'Abkhazia': ([('breakaway', 'B35')], 'Breakaway from Georgia', 'disputed'),
    'South Ossetia': ([('breakaway', 'B37')], 'Breakaway from Georgia', 'disputed'),
    'Transnistria': ([('breakaway', 'B36')], 'Breakaway from Moldova', 'disputed'),
    'Nagorno-Karabakh': ([('breakaway', 'B38')], 'NE 5.1.2 still draws the pre-2023 Artsakh area; it has been under Azerbaijani control since Sept 2023 and the republic dissolved Jan 2024', 'disputed'),
    'Northern Cyprus': ([('units', 'CYN')], 'Recognized only by Turkey', 'disputed'),
    'Somaliland': ([('units', 'SOL')], 'Self-governing, claimed by Somalia', 'disputed'),
    'Sahrawi Arab Democratic Republic': ([('units', 'SAH')], 'Our title names the state (SADR); the shape is all of Western Sahara, most of which Morocco administers', 'disputed'),
    'Kosovo': ([('units', 'KOS')], 'No ISO 3166 code; XKX is the common user-assigned code', 'disputed'),
    'Palestine': ([('units', 'GAZ'), ('units', 'WEB')], 'Two NE units: Gaza and the West Bank', 'disputed'),
    'Taiwan': ([('units', 'TWN')], 'NE classes it Admin-1 under China in some views', 'disputed'),
    'Hong Kong': ([('units', 'HKG')], 'Special administrative region of China', None),
    'Macao': ([('units', 'MAC')], 'Special administrative region of China', None),
}

ISO = {}
for f in units + countries:
    p = f['properties']
    code = p.get('GU_A3') if f in units else p['ADM0_A3']
    iso = p.get('ISO_A3') if p.get('ISO_A3') not in (None, '-99', -99) else p.get('ISO_A3_EH')
    ISO.setdefault(code, iso if iso not in (None, '-99', -99) else None)
ISO_EXTRA = {'B35': None, 'B36': None, 'B37': None, 'B38': None, 'KOS': 'XKX', 'GAZ': 'PSE', 'WEB': 'PSE', 'FXX': 'FRA', 'SOL': None, 'CYN': None, 'SAH': 'ESH'}

rows = []
for t in terrs:
    title = t['title']
    if title in OVERRIDES:
        refs, note, flag = OVERRIDES[title]
        how = 'override'
    else:
        cands = index.get(norm(title), [])
        # Prefer a whole country over a map unit with the same name.
        c = [x for x in cands if x[0] == 'countries'] or cands
        c = list(dict.fromkeys(c))
        refs, note, flag, how = (c[:1], None, None, 'name') if c else ([], 'No match', 'unmatched', 'none')
        if len(c) > 1:
            note, flag = f'Several candidates: {c}', 'ambiguous'
    codes = [r[1] for r in refs]
    iso = None
    for code in codes:
        iso = ISO_EXTRA.get(code, ISO.get(code)) if code in ISO_EXTRA else ISO.get(code)
        if iso: break
    rows.append({**t, 'refs': refs, 'how': how, 'note': note, 'flag': flag, 'iso': iso})

json.dump(rows, open(f'{S}/data/matches.json', 'w'), ensure_ascii=False, indent=1)
from collections import Counter
print(Counter(r['how'] for r in rows), Counter(r['flag'] for r in rows))
for r in rows:
    if r['how'] != 'name' or r['flag'] or not r['iso']:
        print(r['title'], r['refs'], r['iso'], r['flag'], r['note'])
