"""Rastrea los resultados del día, actualiza el lago de datos y los publica.

Ciclo, una vez por ejecución del cron:

  1. Toma un cerrojo: si la pasada anterior sigue viva, esta termina.
  2. Rastrea las páginas de origen y extrae los sorteos del JSON-LD.
  3. Si no hay nada nuevo, termina sin escribir ni subir nada.
  4. Actualiza los históricos locales (escritura atómica).
  5. Dispara los tres motores de análisis.
  6. Publica el JSON de resultados en el hosting por FTP.

Ejecución:
    set -a; . /etc/lottery.env; set +a
    python3 -m vps.scraper
"""

from __future__ import annotations

import json
import re
import subprocess
import sys
import time
from datetime import datetime, timedelta
from pathlib import Path

from zoneinfo import ZoneInfo

from . import archivos, config, red
from .cerrojo import instancia_unica
from .ftp_cliente import subir
from .loterias import URLS_BASE

RAIZ = Path(__file__).resolve().parent

_BLOQUE_JSONLD = re.compile(
    r'<script type="application/ld\+json">([\s\S]*?)</script>'
)
_NUMEROS_GANADORES = re.compile(r"ganadores(?: son)?:\s*([0-9,\s]+)\.")


# --------------------------------------------------------------------------
# Extracción
# --------------------------------------------------------------------------

def extraer_sorteos(html: str) -> list[dict]:
    """Saca los sorteos del JSON-LD incrustado en la página."""
    sorteos = []

    for bloque in _BLOQUE_JSONLD.findall(html):
        try:
            datos = json.loads(bloque)
        except json.JSONDecodeError:
            continue

        elementos = datos if isinstance(datos, list) else datos.get("@graph", [datos])

        for elemento in elementos:
            if not isinstance(elemento, dict):
                continue
            if elemento.get("@type") != "Event" or "description" not in elemento:
                continue

            coincidencia = _NUMEROS_GANADORES.search(elemento["description"])
            if not coincidencia:
                continue

            fecha = (elemento.get("startDate") or "")[:10]
            if not re.fullmatch(r"\d{4}-\d{2}-\d{2}", fecha):
                continue

            numeros = [n.strip().zfill(2) for n in coincidencia.group(1).split(",")]
            if len(numeros) < 3 or not all(n.isdigit() for n in numeros[:3]):
                continue
            if numeros[:3] == ["00", "00", "00"]:
                continue  # sorteo aún no jugado

            sorteos.append(
                {"fecha": fecha, "nombre": elemento["name"], "numeros": numeros[:3]}
            )

    return sorteos


def fechas_a_rastrear(ahora: datetime) -> list[str]:
    """Hoy siempre; ayer solo durante la madrugada.

    El origen publica los resultados tardíos del día anterior de madrugada.
    Rastrear «ayer» las 24 horas duplicaba el tráfico sin aportar nada: son
    ~43 peticiones extra por minuto, unas 62 000 al día.
    """
    fechas = [ahora.strftime("%Y-%m-%d")]

    if ahora.hour < config.HORA_LIMITE_RASTREO_AYER:
        fechas.append((ahora - timedelta(days=1)).strftime("%Y-%m-%d"))

    return fechas


def rastrear(fechas: list[str]) -> list[dict]:
    vistos: set[tuple] = set()
    sorteos: list[dict] = []

    for fecha in fechas:
        for url_base in URLS_BASE:
            html = red.obtener_html(f"{url_base}-{fecha}")
            if html is None:
                continue

            for sorteo in extraer_sorteos(html):
                # Clave en un set: la versión anterior hacía `if x not in lista`,
                # una comparación lineal por cada resultado.
                clave = (sorteo["fecha"], sorteo["nombre"], tuple(sorteo["numeros"]))
                if clave in vistos:
                    continue
                vistos.add(clave)
                sorteos.append(sorteo)

            time.sleep(config.PAUSA_ENTRE_PETICIONES)

    return sorteos


# --------------------------------------------------------------------------
# Lago de datos local
# --------------------------------------------------------------------------

def _ordenar_pale(a: str, b: str) -> str:
    return f"{a}-{b}" if a < b else f"{b}-{a}"


def actualizar_pales(sorteos: list[dict]) -> None:
    por_fecha: dict[str, set[str]] = {}

    if config.HISTORIAL_PALES.is_file():
        for linea in config.HISTORIAL_PALES.read_text(encoding="utf-8").splitlines():
            partes = linea.strip().split("|")
            if len(partes) == 2 and partes[1]:
                por_fecha[partes[0]] = set(partes[1].split(","))

    for sorteo in sorteos:
        numeros = sorteo["numeros"]
        conjunto = por_fecha.setdefault(sorteo["fecha"], set())
        conjunto.add(_ordenar_pale(numeros[0], numeros[1]))
        conjunto.add(_ordenar_pale(numeros[0], numeros[2]))

    archivos.escribir_texto(
        config.HISTORIAL_PALES,
        "".join(f"{f}|{','.join(sorted(por_fecha[f]))}\n" for f in sorted(por_fecha)),
    )


def actualizar_quinielas(sorteos: list[dict]) -> None:
    por_fecha: dict[str, dict[str, set[str]]] = {}

    if config.HISTORIAL_QUINIELAS.is_file():
        for linea in config.HISTORIAL_QUINIELAS.read_text(encoding="utf-8").splitlines():
            partes = linea.strip().split("|")
            if len(partes) == 4:
                por_fecha[partes[0]] = {
                    clave: set(filter(None, parte[2:].split(",")))
                    for clave, parte in zip(("P", "S", "T"), partes[1:])
                }

    for sorteo in sorteos:
        numeros = sorteo["numeros"]
        registro = por_fecha.setdefault(
            sorteo["fecha"], {"P": set(), "S": set(), "T": set()}
        )
        registro["P"].add(numeros[0])
        registro["S"].add(numeros[1])
        registro["T"].add(numeros[2])

    lineas = []
    for fecha in sorted(por_fecha):
        r = por_fecha[fecha]
        lineas.append(
            f"{fecha}|P:{','.join(sorted(r['P']))}"
            f"|S:{','.join(sorted(r['S']))}"
            f"|T:{','.join(sorted(r['T']))}\n"
        )

    archivos.escribir_texto(config.HISTORIAL_QUINIELAS, "".join(lineas))


def actualizar_respaldo(sorteos: list[dict]) -> int:
    """Añade al db_backup.json los sorteos que aún no estaban. Devuelve cuántos."""
    datos = archivos.leer_json(config.DB_BACKUP, [])
    if not isinstance(datos, list):
        datos = []

    existentes = {
        (fila.get("fecha"), fila.get("nombre_loteria"))
        for fila in datos
        if isinstance(fila, dict)
    }
    ultimo_id = max((fila.get("id", 0) for fila in datos), default=0)

    nuevos = 0
    for sorteo in sorteos:
        clave = (sorteo["fecha"], sorteo["nombre"])
        if clave in existentes:
            continue

        ultimo_id += 1
        numeros = sorteo["numeros"]
        datos.append({
            "id": ultimo_id,
            "fecha": sorteo["fecha"],
            "nombre_loteria": sorteo["nombre"],
            "primera": numeros[0],
            "segunda": numeros[1],
            "tercera": numeros[2],
        })
        existentes.add(clave)
        nuevos += 1

    if nuevos:
        archivos.escribir_json(config.DB_BACKUP, datos, indent=2)

    return nuevos


# --------------------------------------------------------------------------
# Motores de análisis
# --------------------------------------------------------------------------

MOTORES = ("update_prediccion", "update_quinielas", "update_super_prediccion", "calc_stats")


def disparar_motores() -> None:
    """Ejecuta los análisis. Las rutas salen de __file__, no van en duro."""
    for motor in MOTORES:
        guion = RAIZ / f"{motor}.py"
        if not guion.is_file():
            print(f"  motor ausente: {guion.name}")
            continue

        print(f"  ejecutando {motor}…")
        resultado = subprocess.run(
            [sys.executable, str(guion)],
            capture_output=True,
            text=True,
            timeout=600,
        )
        if resultado.returncode != 0:
            print(f"  {motor} falló ({resultado.returncode}): {resultado.stderr[:400]}")


# --------------------------------------------------------------------------

def main() -> int:
    ahora = datetime.now(ZoneInfo(config.TIMEZONE))
    fechas = fechas_a_rastrear(ahora)

    print(f"[{ahora:%Y-%m-%d %H:%M:%S}] rastreando {len(URLS_BASE)} loterías "
          f"para {', '.join(fechas)}")

    sorteos = rastrear(fechas)
    if not sorteos:
        print("Sin resultados; nada que hacer.")
        return 0

    nuevos = actualizar_respaldo(sorteos)
    print(f"{len(sorteos)} sorteos leídos, {nuevos} nuevos.")

    # Sin novedades no hace falta recalcular ni volver a subir nada.
    if nuevos == 0:
        print("Nada nuevo; se omiten análisis y publicación.")
        return 0

    actualizar_pales(sorteos)
    actualizar_quinielas(sorteos)
    disparar_motores()

    carga = {"token": config.exigir_token(), "resultados": sorteos}
    temporal = Path(config.DATA_DIR) / "resultados_pendientes.json"
    archivos.escribir_json(temporal, carga)

    print("Publicando en el hosting…")
    if not subir({"resultados_pendientes.json": temporal}):
        print("No se pudo publicar; el próximo ciclo lo reintentará.")
        return 1

    return 0


if __name__ == "__main__":
    with instancia_unica("scraper"):
        raise SystemExit(main())
