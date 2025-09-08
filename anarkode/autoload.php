<?php
/*
|--------------------------------------------------------------------------
| FILE: punkode/anarkode/autoload.php
| DESCRIPTION:
| EN: Anarkode entrypoint. Use ONLY this folder by requiring this file.
|     Composer if available; otherwise internal autoloader.
| IT: Entrypoint Anarkode. Usa SOLO questa cartella includendo questo file.
|     Se Composer è disponibile; altrimenti autoloader interno.
| WHAT IT LOADS / COSA CARICA:
| - PSR-4 map: Punkode\Anarkode\NoFutureFrame\ → nofutureframe/src/
| - Helpers (punk_resize, punk_log) from NFF
|--------------------------------------------------------------------------
*/
if (!defined('PK_ENV')) { define('PK_ENV', 'wp'); }

// 1) Composer autoload if anarkode/vendor exists (rare) or project vendor nearby
$paths = [
    __DIR__ . '/vendor/autoload.php',           // anarkode-local vendor
    dirname(__DIR__) . '/vendor/autoload.php',  // project vendor if punkode/ not included
];
foreach ($paths as $p) {
    if (file_exists($p)) { require_once $p; break; }
}

// 2) Internal autoloader for NFF
spl_autoload_register(function ($class) {
    $prefix = 'Punkode\\Anarkode\\NoFutureFrame\\';
    $base   = __DIR__ . '/nofutureframe/src/';
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;
    $rel = substr($class, strlen($prefix));
    $file = $base . str_replace('\\','/',$rel) . '.php';
    $file = strtolower(str_replace('PUNK_', 'class-punk-', $file));
    if (file_exists($file)) require_once $file;
});

// 3) Load helpers of each module we want globally
require_once __DIR__ . '/nofutureframe/helpers.php';
