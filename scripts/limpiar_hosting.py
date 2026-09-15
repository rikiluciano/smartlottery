"""Elimina del hosting los archivos que nunca debieron publicarse.

El despliegue por FTP subía el repositorio entero, así que en la raíz web
acabaron los endpoints destructivos (truncar_db.php y compañía) y los ~70
scripts de Python del VPS, varios con las credenciales FTP dentro.

Quitar un archivo del repositorio NO lo quita del servidor: hay que borrarlo
allí. Eso es lo que hace este script.

Uso:
    export LOTTERY_FTP_USER=...
    export LOTTERY_FTP_PASS=...

    python3 scripts/limpiar_hosting.py              # simulación: solo lista
    python3 scripts/limpiar_hosting.py --ejecutar   # borra de verdad

Conserva todo lo que la versión desplegada necesita para seguir en pie,
incluido db_wiped.flag: sin ese archivo, el core/logic.php antiguo ejecuta
TRUNCATE TABLE sorteos en la siguiente carga de página.

Haz una copia antes:
    wget -m --user=USUARIO --password=CLAVE ftp://ftpupload.net/htdocs/lottery/
"""

import fnmatch
import ftplib
import os
import sys

HOST = os.environ.get("LOTTERY_FTP_HOST", "ftpupload.net")
USER = os.environ.get("LOTTERY_FTP_USER", "")
PASS = os.environ.get("LOTTERY_FTP_PASS", "")
RAIZ = os.environ.get("LOTTERY_FTP_DIR", "/htdocs/lottery")

if not USER or not PASS:
    raise SystemExit(
        "Faltan credenciales.\n"
        "  export LOTTERY_FTP_USER=...\n"
        "  export LOTTERY_FTP_PASS=..."
    )

EJECUTAR = "--ejecutar" in sys.argv

# ---------------------------------------------------------------------------
# CONSERVAR: lo que el código ACTUALMENTE desplegado (master, versión vieja)
# necesita para seguir funcionando. No se toca hasta que despliegue el refactor.
# ---------------------------------------------------------------------------
CONSERVAR = {
    # Páginas y endpoints vivos
    'index.php','resultados.php','resultados_live.php','panel_admin.php',
    'ia_quinielas.php','ia_prediccion.php','ia_prediccion_pale.php',
    'ia_prediccion_super_pale.php','api_chat_quiniela.php','api_prediccion.php',
    'api_quinielas.php','api_super_prediccion.php','guardar_resultados.php',
    'descargar_pales.php',
    # Los necesita la versión vieja: se irán solos al desplegar el refactor
    'config_db.php','security.php',
    # Datos generados por el VPS
    'db_backup.json','prediccion.json','prediccion_quinielas.json',
    'super_prediccion.json','stats_quinielas.json','resultados_pendientes.json',
    'pales.txt','pales_posibles.txt','pales_encontrados.txt',
    'super_pales_encontrados.txt','tripletas.txt','auditoria_pales.txt',
    # CRÍTICO: sin este archivo, el logic.php viejo ejecuta TRUNCATE TABLE
    'db_wiped.flag',
    # Credenciales: se sube a mano y no debe borrarse nunca
    'secrets.php',
    # Assets e infraestructura
    'ai_avatar.jpg','finance_bg.jpg','scraper_bot.js',
    '.htaccess','.ftp-deploy-sync-state.json',
}
DIRS_CONSERVAR = {'core','components','img','resultado'}

# ---------------------------------------------------------------------------
# BORRAR
# ---------------------------------------------------------------------------
PATRONES_BORRAR = [
    '*.py',          # scripts del VPS: no pintan nada aquí y llevan las credenciales FTP dentro
    '*.sh',
    'test*.php','test*.js','test*.html','test*.out',
    'truncar_db.php','fix_db.php','dump.php','dump2.php','desc_db.php',
    'check_db*.php','crear_tablas.php','sec.php',
    'enloteria.html','scratch.js','nginx_default','README.md',
    'local_*','debug_*.txt','db_error.txt','db_count.txt',
    'database.sqlite','lottery.db','dump.txt','temp_resultados.json','db_dump.json',
    'ai_bot_avatar_*.jpg','finance_ai_bg_*.jpg',   # duplicados de ai_avatar/finance_bg
]

MOTIVOS = {
    'truncar_db.php':'vacía la base de datos, sin autenticación',
    'fix_db.php':'borra filas, sin autenticación',
    'dump.php':'vuelca la BD a un archivo público',
    'dump2.php':'vuelca la BD a un archivo público',
    'desc_db.php':'expone el esquema',
    'check_db.php':'expone conteos de la BD',
    'check_db_error.php':'expone errores de la BD',
    'test_ingest.php':'ingesta sin token',
    'crear_tablas.php':'DDL sin autenticación',
    'sec.php':'resto de pruebas',
}

f = ftplib.FTP(HOST, timeout=60); f.connect(HOST,21); f.login(USER,PASS); f.cwd(RAIZ)
filas=[]; f.retrlines('LIST', filas.append)

archivos, directorios = [], []
for l in filas:
    nombre = l.split(None,8)[-1]
    if nombre in ('.','..'): continue
    (directorios if l.startswith('d') else archivos).append(nombre)

a_borrar = [
    n for n in archivos
    if n not in CONSERVAR and any(fnmatch.fnmatch(n, p) for p in PATRONES_BORRAR)
]

peligrosos = [n for n in a_borrar if n in MOTIVOS]
resto      = [n for n in a_borrar if n not in MOTIVOS]

print(f"{'BORRANDO' if EJECUTAR else 'SIMULACIÓN — no se borra nada'}\n")
print(f"ENDPOINTS PELIGROSOS ({len(peligrosos)}):")
for n in sorted(peligrosos): print(f"   ✗ {n:26s} {MOTIVOS[n]}")
print(f"\nCÓDIGO MUERTO Y FUENTES DEL VPS ({len(resto)}):")
for i in range(0, len(resto), 4):
    print("   " + "  ".join(f"{x:24s}" for x in sorted(resto)[i:i+4]))

conservados = [n for n in archivos if n not in a_borrar]
print(f"\nSE CONSERVAN: {len(conservados)} archivos + {len(directorios)} directorios ({', '.join(sorted(directorios))})")
print(f"\nTOTAL A BORRAR: {len(a_borrar)} de {len(archivos)}")

if EJECUTAR:
    ok = err = 0
    for n in a_borrar:
        try:
            f.delete(n); ok += 1
        except Exception as e:
            print(f"   ! no se pudo borrar {n}: {e}"); err += 1
    print(f"\nBorrados: {ok}   Fallos: {err}")

f.quit()
