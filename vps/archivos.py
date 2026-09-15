"""Escritura atómica de archivos.

Los históricos se reescriben enteros en cada pasada. Con open(...,'w') basta
que el proceso muera a media escritura para dejar el archivo truncado y
perder años de datos. Escribir en un temporal y renombrar hace el cambio
atómico: o está el archivo viejo completo, o el nuevo completo.
"""

from __future__ import annotations

import json
import os
import tempfile
from pathlib import Path
from typing import Any


def escribir_texto(destino: str | Path, contenido: str) -> None:
    destino = Path(destino)
    destino.parent.mkdir(parents=True, exist_ok=True)

    descriptor, temporal = tempfile.mkstemp(dir=str(destino.parent), suffix=".tmp")
    try:
        with os.fdopen(descriptor, "w", encoding="utf-8") as manejador:
            manejador.write(contenido)
            manejador.flush()
            os.fsync(manejador.fileno())
        os.replace(temporal, destino)  # atómico dentro del mismo sistema de archivos
    except BaseException:
        if os.path.exists(temporal):
            os.unlink(temporal)
        raise


def escribir_json(destino: str | Path, datos: Any, indent: int | None = None) -> None:
    escribir_texto(destino, json.dumps(datos, ensure_ascii=False, indent=indent))


def leer_json(origen: str | Path, por_defecto: Any = None) -> Any:
    origen = Path(origen)
    if not origen.is_file():
        return por_defecto
    try:
        return json.loads(origen.read_text(encoding="utf-8"))
    except (json.JSONDecodeError, OSError):
        return por_defecto
