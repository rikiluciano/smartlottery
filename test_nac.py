import requests
import json
import re

html = requests.get('https://enloteria.com/resultados-nacional-2026-09-05').text
matches = re.findall(r'<script type="application/ld\+json">([\s\S]*?)</script>', html)
for m in matches:
    data = json.loads(m)
    if isinstance(data, dict):
        print(data.get('name'))
    else:
        print([i.get('name') for i in data.get('@graph', [])])
