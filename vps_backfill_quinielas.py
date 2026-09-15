import json
from collections import defaultdict

def build_data_lake():
    print("Leyendo db_backup.json...")
    try:
        with open('db_backup.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print("Error leyendo db_backup.json:", e)
        return
        
    quinielas_by_date = defaultdict(lambda: {'P': set(), 'S': set(), 'T': set()})
    
    for row in data:
        if not row.get('fecha') or not row.get('primera') or not row.get('segunda') or not row.get('tercera'):
            continue
            
        fecha = row['fecha']
        p1 = str(row['primera']).zfill(2)
        p2 = str(row['segunda']).zfill(2)
        p3 = str(row['tercera']).zfill(2)
        
        quinielas_by_date[fecha]['P'].add(p1)
        quinielas_by_date[fecha]['S'].add(p2)
        quinielas_by_date[fecha]['T'].add(p3)
        
    sorted_dates = sorted(quinielas_by_date.keys())
    
    with open('historial_quinielas.txt', 'w', encoding='utf-8') as f:
        for fecha in sorted_dates:
            p_str = ','.join(quinielas_by_date[fecha]['P'])
            s_str = ','.join(quinielas_by_date[fecha]['S'])
            t_str = ','.join(quinielas_by_date[fecha]['T'])
            f.write(f"{fecha}|P:{p_str}|S:{s_str}|T:{t_str}\n")
            
    print("Archivo historial_quinielas.txt generado con éxito.")

if __name__ == "__main__":
    build_data_lake()
