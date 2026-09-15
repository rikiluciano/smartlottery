"""Cerrojo de instancia única para los trabajos del cron.

El scraper tarda más de lo que dura un minuto de cron. Sin esto, varias
copias corren a la vez y se pisan al reescribir los archivos de historial,
que es la forma más rápida de corromper el lago de datos.
"""

from __future__ import annotations

import fcntl
import os
import sys
import tempfile
from contextlib import contextmanager
from pathlib import Path


@contextmanager
def instancia_unica(nombre: str, silencioso: bool = True):
    """Toma un cerrojo exclusivo o termina si ya hay otra copia corriendo.

    Se usa flock, que el núcleo libera solo aunque el proceso muera de golpe;
    un archivo PID quedaría obsoleto tras un reinicio.
    """
    ruta = Path(tempfile.gettempdir()) / f"lottery_{nombre}.lock"
    descriptor = os.open(ruta, os.O_CREAT | os.O_RDWR, 0o644)

    try:
        fcntl.flock(descriptor, fcntl.LOCK_EX | fcntl.LOCK_NB)
    except BlockingIOError:
        os.close(descriptor)
        if not silencioso:
            print(f"[{nombre}] ya hay una instancia en ejecución; se omite esta.")
        sys.exit(0)

    try:
        os.truncate(descriptor, 0)
        os.write(descriptor, str(os.getpid()).encode())
        yield
    finally:
        fcntl.flock(descriptor, fcntl.LOCK_UN)
        os.close(descriptor)
