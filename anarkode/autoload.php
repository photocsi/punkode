<?php
/*
|-----------------------------------------------------------------------
| FILE: punkode/anarkode/autoload.php
| DESCRIPTION:
| EN: Anarkode entrypoint. Prefer Composer if available; otherwise defer
|     to NFF's own autoloader. Includes config before helpers.
| IT: Entrypoint Anarkode. Se c’è Composer lo usa; altrimenti delega
|     all’autoloader di NFF. Include la config prima degli helpers.
|-----------------------------------------------------------------------
*/

// 0) (Facoltativo) Evita di forzare PK_ENV qui: lasciamo che NFF decida.
// if (!defined('PK_ENV')) { define('PK_ENV', 'php'); }

/** 1) Composer autoload se esiste (prima locale, poi quello del progetto) */
$composerCandidates = [
    __DIR__ . '/vendor/autoload.php',          // vendor sotto anarkode/
    dirname(__DIR__) . '/vendor/autoload.php', // vendor del progetto
];
foreach ($composerCandidates as $p) {
    if (is_file($p)) { require_once $p; break; }
}

/** 2) Prova a usare direttamente l’autoloader di NoFutureFrame */
$nffAutoload = __DIR__ . '/nofutureframe/autoload.php';
if (is_file($nffAutoload)) {
    require_once $nffAutoload; // questo già include config.php (prima) e helpers.php (dopo)
    return;
}

/** 3) Fallback: autoloader minimale per NFF (senza lowercasing delle directory) */
$baseDir = __DIR__ . '/nofutureframe/src/';

// Carica la config PRIMA di tutto (così definisce PUNK_ENV)
$nffCfg = __DIR__ . '/nofutureframe/config.php';
if (is_file($nffCfg)) {
    require_once $nffCfg;
}

spl_autoload_register(function ($class) use ($baseDir) {
    $nsPrefix = 'Punkode\\Anarkode\\NoFutureFrame\\';

    // Non è NFF? Esci.
    if (strncmp($nsPrefix, $class, strlen($nsPrefix)) !== 0) return;

    // Esempio: Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_ResizeLaravel
    $relative   = substr($class, strlen($nsPrefix));
    $segments   = explode('\\', $relative);
    $shortClass = array_pop($segments);                    // PUNK_ResizeLaravel
    $subPath    = $segments ? implode('/', $segments).'/' : '';

    // Gestiamo solo classi/iface/trait con prefisso PUNK_
    if (strncmp('PUNK_', $shortClass, 5) !== 0) return;

    // Tipo: class / interface / trait + name base
    $nameRemainder = substr($shortClass, 5);
    $type = 'class';
    if (preg_match('/^(.*?)(?:_)?Interface$/', $nameRemainder, $m)) {
        $type     = 'interface';
        $nameBase = $m[1];
    } elseif (preg_match('/^(.*?)(?:_)?Trait$/', $nameRemainder, $m)) {
        $type     = 'trait';
        $nameBase = $m[1];
    } else {
        $nameBase = $nameRemainder;
    }

    // Slug kebab-case (solo per il nome file, non per le cartelle!)
    $slug = (strpos($nameBase, '_') !== false)
        ? strtolower(str_replace('_', '-', $nameBase))
        : strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $nameBase));

    $dir = rtrim($baseDir . $subPath, '/\\') . '/';
    $candidates = [
        "{$dir}{$type}-punk-{$slug}.php", // class-punk-*, interface-punk-*, trait-punk-*
        "{$dir}punk-{$slug}.php",         // fallback opzionale
    ];

    foreach ($candidates as $file) {
        if (is_file($file)) { require_once $file; return; }
    }
});

// Helpers DOPO la config (e dopo aver registrato l’autoload)
$nffHelpers = __DIR__ . '/nofutureframe/helpers.php';
if (is_file($nffHelpers)) {
    require_once $nffHelpers;
}
