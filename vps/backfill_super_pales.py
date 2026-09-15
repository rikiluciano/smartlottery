import json
import os

def run():
    print("Leyendo db_backup.json...")
    if not os.path.exists('db_backup.json'):
        print("db_backup.json no encontrado.")
        return
        
    with open('db_backup.json', 'r', encoding='utf-8') as f:
        data = json.load(f)
        
    primeras_by_date = {}
    
    for record in data:
        fecha = record.get('fecha')
        p = record.get('primera')
        if fecha and p:
            if fecha not in primeras_by_date:
                primeras_by_date[fecha] = []
            primeras_by_date[fecha].append(p)
            
    sorted_dates = sorted(primeras_by_date.keys())
    
    print(f"Escribiendo historial_primeras.txt para {len(primeras_by_date)} días históricos...")
    
    with open('historial_primeras.txt', 'w', encoding='utf-8') as f:
        for fecha in sorted_dates:
            primeras = primeras_by_date[fecha]
            f.write(f"{fecha}|{','.join(primeras)}\n")
            
    print("Archivo historial_primeras.txt generado con éxito.")

if __name__ == '__main__':
    run()
