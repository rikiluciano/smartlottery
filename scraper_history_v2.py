import requests
import json
import re
from datetime import datetime, timedelta
import pytz
import ftplib
import tempfile
import os
import time

def extract_date(event_data, requested_date):
    if 'startDate' in event_data and event_data['startDate']:
        return event_data['startDate'][:10]
    return requested_date

def fetch_lottery(url, requested_date):
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
                        nums_match = re.search(r'ganadores(?: son)?:\s*([0-9,\s]+)\.', desc)
                        if nums_match:
                            nums = [n.strip() for n in nums_match.group(1).split(',')]
                            real_date = extract_date(it, requested_date)
                            resultados.append({
                                'fecha': real_date,
                                'nombre': it['name'],
                                'numeros': nums
                            })
            except Exception as e:
                pass
        return resultados
    except Exception as e:
        return []

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
    
    state_file = 'history_state.txt'
    finished_flag = 'history_finished.flag'
    
    if os.path.exists(finished_flag):
        # El historial ya fue extraído completamente
        return
    
    if os.path.exists(state_file):
        with open(state_file, 'r') as f:
            current_date_str = f.read().strip()
        current_date = datetime.strptime(current_date_str, '%Y-%m-%d')
    else:
        # Empezamos desde hace 2 días (ya que scraper_v2 se encarga de hoy y ayer)
        current_date = datetime.now(tz) - timedelta(days=2)
        
    # Extracción de una sola fecha por ejecución (como pidió el usuario: 1 minuto = 1 fecha)
    date_ev = current_date.strftime('%Y-%m-%d')
    print(f"Scraping histórico para la fecha: {date_ev}")
    
    all_resultados = []
    for base_url in base_urls:
        url = f"{base_url}-{date_ev}"
        resultados = fetch_lottery(url, date_ev)
        for res in resultados:
            if res not in all_resultados:
                all_resultados.append(res)
        time.sleep(0.3)
        
    # Control de días vacíos seguidos para auto-detenerse
    empty_days_file = 'empty_days_count.txt'
    empty_days = 0
    if os.path.exists(empty_days_file):
        with open(empty_days_file, 'r') as f:
            try:
                empty_days = int(f.read().strip())
            except:
                pass
                
    if not all_resultados:
        print(f"No se encontraron resultados para {date_ev}.")
        empty_days += 1
        with open(empty_days_file, 'w') as f:
            f.write(str(empty_days))
            
        if empty_days >= 10:
            print("10 días consecutivos sin resultados. Finalizando scraper histórico definitivamente.")
            with open(finished_flag, 'w') as f:
                f.write("finished")
            return # Se detiene y no avanza la fecha, por lo que el cron no hará nada
    else:
        # Reset empty days
        empty_days = 0
        with open(empty_days_file, 'w') as f:
            f.write("0")
        
    # Usamos V2
    payload = {
        'token': 'Rlabs_Scraper_V2_2026',
        'resultados': all_resultados
    }
    
    if all_resultados:
        try:
            print("Connecting to FTP para enviar histórico...")
            ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
            ftp.cwd('htdocs/lottery')
            
            with tempfile.NamedTemporaryFile('w', delete=False) as tf:
                json.dump(payload, tf)
                temp_name = tf.name
                
            filename = f"history_{date_ev}.json"
            with open(temp_name, 'rb') as f:
                ftp.storbinary(f'STOR {filename}', f)
                
            os.remove(temp_name)
            ftp.quit()
            print(f"Successfully uploaded {len(all_resultados)} results para {date_ev} as {filename}.")
        except Exception as e:
            print(f"FTP Error: {e}")
            return # Si falla, no avanzamos la fecha
            
    # Avanzamos un día hacia atrás
    next_date = current_date - timedelta(days=1)
    with open(state_file, 'w') as f:
        f.write(next_date.strftime('%Y-%m-%d'))

if __name__ == "__main__":
    main()
