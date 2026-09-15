import json
from collections import defaultdict, Counter
from datetime import datetime
from . import config

def calculate_stats():
    print("Leyendo db_backup.json...")
    try:
        with open(config.ruta('db_backup.json'), 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print("Error leyendo db_backup.json:", e)
        return

    # Estructuras para estadísticas
    # stats[num] = {
    #   'ultima_aparicion': {'fecha': '', 'loteria': '', 'posicion': ''},
    #   'conteo_loterias': Counter(),
    #   'conteo_posiciones': Counter(),
    #   'co_ocurrencias': Counter(),
    #   'fechas_vistas': set()
    # }
    stats = {}
    for i in range(100):
        num = f"{i:02d}"
        stats[num] = {
            'ultima_fecha': '1900-01-01',
            'ultima_loteria': '',
            'ultima_posicion': '',
            'conteo_loterias': Counter(),
            'conteo_posiciones': Counter(),
            'co_ocurrencias': Counter(),
            'fechas_vistas': set()
        }

    # Ordenar cronológicamente para asegurar que procesamos del más viejo al más nuevo
    data.sort(key=lambda x: x.get('fecha', ''))

    for row in data:
        fecha = row.get('fecha')
        loteria = row.get('nombre_loteria', 'Desconocida')
        
        p1 = str(row.get('primera', '')).zfill(2)
        p2 = str(row.get('segunda', '')).zfill(2)
        p3 = str(row.get('tercera', '')).zfill(2)
        
        nums_in_draw = []
        if p1 and p1 != '00' and p1 != '0': nums_in_draw.append((p1, 'Primera'))
        if p2 and p2 != '00' and p2 != '0': nums_in_draw.append((p2, 'Segunda'))
        if p3 and p3 != '00' and p3 != '0': nums_in_draw.append((p3, 'Tercera'))
        
        # Ocurrencias
        just_nums = [n[0] for n in nums_in_draw]
        
        for num, pos in nums_in_draw:
            if num not in stats: continue # Si hay algun dato mal formado
            
            s = stats[num]
            s['fechas_vistas'].add(fecha)
            s['conteo_loterias'][loteria] += 1
            s['conteo_posiciones'][pos] += 1
            
            if fecha >= s['ultima_fecha']:
                s['ultima_fecha'] = fecha
                s['ultima_loteria'] = loteria
                s['ultima_posicion'] = pos
                
            # Co-ocurrencias
            for other_num in just_nums:
                if other_num != num and other_num in stats:
                    s['co_ocurrencias'][other_num] += 1

    hoy = datetime.now()
    final_stats = {}
    
    for num, s in stats.items():
        if s['ultima_fecha'] == '1900-01-01':
            continue
            
        # Calcular dias
        try:
            d_ult = datetime.strptime(s['ultima_fecha'], '%Y-%m-%d')
            dias_ausente = (hoy - d_ult).days
        except:
            dias_ausente = -1
            
        mejor_amigo = s['co_ocurrencias'].most_common(1)
        amigo_num = mejor_amigo[0][0] if mejor_amigo else "Ninguno"
        
        loteria_fav = s['conteo_loterias'].most_common(1)
        loteria_nom = loteria_fav[0][0] if loteria_fav else "Desconocida"
        
        pos_fav = s['conteo_posiciones'].most_common(1)
        pos_nom = pos_fav[0][0] if pos_fav else "Desconocida"
        
        final_stats[num] = {
            'ultima_aparicion': {
                'fecha': s['ultima_fecha'],
                'loteria': s['ultima_loteria'],
                'posicion': s['ultima_posicion']
            },
            'dias_ausente': dias_ausente,
            'loteria_mas_frecuente': loteria_nom,
            'posicion_mas_frecuente': pos_nom,
            'numero_companero_frecuente': amigo_num
        }

    final_stats["metadata"] = {
        "total_sorteos": len(data)
    }

    with open(config.ruta('stats_quinielas.json'), 'w', encoding='utf-8') as f:
        json.dump(final_stats, f, indent=2, ensure_ascii=False)

    print("Estadísticas calculadas y guardadas en stats_quinielas.json")
    
    try:
        import ftplib
        ftp = ftplib.FTP(config.FTP_HOST, config.FTP_USER, config.FTP_PASS)
        ftp.cwd(config.FTP_DIR)
        with open(config.ruta('stats_quinielas.json'), 'rb') as f:
            ftp.storbinary('STOR stats_quinielas.json', f)
        ftp.quit()
        print("stats_quinielas.json subido exitosamente.")
    except Exception as e:
        print("Error subiendo stats:", e)

if __name__ == '__main__':
    calculate_stats()
