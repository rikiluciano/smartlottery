"""
Script para simular tráfico (evitar suspensión en InfinityFree) y descargar respaldo de la BD.
Se puede ejecutar diariamente mediante cron.
"""

import os
import requests
import time
from datetime import datetime
import json
import urllib3
from . import config

urllib3.disable_warnings(urllib3.exceptions.InsecureRequestWarning)

URL_BASE = "https://tu-dominio.com"  # Será inferida de los logs o se puede poner dinámica

def run():
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Iniciando Keep-Alive y Backup de BD...")
    
    # 1. Visitar las páginas principales para generar tráfico
    # Vamos a extraer el dominio real desde la configuración o hardcodear el dominio si lo tenemos
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
        
    # 2. Respaldar la base de datos completa usando el endpoint export_db.php
    print("  [Backup] Descargando base de datos completa...")
    token = config.INGEST_TOKEN
    
    try:
        url_export = f"{dominio}/export_db.php?token={token}"
        res = requests.get(url_export, headers=headers, timeout=30, verify=False)
        
        if res.ok:
            datos = res.json()
            total = datos.get('total', 0)
            print(f"  [Backup] Éxito. {total} sorteos descargados.")
            
            # Guardar en Lottery-Backup con fecha
            backup_dir = os.path.expanduser("~/Lottery-Backup")
            os.makedirs(backup_dir, exist_ok=True)
            
            fecha_str = datetime.now().strftime("%Y-%m-%d")
            ruta_backup = os.path.join(backup_dir, f"db_completa_{fecha_str}.json")
            
            with open(ruta_backup, "w", encoding="utf-8") as f:
                json.dump(datos, f, indent=2, ensure_ascii=False)
                
            print(f"  [Backup] Guardado en {ruta_backup}")
        else:
            print(f"  [Backup] Falló la descarga. HTTP {res.status_code}")
            
    except Exception as e:
        print(f"  [Backup] Error descargando base de datos: {e}")
        
    print(f"[{datetime.now().strftime('%Y-%m-%d %H:%M:%S')}] Proceso finalizado.")

if __name__ == '__main__':
    run()
