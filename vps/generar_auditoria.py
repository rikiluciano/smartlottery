import json
import itertools
from collections import defaultdict
from datetime import datetime

def sort_pale(n1, n2):
    return f"{n1}-{n2}" if n1 < n2 else f"{n2}-{n1}"

def audit_pales(db_file):
    try:
        with open(db_file, 'r', encoding='utf-8') as f:
            data = json.load(f)
    except FileNotFoundError:
        print(f"Error: {db_file} no encontrado.")
        return

    # Generar todos los 4,950 pales
    numbers = [f"{i:02d}" for i in range(100)]
    todas_combinaciones = set(sort_pale(a, b) for a, b in itertools.combinations(numbers, 2))
    
    # Rastrear ultima fecha
    ultima_aparicion = {pale: None for pale in todas_combinaciones}
    
    # Procesar historia
    for row in data:
        if not row.get('fecha') or not row.get('primera') or not row.get('segunda') or not row.get('tercera'):
            continue
        fecha = row['fecha']
        p1 = sort_pale(row['primera'], row['segunda'])
        p2 = sort_pale(row['primera'], row['tercera'])
        
        if p1 in ultima_aparicion:
            if ultima_aparicion[p1] is None or fecha > ultima_aparicion[p1]:
                ultima_aparicion[p1] = fecha
                
        if p2 in ultima_aparicion:
            if ultima_aparicion[p2] is None or fecha > ultima_aparicion[p2]:
                ultima_aparicion[p2] = fecha
                
    # Ordenar por fecha (mas antiguo primero)
    # None (nunca ha salido) va de primero
    def sort_key(item):
        if item[1] is None:
            return "0000-00-00"
        return item[1]
        
    resultado_ordenado = sorted(ultima_aparicion.items(), key=sort_key)
    
    with open('auditoria_pales.txt', 'w', encoding='utf-8') as f:
        f.write("REPORTE DE AUDITORIA: ÚLTIMA APARICIÓN DE CADA PALÉ\n")
        f.write("="*60 + "\n")
        f.write(f"Total de sorteos analizados: {len(data)}\n")
        f.write("Ordenado desde el que lleva más tiempo sin salir (o nunca) hasta el más reciente.\n")
        f.write("="*60 + "\n\n")
        
        for i, (pale, fecha) in enumerate(resultado_ordenado, 1):
            fecha_str = fecha if fecha else "NUNCA REGISTRADO EN LA BD"
            f.write(f"{i:04d}. Palé: {pale} | Última aparición: {fecha_str}\n")
            
    print("Reporte generado con exito: auditoria_pales.txt")

if __name__ == '__main__':
    audit_pales('db_backup.json')
