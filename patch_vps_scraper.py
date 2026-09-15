import re

with open('vps_scraper.py', 'r', encoding='utf-8') as f:
    content = f.read()
    
# Add update_local_primeras function right before update_local_quinielas
primeras_func = """
def update_local_primeras(resultados):
    if not resultados: return
    
    import os
    file_path = os.path.expanduser('~/historial_primeras.txt')
    p_by_date = {}
    if os.path.exists(file_path):
        with open(file_path, 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line: continue
                parts = line.split('|')
                if len(parts) == 2:
                    p_by_date[parts[0]] = parts[1].split(',')
                    
    # Add new
    for res in resultados:
        fecha = res.get('fecha')
        p = res.get('numeros', [])
        if fecha and len(p) > 0:
            if fecha not in p_by_date:
                p_by_date[fecha] = []
            p_by_date[fecha].append(p[0])
            
    # Write back
    sorted_dates = sorted(p_by_date.keys())
    with open(file_path, 'w', encoding='utf-8') as f:
        for fecha in sorted_dates:
            primeras = p_by_date[fecha]
            f.write(f"{fecha}|{','.join(primeras)}\\n")

def update_local_quinielas"""

content = content.replace("def update_local_quinielas", primeras_func)

# Call update_local_primeras(all_resultados) alongside update_local_quinielas
call_patch = """
    update_local_quinielas(all_resultados)
    update_local_primeras(all_resultados)
"""
content = content.replace("    update_local_quinielas(all_resultados)", call_patch)

# Call python3 update_super_prediccion.py at the end
subprocess_call = """
        print("Ejecutando update_prediccion.py...")
        subprocess.run(["python3", "update_prediccion.py"])
        print("Ejecutando update_super_prediccion.py...")
        subprocess.run(["python3", "update_super_prediccion.py"])
"""
content = content.replace('        print("Ejecutando update_prediccion.py...")\n        subprocess.run(["python3", "update_prediccion.py"])', subprocess_call)

with open('vps_scraper_patched.py', 'w', encoding='utf-8') as f:
    f.write(content)
