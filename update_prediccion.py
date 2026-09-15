from lottery_config import FTP_HOST, FTP_USER, FTP_PASS, INGEST_TOKEN
import ftplib
import json
import itertools
from collections import defaultdict
from datetime import datetime
import os

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

def find_missing(target_set, sorted_dates, draws_by_date, is_general=False):
    seen = set()
    found_with_dates = []
    total = len(target_set)
    for fecha in sorted_dates:
        draws_today = draws_by_date[fecha]
        for p in draws_today:
            if p in target_set and p not in seen:
                seen.add(p)
                if is_general:
                    found_with_dates.append((p, fecha))
        remaining = total - len(seen)
        if remaining == 1:
            missing = (target_set - seen).pop()
            
            if is_general:
                # Write to file
                with open('pales_encontrados.txt', 'w', encoding='utf-8') as f:
                    for p, fch in found_with_dates:
                        f.write(f"combinacion: {p} fecha: {fch}\n")
                        
            return {"status": "found_one", "missing": [missing], "fecha_alcanzada": fecha}
        elif remaining == 0:
            if is_general:
                with open('pales_encontrados.txt', 'w', encoding='utf-8') as f:
                    for p, fch in found_with_dates:
                        f.write(f"combinacion: {p} fecha: {fch}\n")
            return {"status": "found_zero_simultaneously", "missing": [], "fecha_alcanzada": fecha}
    missing_list = list(target_set - seen)
    return {"status": "history_ended", "missing": missing_list[:10], "remaining_count": len(missing_list)}

def run():
    print("Iniciando análisis usando Data Lake local...")
    
    # Read historial_pales.txt
    dates = []
    draws = {}
    try:
        with open('historial_pales.txt', 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line: continue
                parts = line.split('|')
                if len(parts) == 2:
                    fecha = parts[0]
                    pales = parts[1].split(',')
                    dates.append(fecha)
                    draws[fecha] = pales
    except Exception as e:
        print("Error leyendo historial_pales.txt:", e)
        return
        
    # Sort newest to oldest
    dates.sort(reverse=True)
    
    sets = get_target_sets()
    results = {}
    
    results['general'] = {'all': find_missing(sets['general']['all'], dates, draws, is_general=True)}
    for category in ['iniciales', 'terminales', 'compartidos']:
        results[category] = {}
        for digit, target_set in sets[category].items():
            results[category][digit] = find_missing(target_set, dates, draws)
            
    with open('prediccion.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)
        
    print("Subiendo archivos al FTP...")
    try:
        import ftplib
        ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
        ftp.cwd('htdocs/lottery')
        with open('prediccion.json', 'rb') as f:
            ftp.storbinary('STOR prediccion.json', f)
            
        import os
        if os.path.exists('pales_encontrados.txt'):
            with open('pales_encontrados.txt', 'rb') as f:
                ftp.storbinary('STOR pales_encontrados.txt', f)
                
        ftp.quit()
        print("Prediccion calculada y subida exitosamente.")
    except Exception as e:
        print("Error subiendo prediccion:", e)

if __name__ == '__main__':
    run()
