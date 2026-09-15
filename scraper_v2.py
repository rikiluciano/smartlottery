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
