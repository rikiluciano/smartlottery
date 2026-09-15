import re

with open('enloteria.html', 'r', encoding='utf-8') as f:
    html = f.read()

# Buscamos img src y alt
matches = re.findall(r'<img[^>]*src="([^"]+)"[^>]*alt="([^"]+)"', html)
for src, alt in set(matches):
    if "Resultados" in alt or "Lotería" in alt or "Logo" in alt:
        print(f"Logo: {alt} -> {src}")
