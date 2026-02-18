<?php
declare(strict_types=1);

namespace Punkode;

/**
 * =============================================================================
 * FILE
 * -----------------------------------------------------------------------------
 * ANARKORE-D20-SETUP — Minimal D20 glue for Punkode/Anarkore
 * =============================================================================
 */

/* -------------------------------------------------------------------------- */
/* D20: Base URL override (WEB)                                                */
/* -------------------------------------------------------------------------- */
$isWeb = (defined('D20_PLATFORM') && D20_PLATFORM === 'web') || !empty($_SERVER['HTTP_HOST']);

if ($isWeb) {
    // Prefer D20_CARTELLA (calcolata dal tuo d20-setup) e fallback su APP_ENV
    $dir = defined('D20_CARTELLA') ? (string)D20_CARTELLA : (string)(getenv('APP_ENV') ?: '');

    $dir = trim($dir, '/');
    \Punkode\SETUP_PK::$url_dir_main = ($dir !== '' && $dir !== 'app') ? '/' . $dir . '/' : '/';
}

/* -------------------------------------------------------------------------- */
/* D20 version                                                                 */
/* -------------------------------------------------------------------------- */
if (!defined('VERSION_D20BS')) {
    define('VERSION_D20BS', '2.2.8');
}
