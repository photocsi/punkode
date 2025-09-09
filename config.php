<?php
/*
|--------------------------------------------------------------------------
| FILE: punkode/config.php
| DESCRIPTION:
| EN: Global configuration for the PUNKODE meta-framework.
|     Defines runtime environment (PUNK_ENV) and log directory (PUNK_LOG_DIR).
| IT: Configurazione globale per il meta-framework PUNKODE.
|     Definisce l'ambiente di runtime (PUNK_ENV) e la cartella log (PUNK_LOG_DIR).
|--------------------------------------------------------------------------
| CONST / COSTANTI:
| - PUNK_ENV:
|     'wp'      → WordPress
|     'laravel' → Laravel
|     'php'     → PHP puro
|     'auto'    → tentativo di auto-rilevamento
|
| - PUNK_LOG_DIR:
|     Percorso assoluto della cartella log.
|     → Se definita a mano, ha priorità.
|     → Altrimenti, viene risolta in base a PUNK_ENV.
|--------------------------------------------------------------------------
*/

/**********************************************************************
 * ENVIRONMENT (PUNK_ENV)
 * EN: If not already defined, default to 'auto' and try to detect.
 * IT: Se non già definito, default a 'auto' e tenta di rilevare.
 **********************************************************************/
if (!defined('PUNK_ENV')) {
    if (defined('ABSPATH') || defined('WPINC') || function_exists('wp_upload_dir')) {
        define('PUNK_ENV', 'wp');
    } elseif (class_exists('\\Illuminate\\Support\\Facades\\App') || function_exists('base_path')) {
        define('PUNK_ENV', 'laravel');
    } else {
        define('PUNK_ENV', 'php');
    }
}

/**********************************************************************
 * LOG DIRECTORY (PUNK_LOG_DIR)
 * EN: If not already defined, pick sensible default per environment.
 * IT: Se non già definita, scegli default sensato per ambiente.
 **********************************************************************/
if (!defined('PUNK_LOG_DIR')) {
    switch (PUNK_ENV) {
        case 'wp':
            // EN: WordPress → wp-content/uploads/nff-logs
            // IT: WordPress → wp-content/uploads/nff-logs
            if (defined('WP_CONTENT_DIR')) {
                define('PUNK_LOG_DIR', rtrim(WP_CONTENT_DIR, '/\\') . '/uploads/nff-logs');
            } else {
                define('PUNK_LOG_DIR', __DIR__ . '/../logs');
            }
            break;

        case 'laravel':
            // EN: Laravel → storage/logs/nff
            // IT: Laravel → storage/logs/nff
            if (function_exists('storage_path')) {
                define('PUNK_LOG_DIR', rtrim(storage_path('logs/nff'), '/\\'));
            } elseif (function_exists('base_path')) {
                define('PUNK_LOG_DIR', rtrim(base_path('storage/logs/nff'), '/\\'));
            } else {
                define('PUNK_LOG_DIR', __DIR__ . '/../logs');
            }
            break;

        case 'php':
        default:
            // EN: Pure PHP → /nofutureframe/logs (next to library root)
            // IT: PHP puro → /nofutureframe/logs (accanto alla root libreria)
            define('PUNK_LOG_DIR', dirname(__DIR__) . '/logs');
            break;
    }
}

/**********************************************************************
 * ENSURE DIRECTORY EXISTS
 * EN: Create log directory if missing.
 * IT: Crea la cartella log se manca.
 **********************************************************************/
if (!is_dir(PUNK_LOG_DIR)) {
    @mkdir(PUNK_LOG_DIR, 0755, true);
}
