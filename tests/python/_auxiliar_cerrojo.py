"""Proceso auxiliar para probar el cerrojo desde otro intérprete.

Uso:  python3 tests/python/_auxiliar_cerrojo.py <nombre> <segundos>

Imprime DENTRO si logró entrar en la sección crítica. Si otra instancia
tiene el cerrojo, termina en silencio con código 0.
"""

import sys
import time
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parents[2]))

from vps.cerrojo import instancia_unica  # noqa: E402

with instancia_unica(sys.argv[1]):
    print("DENTRO", flush=True)
    time.sleep(float(sys.argv[2]))
