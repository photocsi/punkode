<?php
/*
|--------------------------------------------------------------------------
| FILE: nofutureframe/autoload.php
| DESCRIPTION:
| EN: Standalone autoloader for the NFF module.
| IT: Autoloader standalone per il modulo NFF.
|--------------------------------------------------------------------------
*/
spl_autoload_register(function ($class) {
    $prefix = 'Punkode\\Anarkode\\NoFutureFrame\\';
    $base   = __DIR__ . '/src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = substr($class, strlen($prefix));
    $file = $base . str_replace('\\','/',$rel) . '.php';
    $file = strtolower(str_replace('PUNK_', 'class-punk-', $file));
    if (file_exists($file)) require_once $file;
});
require_once __DIR__ . '/helpers.php';
