from lottery_config import FTP_HOST, FTP_USER, FTP_PASS, INGEST_TOKEN
import requests
import json
import re
import datetime
import pytz
import os
import ftplib
import subprocess

# --- CONFIGURACIÓN ---
TARGET_URL = 'https://enloteria.com/'
SECRET_TOKEN = INGEST_TOKEN
TRACKER_FILE = '/home/ubuntu/history_date.txt'
# ----------------------

def extract_numbers_from_desc(desc):
    match = re.search(r'ganadores(?: son)?:\s*([0-9,\s]+)\.', desc)
    if match:
        nums_str = match.group(1)
        numeros = [n.strip() for n in nums_str.split(',')]
        if len(numeros) == 3 and not (numeros[0] == '00' and numeros[1] == '00' and numeros[2] == '00'):
            return numeros
    return None

def extract_history():
    tz = pytz.timezone('America/Santo_Domingo')
    fecha_hoy = datetime.datetime.now(tz).date()
    
    # Determinar qué fecha toca
    if os.path.exists(TRACKER_FILE):
        with open(TRACKER_FILE, 'r') as f:
            current_date_str = f.read().strip()
            current_date = datetime.datetime.strptime(current_date_str, '%Y-%m-%d').date()
    else:
        # Empezamos desde ayer
        current_date = fecha_hoy - datetime.timedelta(days=1)
        current_date_str = current_date.strftime('%Y-%m-%d')
        
    print(f"[{datetime.datetime.now(tz)}] Iniciando scraper HISTÓRICO. Fecha a extraer: {current_date_str}")
    
    resultados_a_enviar = []
    
    try:
        # 1. Obtener URLs base de la página principal
        response = requests.get(TARGET_URL, timeout=30)
        response.raise_for_status()
        html = response.text
        
        pattern = re.compile(r'<script type="application/ld\+json">([\s\S]*?)</script>')
        matches = pattern.findall(html)
        
        base_urls = []
        for match in matches:
            try:
                json_ld = json.loads(match)
                items = json_ld if isinstance(json_ld, list) else json_ld.get('@graph', [json_ld])
                for item in items:
                    if item.get('@type') == 'Event' and 'url' in item:
                        base_urls.append(item['url'])
            except:
                pass
                
        unique_urls = list(set(base_urls))
        
        # Modificar URLs para que apunten a la fecha objetivo
        target_urls = []
        for url in unique_urls:
            # Reemplazar la fecha final por la fecha objetivo
            # Ej: https://enloteria.com/resultados-anguilla-8am-2026-09-05 -> -2026-08-31
            new_url = re.sub(r'\d{4}-\d{2}-\d{2}$', current_date_str, url)
            target_urls.append(new_url)
            
        print(f"Buscando historial en {len(target_urls)} loterías para {current_date_str}...")
        
        for url in target_urls:
            try:
                res_ind = requests.get(url, timeout=10)
                if res_ind.status_code == 200:
                    matches_ind = pattern.findall(res_ind.text)
                    for m_ind in matches_ind:
                        try:
                            json_ld_ind = json.loads(m_ind)
                            items_ind = json_ld_ind if isinstance(json_ld_ind, list) else json_ld_ind.get('@graph', [json_ld_ind])
                            for it in items_ind:
                                if it.get('@type') == 'Event' and 'name' in it and 'description' in it:
                                    date_ev = it.get('startDate', '')[:10]
                                    if date_ev == current_date_str:
                                        nums = extract_numbers_from_desc(it['description'])
                                        if nums:
                                            resultados_a_enviar.append({
                                                'fecha': date_ev,
                                                'nombre': it['name'],
                                                'numeros': nums
                                            })
                        except:
                            pass
            except Exception as e:
                pass
                
        print(f"Resultados encontrados para {current_date_str}: {len(resultados_a_enviar)}")
        
        # Verificar Condición de Parada (Autodestrucción)
        if len(resultados_a_enviar) == 0:
            print("==================================================")
            print(f"LÍMITE ALCANZADO. 0 resultados para {current_date_str}.")
            print("Eliminando este cronjob (Autodestrucción)...")
            
            # Quitar de crontab la línea que contiene vps_scraper_history.py
            os.system("crontab -l | grep -v 'vps_scraper_history.py' | crontab -")
            print("Autodestrucción completada. El script histórico ha terminado su propósito.")
            return

        # Enviar resultados vía FTP
        temp_file = "resultados_history.json"
        with open(temp_file, "w", encoding="utf-8") as f:
            json.dump({"token": SECRET_TOKEN, "resultados": resultados_a_enviar}, f)
        
        ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
        for d in ['htdocs', 'lottery']:
            try: ftp.cwd(d)
            except: pass
            
        with open(temp_file, 'rb') as file:
            # Usar un nombre de archivo único para que no colisione con el de HOY
            ftp.storbinary('STOR resultados_pendientes.json', file)
        ftp.quit()
        
        # Restar un día y guardar para el próximo minuto
        next_date = current_date - datetime.timedelta(days=1)
        with open(TRACKER_FILE, 'w') as f:
            f.write(next_date.strftime('%Y-%m-%d'))
            
        print(f"Archivo subido. Próxima fecha será: {next_date.strftime('%Y-%m-%d')}")
        
    except Exception as e:
        print(f"Error general en history scraper: {e}")

if __name__ == '__main__':
    extract_history()
