<?php
/*
|--------------------------------------------------------------------------
| FILE: punkode/autoload.php
| DESCRIPTION:
| EN: PUNKODE entrypoint. Composer first, then internal autoloader.
| IT: Entrypoint PUNKODE. Prima Composer, poi autoloader interno.
|--------------------------------------------------------------------------
*/
require_once __DIR__ . '/config.php';

$composer = __DIR__ . '/vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
} else {
    // Map submodules (currently: Anarkode\NoFutureFrame)
    spl_autoload_register(function ($class) {
        $prefix = 'Punkode\\Anarkode\\NoFutureFrame\\';
        $base   = __DIR__ . '/anarkode/nofutureframe/src/';
        if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
        $rel = substr($class, strlen($prefix));
        $file = $base . str_replace('\\','/',$rel) . '.php';
        $file = strtolower(str_replace('PUNK_', 'class-punk-', $file));
        if (file_exists($file)) require_once $file;
    });

    // Load helpers for modules we expose globally
    require_once __DIR__ . '/anarkode/nofutureframe/helpers.php';
}
