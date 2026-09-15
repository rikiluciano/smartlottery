from lottery_config import FTP_HOST, FTP_USER, FTP_PASS, INGEST_TOKEN
import requests
import json
import re
from datetime import datetime
import pytz
import ftplib
import tempfile
import os
import time

def extract_date(event_data):
    # Try to extract actual date from startDate
    if 'startDate' in event_data and event_data['startDate']:
        # e.g., '2026-09-04T20:50:00-04:00'
        return event_data['startDate'][:10]
    return None

def fetch_lottery(url):
    try:
        html = requests.get(url, timeout=15).text
        matches = re.findall(r'<script type="application/ld\+json">([\s\S]*?)</script>', html)
        if not matches:
            return []
            
        resultados = []
        for match in matches:
            try:
                data = json.loads(match)
                items = data if isinstance(data, list) else data.get('@graph', [data])
                for it in items:
                    if it.get('@type') == 'Event' and 'description' in it:
                        desc = it['description']
                        # Buscar los numeros
                        nums_match = re.search(r'ganadores(?: son)?:\s*([0-9,\s]+)\.', desc)
                        if nums_match:
                            nums = [n.strip() for n in nums_match.group(1).split(',')]
                            real_date = extract_date(it)
                            if real_date:
                                resultados.append({
                                    'fecha': real_date,
                                    'nombre': it['name'],
                                    'numeros': nums
                                })
            except Exception as e:
                pass
        return resultados
    except Exception as e:
        print(f"Error fetching {url}: {e}")
        return []

def sort_pale(n1, n2):
    return f"{n1}-{n2}" if n1 < n2 else f"{n2}-{n1}"

def update_local_data_lake(resultados):
    if not resultados: return
    
    # Read existing
    import os
    file_path = os.path.expanduser('~/historial_pales.txt')
    pales_by_date = {}
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line: continue
                parts = line.split('|')
                if len(parts) == 2:
                    pales_by_date[parts[0]] = set(parts[1].split(','))
                    
    # Add new
    for res in resultados:
        fecha = res.get('fecha')
        nums = res.get('numeros', [])
        if fecha and len(nums) >= 3:
            p1 = sort_pale(nums[0], nums[1])
            p2 = sort_pale(nums[0], nums[2])
            if fecha not in pales_by_date:
                pales_by_date[fecha] = set()
            pales_by_date[fecha].add(p1)
            pales_by_date[fecha].add(p2)
            
    # Write back
    sorted_dates = sorted(pales_by_date.keys())
    with open(file_path, 'w', encoding='utf-8') as f:
        for fecha in sorted_dates:
            pales = list(pales_by_date[fecha])
            f.write(f"{fecha}|{','.join(pales)}\n")

def update_local_quinielas(resultados):
    if not resultados: return
    
    import os
    file_path = os.path.expanduser('~/historial_quinielas.txt')
    q_by_date = {}
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line: continue
                parts = line.split('|')
                if len(parts) == 4:
                    q_by_date[parts[0]] = {
                        'P': set(parts[1][2:].split(',')) if parts[1][2:] else set(),
                        'S': set(parts[2][2:].split(',')) if parts[2][2:] else set(),
                        'T': set(parts[3][2:].split(',')) if parts[3][2:] else set()
                    }
                    
    # Add new
    for res in resultados:
        fecha = res.get('fecha')
        nums = res.get('numeros', [])
        if fecha and len(nums) >= 3:
            if fecha not in q_by_date:
                q_by_date[fecha] = {'P': set(), 'S': set(), 'T': set()}
            q_by_date[fecha]['P'].add(nums[0].zfill(2))
            q_by_date[fecha]['S'].add(nums[1].zfill(2))
            q_by_date[fecha]['T'].add(nums[2].zfill(2))
            
    sorted_dates = sorted(q_by_date.keys())
    with open(file_path, 'w', encoding='utf-8') as f:
        for fecha in sorted_dates:
            p_str = ','.join(q_by_date[fecha]['P'])
            s_str = ','.join(q_by_date[fecha]['S'])
            t_str = ','.join(q_by_date[fecha]['T'])
            f.write(f"{fecha}|P:{p_str}|S:{s_str}|T:{t_str}\n")

def update_db_backup(resultados):
    if not resultados: return
    
    import os, json
    file_path = os.path.expanduser('~/db_backup.json')
    if not os.path.exists(file_path):
        return
        
    try:
        with open(file_path, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except:
        data = []
        
    # Crear un set para buscar duplicados rapido
    existentes = set()
    for row in data:
        fch = row.get('fecha')
        nom = row.get('nombre_loteria')
        if fch and nom:
            existentes.add(f"{fch}|{nom}")
            
    # Asignar un ID a los nuevos si es necesario
    max_id = max([r.get('id', 0) for r in data]) if data else 0
    
    nuevos = 0
    for res in resultados:
        fch = res.get('fecha')
        nom = res.get('nombre')
        nums = res.get('numeros', [])
        if fch and nom and len(nums) >= 3:
            clave = f"{fch}|{nom}"
            if clave not in existentes:
                max_id += 1
                data.append({
                    'id': max_id,
                    'fecha': fch,
                    'nombre_loteria': nom,
                    'primera': nums[0],
                    'segunda': nums[1],
                    'tercera': nums[2]
                })
                existentes.add(clave)
                nuevos += 1
                
    if nuevos > 0:
        with open(file_path, 'w', encoding='utf-8') as f:
            json.dump(data, f, indent=2, ensure_ascii=False)
        # Llamar al script de stats
        os.system('python3 ~/vps_calc_stats.py')

def main():
    base_urls = [
        'https://enloteria.com/resultados-anguilla-8am',
        'https://enloteria.com/resultados-anguilla-9am',
        'https://enloteria.com/resultados-anguilla-10am',
        'https://enloteria.com/resultados-anguilla-11am',
        'https://enloteria.com/resultados-anguilla-12pm',
        'https://enloteria.com/resultados-anguilla-1pm',
        'https://enloteria.com/resultados-anguilla-2pm',
        'https://enloteria.com/resultados-anguilla-3pm',
        'https://enloteria.com/resultados-anguilla-4pm',
        'https://enloteria.com/resultados-anguilla-5pm',
        'https://enloteria.com/resultados-anguilla-6pm',
        'https://enloteria.com/resultados-anguilla-7pm',
        'https://enloteria.com/resultados-anguilla-8pm',
        'https://enloteria.com/resultados-anguilla-9pm',
        'https://enloteria.com/resultados-anguilla-10pm',
        'https://enloteria.com/resultados-la-primera',
        'https://enloteria.com/resultados-la-primera-noche',
        'https://enloteria.com/resultados-lotedom',
        'https://enloteria.com/resultados-georgia-dia',
        'https://enloteria.com/resultados-georgia-tarde',
        'https://enloteria.com/resultados-georgia-noche',
        'https://enloteria.com/resultados-florida-tarde',
        'https://enloteria.com/resultados-florida-noche',
        'https://enloteria.com/resultados-new-york-tarde',
        'https://enloteria.com/resultados-new-york-noche',
        'https://enloteria.com/resultados-new-jersey-tarde',
        'https://enloteria.com/resultados-new-jersey-noche',
        'https://enloteria.com/resultados-la-suerte',
        'https://enloteria.com/resultados-la-suerte-6pm',
        'https://enloteria.com/resultados-king-lottery-dia',
        'https://enloteria.com/resultados-king-lottery-noche',
        'https://enloteria.com/resultados-real',
        'https://enloteria.com/resultados-real-noche',
        'https://enloteria.com/resultados-loteka',
        'https://enloteria.com/resultados-gana-mas',
        'https://enloteria.com/resultados-nacional-noche',
        'https://enloteria.com/resultados-haiti-bolet-9-30-am',
        'https://enloteria.com/resultados-haiti-bolet-10-30-am',
        'https://enloteria.com/resultados-haiti-bolet-11-30-am',
        'https://enloteria.com/resultados-haiti-bolet-5-30-pm',
        'https://enloteria.com/resultados-haiti-bolet-6-30-pm',
        'https://enloteria.com/resultados-haiti-bolet-7-30-pm',
        'https://enloteria.com/resultados-leidsa'
    ]

    tz = pytz.timezone('America/Santo_Domingo')
    
    # We scrape for TODAY and YESTERDAY just to be sure we get the latest results!
    # Because some lotteries might not have updated for today yet.
    target_dates = [
        datetime.now(tz).strftime('%Y-%m-%d'),
        datetime.fromtimestamp(datetime.now(tz).timestamp() - 86400).strftime('%Y-%m-%d')
    ]
    
    all_resultados = []
    
    for date_ev in target_dates:
        for base_url in base_urls:
            url = f"{base_url}-{date_ev}"
            print(f"Scraping: {url}")
            resultados = fetch_lottery(url)
            for res in resultados:
                if res not in all_resultados:
                    all_resultados.append(res)
            time.sleep(0.5)

    if not all_resultados:
        print("No results found.")
        return
        
    update_local_data_lake(all_resultados)
    update_local_quinielas(all_resultados)
    update_db_backup(all_resultados)

    # Usamos V2 para bloquear el scraper viejo
    payload = {
        'token': INGEST_TOKEN,
        'resultados': all_resultados
    }
    
    # Upload to FTP
    try:
        print("Connecting to FTP...")
        ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
        ftp.cwd('htdocs/lottery')
        
        with tempfile.NamedTemporaryFile('w', delete=False) as tf:
            json.dump(payload, tf)
            temp_name = tf.name
            
        with open(temp_name, 'rb') as f:
            ftp.storbinary('STOR resultados_pendientes.json', f)
            
        os.remove(temp_name)
        ftp.quit()
        print(f"Successfully uploaded {len(all_resultados)} results.")
    except Exception as e:
        print(f"FTP Error: {e}")

if __name__ == "__main__":
    main()
