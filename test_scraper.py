import requests
import json
import re

url = 'https://enloteria.com/resultados-anguilla-8am-2026-09-01'
res = requests.get(url, timeout=10)
matches = re.findall(r'<script type="application/ld\+json">([\s\S]*?)</script>', res.text)

for match in matches:
    try:
        json_ld = json.loads(match)
        items = json_ld if isinstance(json_ld, list) else json_ld.get('@graph', [json_ld])
        
        for item in items:
            if item.get('@type') == 'Event' and 'name' in item and 'description' in item:
                desc = item['description']
                # Extraer los números
                # Formato: "Resultados de Anguilla 8AM del 01 de septiembre de 2026. Números ganadores: 23, 80, 56."
                if "úmeros ganadores:" in desc or "úmeros ganadores son:" in desc:
                    nums_match = re.search(r'ganadores(?: son)?:\s*([0-9,\s]+)\.', desc)
                    if nums_match:
                        nums_str = nums_match.group(1)
                        numeros = [n.strip() for n in nums_str.split(',')]
                        # Extract date
                        date = item['startDate'][:10]
                        print(f"[{date}] {item['name']} -> {numeros}")
    except Exception as e:
        pass
