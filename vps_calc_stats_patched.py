from lottery_config import FTP_HOST, FTP_USER, FTP_PASS, INGEST_TOKEN
import json
from collections import defaultdict, Counter
from datetime import datetime

def calculate_stats():
    print("Leyendo db_backup.json...")
    try:
        with open('db_backup.json', 'r', encoding='utf-8') as f:
            data = json.load(f)
    except Exception as e:
        print("Error leyendo db_backup.json:", e)
        return

    def get_empty_stat():
        return {
            'ultima_fecha': '1900-01-01',
            'ultima_loteria': '',
            'ultima_posicion': '',
            'conteo_loterias': Counter(),
            'conteo_posiciones': Counter(),
            'co_ocurrencias': Counter(),
            'fechas_vistas': set()
        }

    stats_todas = {f"{i:02d}": get_empty_stat() for i in range(100)}
    stats_primera = {f"{i:02d}": get_empty_stat() for i in range(100)}

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
        
        just_nums = [n[0] for n in nums_in_draw]
        
        for num, pos in nums_in_draw:
            if num not in stats_todas: continue
            
            # --- TODAS LAS POSICIONES ---
            st = stats_todas[num]
            st['fechas_vistas'].add(fecha)
            st['conteo_loterias'][loteria] += 1
            st['conteo_posiciones'][pos] += 1
            if fecha >= st['ultima_fecha']:
                st['ultima_fecha'] = fecha
                st['ultima_loteria'] = loteria
                st['ultima_posicion'] = pos
            for other_num in just_nums:
                if other_num != num:
                    st['co_ocurrencias'][other_num] += 1
                    
            # --- SOLAMENTE PRIMERA ---
            if pos == 'Primera':
                sp = stats_primera[num]
                sp['fechas_vistas'].add(fecha)
                sp['conteo_loterias'][loteria] += 1
                sp['conteo_posiciones'][pos] += 1
                if fecha >= sp['ultima_fecha']:
                    sp['ultima_fecha'] = fecha
                    sp['ultima_loteria'] = loteria
                    sp['ultima_posicion'] = pos
                for other_num in just_nums:
                    if other_num != num:
                        sp['co_ocurrencias'][other_num] += 1

    hoy = datetime.now()
    
    def finalize_stats(stats_dict):
        final = {}
        for num, s in stats_dict.items():
            if s['ultima_fecha'] == '1900-01-01':
                continue
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
            
            final[num] = {
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
        return final

    final_output = {
        'todas': finalize_stats(stats_todas),
        'primera': finalize_stats(stats_primera)
    }

    with open('stats_quinielas.json', 'w', encoding='utf-8') as f:
        json.dump(final_output, f, indent=2, ensure_ascii=False)

    print("Estadísticas calculadas y guardadas en stats_quinielas.json")
    
    try:
        import ftplib
        ftp = ftplib.FTP(FTP_HOST, FTP_USER, FTP_PASS)
        ftp.cwd('htdocs/lottery')
        with open('stats_quinielas.json', 'rb') as f:
            ftp.storbinary('STOR stats_quinielas.json', f)
        ftp.quit()
        print("stats_quinielas.json subido exitosamente.")
    except Exception as e:
        print("Error subiendo stats:", e)

if __name__ == '__main__':
    calculate_stats()
