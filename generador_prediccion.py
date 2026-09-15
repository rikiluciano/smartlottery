import json
import itertools
from collections import defaultdict
from datetime import datetime

def sort_pale(n1, n2):
    return f"{n1}-{n2}" if n1 < n2 else f"{n2}-{n1}"

def get_target_sets():
    numbers = [f"{i:02d}" for i in range(100)]
    sets = {}
    
    # 1. General
    sets['general'] = {'all': set(sort_pale(a, b) for a, b in itertools.combinations(numbers, 2))}
    
    # 2. Iniciales (0-9)
    sets['iniciales'] = {}
    for d in range(10):
        digit = str(d)
        group = [n for n in numbers if n.startswith(digit)]
        sets['iniciales'][digit] = set(sort_pale(a, b) for a, b in itertools.combinations(group, 2))
        
    # 3. Terminales (0-9)
    sets['terminales'] = {}
    for d in range(10):
        digit = str(d)
        group = [n for n in numbers if n.endswith(digit)]
        sets['terminales'][digit] = set(sort_pale(a, b) for a, b in itertools.combinations(group, 2))
        
    # 4. Compartidos (0-9)
    sets['compartidos'] = {}
    for d in range(10):
        digit = str(d)
        group = [n for n in numbers if digit in n]
        sets['compartidos'][digit] = set(sort_pale(a, b) for a, b in itertools.combinations(group, 2))
        
    return sets

def analyze_history(db_file):
    try:
        with open(db_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except FileNotFoundError:
        print("db_backup.json not found")
        return None
        
    # Group by date
    draws_by_date = defaultdict(list)
    for row in data:
        if not row.get('fecha') or not row.get('primera') or not row.get('segunda') or not row.get('tercera'):
            continue
        fecha = row['fecha']
        p1 = sort_pale(row['primera'], row['segunda'])
        p2 = sort_pale(row['primera'], row['tercera'])
        draws_by_date[fecha].append(p1)
        draws_by_date[fecha].append(p2)
        
    # Sort dates newest to oldest
    sorted_dates = sorted(draws_by_date.keys(), reverse=True)
    return sorted_dates, draws_by_date

def find_missing(target_set, sorted_dates, draws_by_date):
    seen = set()
    total = len(target_set)
    
    for fecha in sorted_dates:
        draws_today = draws_by_date[fecha]
        
        # Add to seen only if it's in the target set
        for p in draws_today:
            if p in target_set:
                seen.add(p)
                
        remaining = total - len(seen)
        
        if remaining == 1:
            missing = (target_set - seen).pop()
            return {"status": "found_one", "missing": [missing], "fecha_alcanzada": fecha}
        elif remaining == 0:
            # Reached 0 on this exact day. We want the one(s) that were just eliminated.
            # We can re-evaluate the previous state
            return {"status": "found_zero_simultaneously", "missing": [], "fecha_alcanzada": fecha}
            
    # If we run out of history
    missing_list = list(target_set - seen)
    return {"status": "history_ended", "missing": missing_list[:10], "remaining_count": len(missing_list)}

def generate():
    dates, draws = analyze_history('db_backup.json')
    sets = get_target_sets()
    
    results = {}
    
    # Analyze general
    print("Analizando General...")
    results['general'] = {'all': find_missing(sets['general']['all'], dates, draws)}
    
    for category in ['iniciales', 'terminales', 'compartidos']:
        print(f"Analizando {category}...")
        results[category] = {}
        for digit, target_set in sets[category].items():
            results[category][digit] = find_missing(target_set, dates, draws)
            
    with open('prediccion.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)
    print("Generado prediccion.json")

if __name__ == '__main__':
    generate()
