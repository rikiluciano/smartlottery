import json
import itertools
from collections import defaultdict

def sort_pale(n1, n2):
    return f"{n1}-{n2}" if n1 < n2 else f"{n2}-{n1}"

def build_data_lake():
    print("Leyendo db_backup.json...")
    try:
        with open('db_backup.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print("Error leyendo db_backup.json:", e)
        return
        
    pales_by_date = defaultdict(set)
    
    for row in data:
        if not row.get('fecha') or not row.get('primera') or not row.get('segunda') or not row.get('tercera'):
            continue
            
        fecha = row['fecha']
        p1 = sort_pale(row['primera'], row['segunda'])
        p2 = sort_pale(row['primera'], row['tercera'])
        
        pales_by_date[fecha].add(p1)
        pales_by_date[fecha].add(p2)
        
    print(f"Escribiendo historial_pales.txt para {len(pales_by_date)} días históricos...")
    
    # Sort dates oldest to newest
    sorted_dates = sorted(pales_by_date.keys())
    
    with open('historial_pales.txt', 'w', encoding='utf-8') as f:
        for fecha in sorted_dates:
            pales = list(pales_by_date[fecha])
            # Format: YYYY-MM-DD|01-05,23-45,12-14...
            f.write(f"{fecha}|{','.join(pales)}\n")
            
    print("Archivo historial_pales.txt generado con éxito.")

if __name__ == "__main__":
    build_data_lake()
