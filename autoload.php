<?php
/*
|-----------------------------------------------------------------------
| FILE: punkode/autoload.php
| DESCRIPTION:
| EN: PUNKODE entrypoint. Prefer Composer; otherwise delegate to Anarkode
|     autoloader which in turn loads NFF (config first, helpers after).
| IT: Entrypoint PUNKODE. Preferisci Composer; altrimenti delega ad
|     Anarkode che a sua volta carica NFF (prima config, poi helpers).
|-----------------------------------------------------------------------
*/

// (Facoltativo) Evita di forzare qui variabili d'ambiente: le decide NFF.
// require_once __DIR__ . '/config.php'; // ← Togli se dentro definisci costanti tipo PUNK_ENV

/** 1) Composer autoload se presente */
$composer = __DIR__ . '/vendor/autoload.php';
if (is_file($composer)) {
    require_once $composer;
}

/** 2) Prova a delegare all’autoloader di ANARKODE (preferibile) */
$anarkodeAutoload = __DIR__ . '/anarkode/autoload.php';
if (is_file($anarkodeAutoload)) {
    require_once $anarkodeAutoload; // questo già si occuperà di NFF (config prima, helpers dopo)
    return;
}

/** 3) Fallback diretto su NFF (se proprio manca anarkode/autoload.php) */
$nffAutoload = __DIR__ . '/anarkode/nofutureframe/autoload.php';
if (is_file($nffAutoload)) {
    require_once $nffAutoload; // include anche config.php e helpers.php nell’ordine giusto
    return;
}

/** 4) Fallback “di emergenza”: autoload minimale per NFF */
$nffBase = __DIR__ . '/anarkode/nofutureframe/';

// Carica config PRIMA (decide PUNK_ENV)
$nffCfg = $nffBase . 'config.php';
if (is_file($nffCfg)) {
    require_once $nffCfg;
}

// Autoloader minimale compatibile con i tuoi naming (nessun lowercase sulle cartelle!)
spl_autoload_register(function ($class) use ($nffBase) {
    $nsPrefix = 'Punkode\\Anarkode\\NoFutureFrame\\';
    if (strncmp($nsPrefix, $class, strlen($nsPrefix)) !== 0) return;

    $relative   = substr($class, strlen($nsPrefix));
    $segments   = explode('\\', $relative);
    $shortClass = array_pop($segments);              // es. PUNK_ResizeLaravel
    $subPath    = $segments ? implode('/', $segments) . '/' : '';

    if (strncmp('PUNK_', $shortClass, 5) !== 0) return;

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

    $slug = (strpos($nameBase, '_') !== false)
        ? strtolower(str_replace('_', '-', $nameBase))
        : strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $nameBase));

    $dir = rtrim($nffBase . 'src/' . $subPath, '/\\') . '/';
    $candidates = [
        "{$dir}{$type}-punk-{$slug}.php", // class-punk-*, interface-punk-*, trait-punk-*
        "{$dir}punk-{$slug}.php",         // fallback opzionale
    ];

    foreach ($candidates as $file) {
        if (is_file($file)) { require_once $file; return; }
    }
});

// Helpers DOPO la config (e dopo aver registrato l’autoload)
$nffHelpers = $nffBase . 'helpers.php';
if (is_file($nffHelpers)) {
    require_once $nffHelpers;
}
