import requests, json, re
html = requests.get('https://enloteria.com/resultados-lotedom-2026-09-05').text
matches = re.findall(r'<script type="application/ld\+json">([\s\S]*?)</script>', html)
try:
    j = json.loads(matches[0])
    items = j if isinstance(j, list) else j.get('@graph', [j])
    for i in items:
        if i.get('@type') == 'Event':
            print(i['name'])
            print(i.get('description'))
except Exception as e:
    print(e)
