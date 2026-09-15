import requests
from bs4 import BeautifulSoup
import os

res = requests.get('https://enloteria.com/', timeout=10)
soup = BeautifulSoup(res.text, 'html.parser')

os.makedirs('resultado/RLabs/logos', exist_ok=True)

for img in soup.find_all('img'):
    src = img.get('src')
    alt = img.get('alt', '')
    if src and 'svg' in src:
        # Intentar adivinar la familia
        name = 'desconocido'
        if 'anguilla' in alt.lower() or 'anguila' in alt.lower(): name = 'anguilla'
        elif 'primera' in alt.lower(): name = 'la-primera'
        elif 'suerte' in alt.lower(): name = 'la-suerte'
        elif 'florida' in alt.lower(): name = 'florida'
        elif 'new york' in alt.lower() or 'nueva york' in alt.lower(): name = 'new-york'
        elif 'real' in alt.lower(): name = 'real'
        elif 'leidsa' in alt.lower(): name = 'leidsa'
        elif 'loteka' in alt.lower(): name = 'loteka'
        elif 'lotedom' in alt.lower(): name = 'lotedom'
        elif 'king lottery' in alt.lower(): name = 'king-lottery'
        elif 'georgia' in alt.lower(): name = 'georgia'
        elif 'haiti' in alt.lower() or 'bolet' in alt.lower(): name = 'haiti-bolet'
        elif 'nacional' in alt.lower() or 'gana m' in alt.lower(): name = 'nacional'
        elif 'new jersey' in alt.lower(): name = 'new-jersey'
        else:
            continue
            
        print(f"Descargando {name} desde {src}...")
        try:
            img_res = requests.get(src if src.startswith('http') else 'https://enloteria.com' + src)
            if img_res.status_code == 200:
                with open(f'resultado/RLabs/logos/{name}.svg', 'wb') as f:
                    f.write(img_res.content)
        except Exception as e:
            print(f"Error: {e}")
