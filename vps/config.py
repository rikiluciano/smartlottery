"""Configuración compartida por los scripts del VPS.

Todo secreto se lee del entorno: nada de credenciales en el repositorio.
En el servidor se definen en /etc/lottery.env y el cron las carga con:

    * * * * * set -a; . /etc/lottery.env; set +a; python3 /home/ubuntu/vps/scraper.py
"""

from __future__ import annotations

import os
from pathlib import Path

# --- Credenciales ---
FTP_HOST = os.environ.get("LOTTERY_FTP_HOST", "ftpupload.net")
FTP_USER = os.environ.get("LOTTERY_FTP_USER", "")
FTP_PASS = os.environ.get("LOTTERY_FTP_PASS", "")
FTP_DIR = os.environ.get("LOTTERY_FTP_DIR", "htdocs/lottery")

INGEST_TOKEN = os.environ.get("LOTTERY_INGEST_TOKEN", "")
SUPABASE_KEY = os.environ.get("SUPABASE_KEY", "")

# --- Rutas de datos en el VPS ---
DATA_DIR = Path(os.environ.get("LOTTERY_DATA_DIR", Path.home())).expanduser()

HISTORIAL_PALES = DATA_DIR / "historial_pales.txt"
HISTORIAL_QUINIELAS = DATA_DIR / "historial_quinielas.txt"
HISTORIAL_PRIMERAS = DATA_DIR / "historial_primeras.txt"
DB_BACKUP = DATA_DIR / "db_backup.json"

# --- Comportamiento del scraper ---
TIMEZONE = "America/Santo_Domingo"
HTTP_TIMEOUT = int(os.environ.get("LOTTERY_HTTP_TIMEOUT", "15"))
HTTP_REINTENTOS = int(os.environ.get("LOTTERY_HTTP_REINTENTOS", "3"))
PAUSA_ENTRE_PETICIONES = float(os.environ.get("LOTTERY_PAUSA", "0.4"))

# Cada página del origen devuelve las últimas 14 jornadas, así que basta con
# pedir la fecha de hoy. Ver la nota en scraper.fechas_a_rastrear().
JORNADAS_POR_PAGINA = 14


def exigir_credenciales_ftp() -> None:
    """Falla pronto y con un mensaje claro si faltan credenciales."""
    if not FTP_USER or not FTP_PASS:
        raise SystemExit(
            "Faltan credenciales FTP.\n"
            "Define LOTTERY_FTP_USER y LOTTERY_FTP_PASS en /etc/lottery.env.\n"
            "Ver README.md, sección «Despliegue en el VPS»."
        )


def exigir_token() -> str:
    if not INGEST_TOKEN:
        raise SystemExit("Falta LOTTERY_INGEST_TOKEN en el entorno.")
    return INGEST_TOKEN


def ruta(nombre: str) -> str:
    """Resuelve un archivo de datos contra DATA_DIR.

    Los motores abrían sus archivos con rutas relativas, así que solo
    funcionaban si el directorio de trabajo era exactamente /home/ubuntu.
    Con esto dan igual el cwd y desde dónde los invoque el cron.
    """
    return str(DATA_DIR / nombre)
