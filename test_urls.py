import requests, json, re
html = requests.get('https://enloteria.com/').text
matches = re.findall(r'<script type="application/ld\+json">([\s\S]*?)</script>', html)
for m in matches:
    try:
        j = json.loads(m)
        items = j if isinstance(j, list) else j.get('@graph', [j])
        for i in items:
            if i.get('@type') == 'Event':
                print(i.get('url'))
    except Exception:
        pass
