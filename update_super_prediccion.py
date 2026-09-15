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

def run():
    print("Iniciando análisis usando Data Lake local (Súper Palés)...")
    
    # Read historial_primeras.txt
    dates = []
    draws = {}
    try:
        with open('historial_primeras.txt', 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line: continue
                parts = line.split('|')
                if len(parts) == 2:
                    fecha = parts[0]
                    primeras = parts[1].split(',')
                    # Generate all Súper Palés for this date
                    super_pales_today = set(sort_pale(a, b) for a, b in itertools.combinations(primeras, 2))
                    dates.append(fecha)
                    draws[fecha] = list(super_pales_today)
    except Exception as e:
        print("Error leyendo historial_primeras.txt:", e)
        return
        
    # Sort newest to oldest
    dates.sort(reverse=True)
    
    sets = get_target_sets()
    results = {}
    
    # Notice we pass `is_general=False` or True depending if we want logging, but we'll use `is_general=True` for 'all' to log super_pales_encontrados.txt
    def find_missing_sp(target_set, sorted_dates, draws_by_date, is_general=False):
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
                    with open('super_pales_encontrados.txt', 'w', encoding='utf-8') as f:
                        for p, fch in found_with_dates:
                            f.write(f"combinacion: {p} fecha: {fch}\n")
                            
                return {"status": "found_one", "missing": [missing], "fecha_alcanzada": fecha}
            elif remaining == 0:
                if is_general:
                    with open('super_pales_encontrados.txt', 'w', encoding='utf-8') as f:
                        for p, fch in found_with_dates:
                            f.write(f"combinacion: {p} fecha: {fch}\n")
                return {"status": "found_zero_simultaneously", "missing": [], "fecha_alcanzada": fecha}
        missing_list = list(target_set - seen)
        return {"status": "history_ended", "missing": missing_list[:10], "remaining_count": len(missing_list)}

    results['general'] = {'all': find_missing_sp(sets['general']['all'], dates, draws, is_general=True)}
    for category in ['iniciales', 'terminales', 'compartidos']:
        results[category] = {}
        for digit, target_set in sets[category].items():
            results[category][digit] = find_missing_sp(target_set, dates, draws)
            
    with open('super_prediccion.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)
        
    print("Subiendo archivos al FTP...")
    try:
        import ftplib
        ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
        ftp.cwd('htdocs/lottery')
        with open('super_prediccion.json', 'rb') as f:
            ftp.storbinary('STOR super_prediccion.json', f)
            
        import os
        if os.path.exists('super_pales_encontrados.txt'):
            with open('super_pales_encontrados.txt', 'rb') as f:
                ftp.storbinary('STOR super_pales_encontrados.txt', f)
                
        ftp.quit()
        print("Super Prediccion calculada y subida exitosamente.")
    except Exception as e:
        print("Error subiendo super prediccion:", e)

if __name__ == '__main__':
    run()
