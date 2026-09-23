"""
Script para simular tráfico (evitar suspensión en InfinityFree).
Se ejecuta a cada minuto mediante cron.
"""

import os
import requests
import time
from datetime import datetime
import urllib3
from . import config

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

def run():
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Iniciando Keep-Alive de tráfico...")
    
    # Dado que no sabemos el dominio exacto en Python, usamos la IP/Host o lo pasamos por env.
    dominio = os.environ.get("LOTTERY_SITE_URL", "https://numerosrd.42web.io/lottery")

    paginas = [
        "/",
        "/resultados.php",
        "/ia_quinielas.php"
    ]
    
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) (Bot de Mantenimiento SmartLottery)"
    }

    for ruta in paginas:
        url = f"{dominio}{ruta}"
        try:
            res = requests.get(url, headers=headers, timeout=15, verify=False)
            print(f"  [Tráfico] {url} -> HTTP {res.status_code}")
        except Exception as e:
            print(f"  [Tráfico] Error visitando {url}: {e}")
        time.sleep(2)
        
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Proceso finalizado.")

if __name__ == '__main__':
    run()
