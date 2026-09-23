import json
from . import config

def run():
    print("Iniciando análisis de Quinielas usando Data Lake local...")
    
    dates = []
    draws = {}
    try:
        with open(config.ruta('historial_quinielas.txt'), 'r', encoding='utf-8') as f:
            for line in f:
                line = line.strip()
                if not line: continue
                parts = line.split('|')
                if len(parts) == 4:
                    fecha = parts[0]
                    p = parts[1][2:].split(',') if parts[1][2:] else []
                    s = parts[2][2:].split(',') if parts[2][2:] else []
                    t = parts[3][2:].split(',') if parts[3][2:] else []
                    dates.append(fecha)
                    draws[fecha] = {'todas': p + s + t, 'primera': p}
    except Exception as e:
        print("Error leyendo historial_quinielas.txt:", e)
        return
        
    dates.sort(reverse=True)
    
    results = {'numero': {}, 'digito': {}}
    
    # --- NUMERO ---
    all_nums = [f"{i:02d}" for i in range(100)]
    sets_num = {
        'general': {'all': set(all_nums)},
        'decena': {str(d): set([f"{d}{i}" for i in range(10)]) for d in range(10)},
        'terminal': {str(d): set([f"{i}{d}" for i in range(10)]) for d in range(10)}
    }
    
    for pos in ['todas', 'primera']:
        results['numero'][pos] = {}
        for cat in ['general', 'decena', 'terminal']:
            results['numero'][pos][cat] = {}
            for k, target_set in sets_num[cat].items():
                seen = set()
                res = None
                for fecha in dates:
                    missing_before = target_set - seen
                    for num in draws[fecha][pos]:
                        if num in target_set:
                            seen.add(num)
                    rem = len(target_set) - len(seen)
                    if rem == 1:
                        res = {"status": "found_one", "missing": list(target_set - seen)[0], "fecha": fecha}
                        break
                    elif rem == 0:
                        # Dropped to 0 simultaneously! The items in missing_before all appeared on this day.
                        res = {"status": "found_multiple", "missing": list(missing_before), "fecha": fecha}
                        break
                if not res:
                    res = {"status": "history_ended", "missing": list(target_set - seen)[:10], "rem": len(target_set - seen)}
                results['numero'][pos][cat][k] = res

    # --- DIGITO ---
    target_digits = set("0123456789")
    
    for pos in ['todas', 'primera']:
        results['digito'][pos] = {}
        for cat in ['general', 'inicial', 'terminal']:
            results['digito'][pos][cat] = {}
            seen = set()
            res_all = None
            
            # For specific digit, we just record the FIRST time we see it going backwards (which is the most recent date)
            specific_res = {}
            for d in target_digits:
                specific_res[d] = None
                
            for fecha in dates:
                missing_before_digits = target_digits - seen
                for num in draws[fecha][pos]:
                    if cat == 'general':
                        chars = [num[0], num[1]]
                    elif cat == 'inicial':
                        chars = [num[0]]
                    elif cat == 'terminal':
                        chars = [num[1]]
                        
                    for c in chars:
                        if c not in seen:
                            seen.add(c)
                            if specific_res[c] is None:
                                specific_res[c] = {"status": "found", "fecha": fecha, "digit": c}
                                
                rem = 10 - len(seen)
                if rem == 1 and res_all is None:
                    res_all = {"status": "found_one", "missing": list(target_digits - seen)[0], "fecha": fecha}
                elif rem == 0 and res_all is None:
                    res_all = {"status": "found_multiple", "missing": list(missing_before_digits), "fecha": fecha}
                    
            if not res_all:
                res_all = {"status": "history_ended", "missing": list(target_digits - seen), "rem": len(target_digits - seen)}
                
            results['digito'][pos][cat]['all'] = res_all
            for d in target_digits:
                if specific_res[d] is None:
                    specific_res[d] = {"status": "history_ended", "fecha": "N/A", "digit": d}
                results['digito'][pos][cat][d] = specific_res[d]

    with open(config.ruta('prediccion_quinielas.json'), 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2)
        
    print("Subiendo archivos al FTP...")
    from . import ftp_cliente
    exito = ftp_cliente.subir({
        'prediccion_quinielas.json': config.ruta('prediccion_quinielas.json')
    })
    if exito:
        print("Subida exitosa.")
    else:
        print("Error subiendo prediccion.")

if __name__ == '__main__':
    run()
