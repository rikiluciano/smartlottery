"""Cliente HTTP con reintentos para el scraper.

Antes cada petición era un `requests.get` suelto sin reintentos: un fallo de
red puntual perdía el resultado de esa lotería hasta la pasada siguiente.
Además se abría una conexión TCP nueva por cada una de las ~43 URLs.
"""

from __future__ import annotations

import requests
from requests.adapters import HTTPAdapter
from urllib3.util.retry import Retry

from . import config

_USER_AGENT = (
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/120.0 Safari/537.36"
)

_sesion: requests.Session | None = None


def sesion() -> requests.Session:
    """Sesión compartida: reutiliza conexiones y reintenta los fallos pasajeros."""
    global _sesion
    if _sesion is not None:
        return _sesion

    politica = Retry(
        total=config.HTTP_REINTENTOS,
        backoff_factor=1.0,                      # 1 s, 2 s, 4 s…
        status_forcelist=(429, 500, 502, 503, 504),
        allowed_methods=frozenset(["GET"]),
        raise_on_status=False,
    )

    s = requests.Session()
    s.headers.update({"User-Agent": _USER_AGENT, "Accept-Language": "es-DO,es;q=0.9"})
    adaptador = HTTPAdapter(max_retries=politica, pool_connections=4, pool_maxsize=8)
    s.mount("https://", adaptador)
    s.mount("http://", adaptador)

    _sesion = s
    return s


def obtener_html(url: str) -> str | None:
    """Devuelve el HTML de la URL, o None si no se pudo obtener."""
    try:
        respuesta = sesion().get(url, timeout=config.HTTP_TIMEOUT)
    except requests.RequestException as error:
        print(f"  error de red en {url}: {error}")
        return None

    if respuesta.status_code == 404:
        return None  # todavía no hay página para esa fecha: es normal
    if not respuesta.ok:
        print(f"  HTTP {respuesta.status_code} en {url}")
        return None

    return respuesta.text
