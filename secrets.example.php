<?php
/**
 * Plantilla de credenciales. Copiar a `secrets.php` y rellenar.
 *
 * `secrets.php` está en .gitignore y en la lista de exclusión del deploy FTP:
 * se sube UNA sola vez al hosting y no se versiona jamás.
 */
return [
    'db_host'     => 'sql202.infinityfree.com',
    'db_name'     => 'if0_XXXXXXXX_resultados_db',
    'db_user'     => 'if0_XXXXXXXX',
    'db_pass'     => 'PON_AQUI_LA_CLAVE',

    // Token compartido con los scrapers del VPS. Generar con:
    //   php -r "echo bin2hex(random_bytes(32));"
    'ingest_token' => 'PON_AQUI_UN_TOKEN_ALEATORIO_LARGO',

    // Clave de OpenRouter. Debe usarse SOLO en servidor, nunca en JS.
    'openrouter_key' => 'sk-or-v1-...',
];
