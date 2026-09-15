"""Credenciales compartidas por los scripts del VPS.

Se leen del entorno para que nunca vivan en el repositorio.
Definirlas en /etc/lottery.env y cargarlas desde el cron.
"""

import os

FTP_HOST = os.environ.get("LOTTERY_FTP_HOST", "ftpupload.net")
FTP_USER = os.environ.get("LOTTERY_FTP_USER", "")
FTP_PASS = os.environ.get("LOTTERY_FTP_PASS", "")
INGEST_TOKEN = os.environ.get("LOTTERY_INGEST_TOKEN", "")

if not FTP_USER or not FTP_PASS:
    raise SystemExit(
        "Faltan credenciales FTP. Exportar LOTTERY_FTP_USER y LOTTERY_FTP_PASS "
        "(ver README) antes de ejecutar este script."
    )
