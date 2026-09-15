"""Pruebas del scraper. Ejecutar:  python3 -m unittest discover -s tests/python -t ."""

from __future__ import annotations

import json
import os
import sys
import tempfile
import unittest
from datetime import datetime
from pathlib import Path
from zoneinfo import ZoneInfo

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))

from vps import archivos, config, scraper  # noqa: E402


def pagina(*eventos: dict) -> str:
    """Envuelve eventos en el JSON-LD tal y como lo publica el origen."""
    bloques = "".join(
        f'<script type="application/ld+json">{json.dumps(e)}</script>' for e in eventos
    )
    return f"<html><head>{bloques}</head><body></body></html>"


def evento(nombre: str, fecha: str, numeros: str) -> dict:
    return {
        "@type": "Event",
        "name": nombre,
        "startDate": f"{fecha}T20:50:00-04:00",
        "description": f"Los números ganadores son: {numeros}.",
    }


class ExtraerSorteos(unittest.TestCase):
    def test_extrae_un_sorteo_bien_formado(self):
        r = scraper.extraer_sorteos(pagina(evento("Leidsa", "2026-09-10", "07, 23, 45")))
        self.assertEqual(
            r, [{"fecha": "2026-09-10", "nombre": "Leidsa", "numeros": ["07", "23", "45"]}]
        )

    def test_rellena_con_cero_a_la_izquierda(self):
        r = scraper.extraer_sorteos(pagina(evento("Loteka", "2026-09-10", "7, 3, 0")))
        self.assertEqual(r[0]["numeros"], ["07", "03", "00"])

    def test_descarta_el_sorteo_sin_jugar(self):
        r = scraper.extraer_sorteos(pagina(evento("Real", "2026-09-10", "00, 00, 00")))
        self.assertEqual(r, [])

    def test_descarta_fecha_ausente_o_mal_formada(self):
        malo = evento("Real", "2026-09-10", "01, 02, 03")
        malo["startDate"] = ""
        self.assertEqual(scraper.extraer_sorteos(pagina(malo)), [])

    def test_descarta_menos_de_tres_numeros(self):
        r = scraper.extraer_sorteos(pagina(evento("Real", "2026-09-10", "01, 02")))
        self.assertEqual(r, [])

    def test_ignora_json_invalido_sin_reventar(self):
        html = '<script type="application/ld+json">{ roto ,, }</script>'
        self.assertEqual(scraper.extraer_sorteos(html), [])

    def test_ignora_elementos_que_no_son_evento(self):
        html = pagina({"@type": "Organization", "name": "enloteria"})
        self.assertEqual(scraper.extraer_sorteos(html), [])

    def test_lee_varios_eventos_de_la_misma_pagina(self):
        html = pagina(
            evento("Leidsa", "2026-09-10", "01, 02, 03"),
            evento("Loteka", "2026-09-10", "04, 05, 06"),
        )
        self.assertEqual(len(scraper.extraer_sorteos(html)), 2)

    def test_acepta_la_variante_sin_son(self):
        html = pagina({
            "@type": "Event", "name": "Real", "startDate": "2026-09-10T13:00:00-04:00",
            "description": "Números ganadores: 11, 22, 33.",
        })
        self.assertEqual(scraper.extraer_sorteos(html)[0]["numeros"], ["11", "22", "33"])


class VentanaDeRastreo(unittest.TestCase):
    """Rastrear «ayer» las 24 h duplicaba el tráfico sin aportar datos."""

    def _a_las(self, hora: int) -> list[str]:
        momento = datetime(2026, 9, 10, hora, 0, tzinfo=ZoneInfo(config.TIMEZONE))
        return scraper.fechas_a_rastrear(momento)

    def test_de_madrugada_incluye_ayer(self):
        self.assertEqual(self._a_las(3), ["2026-09-10", "2026-09-09"])

    def test_pasada_la_hora_limite_solo_hoy(self):
        self.assertEqual(self._a_las(15), ["2026-09-10"])

    def test_el_limite_es_exclusivo(self):
        self.assertEqual(len(self._a_las(config.HORA_LIMITE_RASTREO_AYER)), 1)
        self.assertEqual(len(self._a_las(config.HORA_LIMITE_RASTREO_AYER - 1)), 2)


class Respaldo(unittest.TestCase):
    def setUp(self):
        self.dir = tempfile.mkdtemp()
        self._original = config.DB_BACKUP
        config.DB_BACKUP = Path(self.dir) / "db_backup.json"

    def tearDown(self):
        config.DB_BACKUP = self._original

    def test_añade_los_nuevos_y_asigna_id(self):
        archivos.escribir_json(config.DB_BACKUP, [])
        n = scraper.actualizar_respaldo(
            [{"fecha": "2026-09-10", "nombre": "Leidsa", "numeros": ["01", "02", "03"]}]
        )
        datos = archivos.leer_json(config.DB_BACKUP)
        self.assertEqual(n, 1)
        self.assertEqual(datos[0]["id"], 1)
        self.assertEqual(datos[0]["nombre_loteria"], "Leidsa")

    def test_no_duplica_al_reejecutar(self):
        archivos.escribir_json(config.DB_BACKUP, [])
        sorteos = [{"fecha": "2026-09-10", "nombre": "Leidsa", "numeros": ["01", "02", "03"]}]
        scraper.actualizar_respaldo(sorteos)
        n = scraper.actualizar_respaldo(sorteos)
        self.assertEqual(n, 0, "reejecutar el scraper no debe duplicar filas")
        self.assertEqual(len(archivos.leer_json(config.DB_BACKUP)), 1)

    def test_continua_la_numeracion_existente(self):
        archivos.escribir_json(config.DB_BACKUP, [
            {"id": 41, "fecha": "2026-09-09", "nombre_loteria": "Loteka",
             "primera": "9", "segunda": "9", "tercera": "9"}
        ])
        scraper.actualizar_respaldo(
            [{"fecha": "2026-09-10", "nombre": "Leidsa", "numeros": ["01", "02", "03"]}]
        )
        self.assertEqual(archivos.leer_json(config.DB_BACKUP)[-1]["id"], 42)


class EscrituraAtomica(unittest.TestCase):
    def setUp(self):
        self.dir = tempfile.mkdtemp()

    def test_no_deja_el_archivo_a_medias_si_falla(self):
        destino = Path(self.dir) / "historial.txt"
        archivos.escribir_texto(destino, "contenido bueno")

        # Se simula un corte justo antes del renombrado, que es el punto donde
        # la escritura no atómica dejaría el archivo destruido.
        import unittest.mock
        with unittest.mock.patch(
            "vps.archivos.os.replace", side_effect=RuntimeError("corte simulado")
        ):
            with self.assertRaises(RuntimeError):
                archivos.escribir_texto(destino, "contenido nuevo a medias")

        self.assertEqual(destino.read_text(), "contenido bueno",
                         "el archivo anterior debe sobrevivir intacto")
        sobrantes = [f for f in os.listdir(self.dir) if f.endswith(".tmp")]
        self.assertEqual(sobrantes, [], "no deben quedar temporales huérfanos")

    def test_leer_json_tolera_archivo_corrupto(self):
        ruta = Path(self.dir) / "roto.json"
        ruta.write_text("{ esto no es json")
        self.assertEqual(archivos.leer_json(ruta, []), [])


class Cerrojo(unittest.TestCase):
    """El cron dispara cada minuto y el scraper tarda más de un minuto.

    Sin cerrojo, varias copias reescriben a la vez los archivos de historial.
    """

    AUXILIAR = str(Path(__file__).resolve().parent / "_auxiliar_cerrojo.py")

    def _lanzar(self, nombre: str, segundos: str):
        import subprocess
        proceso = subprocess.Popen(
            [sys.executable, self.AUXILIAR, nombre, segundos],
            stdout=subprocess.PIPE, stderr=subprocess.PIPE, text=True,
        )
        self.addCleanup(self._cerrar, proceso)
        return proceso

    @staticmethod
    def _cerrar(proceso) -> None:
        """Termina el proceso y cierra las tuberías para no dejar fd abiertos."""
        if proceso.poll() is None:
            proceso.kill()
            proceso.wait(timeout=10)
        for tuberia in (proceso.stdout, proceso.stderr):
            if tuberia is not None and not tuberia.closed:
                tuberia.close()

    def test_la_segunda_instancia_se_omite(self):
        primero = self._lanzar("suite_a", "5")
        self.assertEqual(primero.stdout.readline().strip(), "DENTRO",
                         "el primero debe entrar")

        segundo = self._lanzar("suite_a", "0")
        salida, _ = segundo.communicate(timeout=20)
        self.assertEqual(segundo.returncode, 0, "debe salir limpiamente, sin error")
        self.assertNotIn("DENTRO", salida,
                         "la segunda instancia no debe entrar en la sección crítica")

    def test_tras_liberarse_la_siguiente_entra(self):
        for intento in range(2):
            proceso = self._lanzar("suite_b", "0")
            salida, error = proceso.communicate(timeout=20)
            self.assertIn("DENTRO", salida,
                          f"la ejecución {intento + 1} debía entrar; stderr={error}")

    def test_cerrojos_distintos_no_se_estorban(self):
        primero = self._lanzar("suite_c", "5")
        self.assertEqual(primero.stdout.readline().strip(), "DENTRO")

        otro = self._lanzar("suite_d", "0")
        salida, _ = otro.communicate(timeout=20)
        self.assertIn("DENTRO", salida,
                      "un trabajo distinto no debe quedar bloqueado por otro")


if __name__ == "__main__":
    unittest.main()
