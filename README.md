# Lotería RD — Resultados y análisis estadístico

Sistema que recopila los resultados de las loterías de República Dominicana,
mantiene el historial y publica análisis estadísticos sobre él.

```
┌─────────────────────────────┐        ┌──────────────────────────────┐
│  VPS (Ubuntu)               │        │  Hosting (InfinityFree)      │
│                             │        │                              │
│  cron ──▶ vps/scraper.py    │        │  index.php      Calculadora  │
│             │               │        │  resultados.php Resultados   │
│             ├─ historiales  │  FTP   │  ia_*.php       Análisis     │
│             ├─ db_backup    │ ─────▶ │  api_*.php      JSON         │
│             └─ motores      │        │           │                  │
│                de análisis  │        │           ▼                  │
│                             │        │      MySQL (sorteos)         │
└─────────────────────────────┘        └──────────────────────────────┘
```

El cálculo pesado corre en el VPS; el hosting solo presenta resultados ya
calculados. Es una separación necesaria: el plan gratuito de InfinityFree
tiene cuotas estrictas de consultas por hora y tiempo de ejecución.

---

## Qué hace cada parte

### Recolección — `vps/scraper.py`

Lee las páginas de resultados de las 43 loterías del catálogo
(`vps/loterias.py`) y extrae los sorteos del bloque JSON-LD que publica el
origen. Usar datos estructurados en vez de parsear HTML hace la extracción
estable frente a cambios de maquetación.

En cada pasada:

1. Toma un cerrojo. Si la pasada anterior sigue viva, esta termina sin hacer
   nada — el rastreo puede durar más de un minuto y el cron dispara cada minuto.
2. Rastrea el día de hoy. Solo antes de las 10:00 rastrea también el día
   anterior, cuando el origen publica los resultados tardíos.
3. Compara con `db_backup.json`. **Si no hay sorteos nuevos, termina ahí**:
   no recalcula ni sube nada.
4. Si los hay, actualiza los historiales, dispara los motores de análisis y
   publica el JSON en el hosting.

### Motores de análisis

| Script | Qué calcula |
|---|---|
| `vps/update_prediccion.py` | Palés (pares dentro de un mismo sorteo) que llevan más tiempo sin aparecer |
| `vps/update_super_prediccion.py` | Súper palés: cruces entre las primeras posiciones de dos loterías el mismo día |
| `vps/update_quinielas.py` | Retraso de cada número 00–99 por posición |
| `vps/calc_stats.py` | Frecuencias, última aparición y números acompañantes |

Los tres primeros recorren el historial hacia atrás descartando combinaciones
ya salidas, hasta aislar la que lleva más tiempo sin aparecer.

> **Qué significan estos números.** Describen el historial: cuánto hace que
> salió cada combinación y con qué frecuencia aparece. No predicen el próximo
> sorteo. Cada sorteo es un evento independiente, y una combinación que lleva
> 800 días sin salir tiene exactamente la misma probabilidad que cualquier
> otra. La utilidad del sistema está en visualizar el historial, no en
> anticipar resultados.

### Frontend

| Archivo | Página |
|---|---|
| `index.php` | Calculadora de estrategias de inversión |
| `resultados.php` | Resultados del día y consulta por fecha |
| `ia_quinielas.php` | Retrasos y frecuencias de los números |
| `ia_prediccion_pale.php` | Palés pendientes |
| `ia_prediccion_super_pale.php` | Súper palés pendientes |
| `panel_admin.php` | Panel de administración (requiere autenticación) |

Las páginas de palés y súper palés comparten una sola plantilla
(`app/views/pagina_prediccion.php`) parametrizada.

---

## Estructura

```
├── app/                  Capa compartida de PHP — sin acceso web
│   ├── bootstrap.php     Arranque: configuración, zona horaria, errores
│   ├── Config.php        Secretos desde entorno o secrets.php
│   ├── Database.php      Conexión PDO
│   ├── Auth.php          Autenticación del panel
│   ├── Http.php          Cabeceras, JSON, escapado, cuotas
│   ├── Lotteries.php     Catálogo: familias, logos, horarios
│   ├── Ingest.php        Validación y guardado de sorteos
│   ├── Results.php       Consulta y ordenación
│   ├── ResultsPage.php   Modelo de vista de resultados
│   └── views/            Plantillas compartidas
├── assets/               CSS compilado, logos, imágenes
├── components/           Componentes de vista
├── vps/                  Scripts del servidor (no se despliegan al hosting)
├── scripts/              Utilidades de línea de comandos
├── tests/                Suites de PHP y Python
└── *.php                 Páginas y endpoints (raíz web)
```

`app/`, `vps/`, `tests/` y `scripts/` están bloqueados en `.htaccess`. En
InfinityFree no se puede colocar código fuera de la raíz web —`htdocs` es el
DocumentRoot— así que la separación se aplica a nivel de servidor.

---

## Puesta en marcha

### Requisitos

- PHP 8.1 o superior con `pdo_mysql`, `curl` y `mbstring`
- Python 3.9 o superior en el VPS
- Node 18 o superior para compilar la hoja de estilos

### 1. Credenciales

Ningún secreto vive en el repositorio. Copia la plantilla y rellénala:

```bash
cp secrets.example.php secrets.php
```

Genera el hash de la contraseña del panel:

```bash
php -r "echo password_hash('tu-clave', PASSWORD_DEFAULT), PHP_EOL;"
```

Y un token de ingesta:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

`secrets.php` está en `.gitignore` y excluido del despliegue: se sube al
hosting una sola vez, a mano.

### 2. Base de datos

```bash
php scripts/migrar.php
```

### 3. Hoja de estilos

```bash
npm install
npm run build:css      # genera assets/css/app.css
npm run watch:css      # recompila al guardar, durante el desarrollo
```

### 4. VPS

```bash
python3 -m pip install -r vps/requirements.txt
```

Credenciales en `/etc/lottery.env`, con permisos `600`:

```bash
LOTTERY_FTP_USER=...
LOTTERY_FTP_PASS=...
LOTTERY_INGEST_TOKEN=...          # el mismo que en secrets.php
LOTTERY_DATA_DIR=/home/ubuntu
```

Cron:

```cron
* * * * * set -a; . /etc/lottery.env; set +a; cd /home/ubuntu && python3 -m vps.scraper >> /var/log/lottery.log 2>&1
```

El cerrojo interno evita que dos pasadas se solapen, así que el intervalo de
un minuto es seguro.

### 5. Despliegue

`git push` a `master` dispara el workflow: compila el CSS y publica por FTP.
Necesita dos secretos en GitHub — *Settings → Secrets → Actions*:

- `FTP_USERNAME`
- `FTP_PASSWORD`

---

## Desarrollo

```bash
# Servidor local
php -S localhost:8000

# Pruebas
php tests/run.php                                  # PHP
python3 -m unittest discover -s tests/python -t .  # Python
```

Para ver los errores en pantalla durante el desarrollo, pon
`'app_env' => 'development'` en `secrets.php`. En producción los errores van
solo al log: un aviso de PDO mostrado al visitante filtra las credenciales de
conexión.

La integración continua comprueba en cada push la sintaxis de PHP y Python,
ejecuta ambas suites, verifica que el CSS compila y **falla si detecta
credenciales en el código**.

---

## Decisiones de diseño

**Los resultados llegan por FTP, no por HTTP.** InfinityFree sirve un desafío
JavaScript a todo cliente que no sea un navegador, así que el scraper no puede
llamar a `guardar_resultados.php` directamente. En su lugar deja un JSON por
FTP que la web absorbe en la siguiente carga de página. El endpoint HTTP existe
y funciona, pero solo es útil desde un origen que supere el desafío.

**El frontend lee JSON precalculado, no la base de datos.** Los análisis
tardarían más que el límite de ejecución de PHP en el hosting compartido.

**Respaldo en disco.** Si MySQL no responde —cuota horaria agotada, caída del
hosting—, la página sirve `db_backup.json` en lugar de mostrarse vacía.

**Ventana de 90 días en la consulta.** La portada solo necesita el último
resultado de cada lotería. Acotar el rango mantiene el coste constante aunque
el historial crezca durante años.

---

## Limitaciones conocidas

- **Font Awesome se carga desde CDN.** Sustituirlo por SVG en línea eliminaría
  la última dependencia externa; requiere reemplazar los iconos uno a uno.
- **`vps/calc_stats_extendido.py`** es una versión más rica de las estadísticas
  (coocurrencias, desglose por posición) que no está conectada al frontend.
- **El panel de administración usa HTTP Basic.** Suficiente para un solo
  administrador sobre HTTPS; si hacen falta varios usuarios o registro de
  actividad, hay que pasar a sesiones.
- **Cuotas del hosting gratuito.** El plan de InfinityFree limita consultas por
  hora; el respaldo en disco mitiga el efecto, pero no lo elimina.

---

## Aviso

Este sitio ofrece información estadística sobre sorteos ya celebrados. No
predice resultados futuros ni garantiza ganancia alguna. Juega con
responsabilidad.
