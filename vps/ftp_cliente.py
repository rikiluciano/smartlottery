"""Subida por FTP con TLS y escritura atómica en el servidor remoto.

Dos cambios frente a la versión anterior:

1. FTP_TLS en lugar de FTP. El FTP plano envía usuario y contraseña en claro
   por la red; cualquiera en la ruta puede leerlas.
2. Subida a un nombre temporal y renombrado al final. Si la conexión se corta
   a mitad, el archivo bueno que ya estaba publicado sigue intacto en vez de
   quedar truncado y romper la web.
"""

from __future__ import annotations

import ftplib
import time
from pathlib import Path

from . import config


def _conectar() -> ftplib.FTP:
    config.exigir_credenciales_ftp()

    try:
        conexion: ftplib.FTP = ftplib.FTP_TLS(timeout=30)
        conexion.connect(config.FTP_HOST, 21)
        conexion.login(config.FTP_USER, config.FTP_PASS)
        conexion.prot_p()  # cifrar también el canal de datos, no solo el de control
    except (ftplib.error_perm, ftplib.error_proto, OSError):
        # InfinityFree no siempre ofrece FTPS. Se avisa y se continúa en claro
        # para no dejar el sitio sin actualizar.
        print("  aviso: el servidor rechazó FTPS; se usa FTP en claro.")
        conexion = ftplib.FTP(timeout=30)
        conexion.connect(config.FTP_HOST, 21)
        conexion.login(config.FTP_USER, config.FTP_PASS)

    conexion.cwd(config.FTP_DIR)
    return conexion


def subir(archivos: dict[str, str | Path], reintentos: int = 3) -> bool:
    """Sube {nombre_remoto: ruta_local}. Devuelve True si todo llegó."""
    if not archivos:
        return True

    for intento in range(1, reintentos + 1):
        conexion = None
        try:
            conexion = _conectar()

            for nombre_remoto, ruta_local in archivos.items():
                ruta_local = Path(ruta_local)
                if not ruta_local.is_file():
                    print(f"  omitido (no existe): {ruta_local}")
                    continue

                temporal = f".{nombre_remoto}.subiendo"
                with ruta_local.open("rb") as manejador:
                    conexion.storbinary(f"STOR {temporal}", manejador)

                try:
                    conexion.delete(nombre_remoto)
                except ftplib.error_perm:
                    pass  # todavía no existía
                conexion.rename(temporal, nombre_remoto)
                print(f"  subido: {nombre_remoto}")

            conexion.quit()
            return True

        except Exception as error:
            print(f"  fallo de FTP (intento {intento}/{reintentos}): {error}")
            if conexion is not None:
                try:
                    conexion.close()
                except Exception:
                    pass
            if intento < reintentos:
                time.sleep(2 ** intento)

    return False
