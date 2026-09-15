import json
import itertools
from collections import defaultdict
from datetime import datetime
import os
import ftplib

def sort_pale(n1, n2):
    return f"{n1}-{n2}" if n1 < n2 else f"{n2}-{n1}"

def get_target_sets():
    numbers = [f"{i:02d}" for i in range(100)]
    sets = {}
    sets['general'] = {'all': set(sort_pale(a, b) for a, b in itertools.combinations(numbers, 2))}
    sets['iniciales'] = {}
    for d in range(10):
        digit = str(d)
        group = [n for n in numbers if n.startswith(digit)]
        sets['iniciales'][digit] = set(sort_pale(a, b) for a, b in itertools.combinations(group, 2))
    sets['terminales'] = {}
    for d in range(10):
        digit = str(d)
        group = [n for n in numbers if n.endswith(digit)]
        sets['terminales'][digit] = set(sort_pale(a, b) for a, b in itertools.combinations(group, 2))
    sets['compartidos'] = {}
    for d in range(10):
        digit = str(d)
        group = [n for n in numbers if digit in n]
        sets['compartidos'][digit] = set(sort_pale(a, b) for a, b in itertools.combinations(group, 2))
    return sets

def run():
    print("Iniciando análisis directo de db_backup.json para Súper Palés...")
    file_path = os.path.expanduser('~/db_backup.json')
    if not os.path.exists(file_path):
        print(f"No se encontro {file_path}")
        return
        
    with open(file_path, 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    primeras_by_date = {}
    for record in data:
        fecha = record.get('fecha')
        p = record.get('primera')
        if fecha and p:
            if fecha not in primeras_by_date:
                primeras_by_date[fecha] = []
            primeras_by_date[fecha].append(p)
            
    dates = sorted(primeras_by_date.keys(), reverse=True)
    draws = {}
    for fecha in dates:
        primeras = primeras_by_date[fecha]
        draws[fecha] = set(sort_pale(a, b) for a, b in itertools.combinations(primeras, 2))
        
    sets = get_target_sets()
    results = {}
    
    def find_missing_sp(target_set, sorted_dates, draws_by_date):
        seen = set()
        total = len(target_set)
        for fecha in sorted_dates:
            draws_today = draws_by_date[fecha]
            for p in draws_today:
                if p in target_set and p not in seen:
                    seen.add(p)
            remaining = total - len(seen)
            if remaining == 1:
                missing = (target_set - seen).pop()
                return {"status": "found_one", "missing": [missing], "fecha_alcanzada": fecha}
            elif remaining == 0:
                return {"status": "found_zero_simultaneously", "missing": [], "fecha_alcanzada": fecha}
        missing_list = list(target_set - seen)
        return {"status": "history_ended", "missing": missing_list[:10], "remaining_count": len(missing_list)}

    results['general'] = {'all': find_missing_sp(sets['general']['all'], dates, draws)}
    for category in ['iniciales', 'terminales', 'compartidos']:
        results[category] = {}
        for digit, target_set in sets[category].items():
            results[category][digit] = find_missing_sp(target_set, dates, draws)
            
    with open('/home/ubuntu/super_prediccion.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)
        
    print("Subiendo archivos al FTP...")
    try:
        ftp = ftplib.FTP('ftpupload.net', 'if0_40933868', 'WYDk3sCTGK8s0u')
        ftp.cwd('htdocs/lottery')
        with open('/home/ubuntu/super_prediccion.json', 'rb') as f:
            ftp.storbinary('STOR super_prediccion.json', f)
        ftp.quit()
        print("Super Prediccion subida exitosamente.")
    except Exception as e:
        print("Error subiendo super prediccion:", e)

if __name__ == '__main__':
    run()
