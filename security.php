<?php
/**
 * Cabeceras de seguridad comunes a todas las páginas públicas.
 *
 * Antes este archivo era un stub vacío ("seguridad desactivada temporalmente"),
 * de modo que los 6 `require_once 'security.php'` del frontend no hacían nada.
 */

if (!headers_sent()) {
    // Evita que el navegador adivine el tipo MIME (vector de XSS vía subida de archivos).
    header('X-Content-Type-Options: nosniff');

    // Impide que el sitio se embeba en un iframe ajeno (clickjacking).
    header('X-Frame-Options: SAMEORIGIN');

    // No filtrar la URL completa al navegar a dominios externos.
    header('Referrer-Policy: strict-origin-when-cross-origin');

    // Desactiva APIs del navegador que este sitio no usa.
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

    // Fuerza HTTPS durante un año una vez servido por TLS.
    if (!empty($_SERVER['HTTPS']) || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}
