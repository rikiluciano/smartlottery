import requests, json, re
html = requests.get('https://enloteria.com/').text
matches = re.findall(r'<script type="application/ld\+json">([\s\S]*?)</script>', html)
for m in matches:
    try:
        j = json.loads(m)
        items = j if isinstance(j, list) else j.get('@graph', [j])
        for i in items:
            if i.get('@type') == 'Event':
                desc = i.get('description', '')
                if re.search(r'ganadores(?: son)?:\s*([0-9,\s]+)\.', desc):
                    print("FOUND NUMBERS FOR:", i['name'], i.get('startDate'))
    except Exception as e:
        pass
