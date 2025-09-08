<?php
/*
|--------------------------------------------------------------------------
| FILE: helpers.php (NFF)
| DESCRIPTION:
| EN: Global helpers for NoFutureFrame: select Image and Log services
|     depending on environment (WordPress / PHP / Laravel) and provide
|     easy functions for resizing images and logging.
| IT: Helper globali per NoFutureFrame: selezione dei servizi Immagini e Log
|     in base all'ambiente (WordPress / PHP puro / Laravel) e funzioni di
|     comodo per ridimensionamento immagini e logging.
|--------------------------------------------------------------------------
*/

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ImageServiceInterface;
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_LogServiceInterface;

/**********************************************************************
 * ENVIRONMENT CONSTANT
 * EN: If not already defined, set default environment to "wp".
 *     Allowed values: "wp", "php", "laravel".
 * IT: Se non è già definita, imposta l'ambiente predefinito su "wp".
 *     Valori possibili: "wp", "php", "laravel".
 **********************************************************************/
if (!defined('PUNK_ENV')) {
    define('PUNK_ENV', 'wp');
}

/**********************************************************************
 * FUNCTION: punk_env_image_service()
 * EN: Returns the proper Image Service instance for the current environment.
 *     Optionally accepts $disk (Laravel): e.g. 'local', 'public', 's3'.
 *     Logic:
 *       - WordPress → WP implementation
 *       - Laravel   → Laravel adapter (passes $disk)
 *       - PHP       → pure PHP implementation
 *       - Otherwise → throws exception
 * IT: Restituisce il servizio Immagini adatto per l'ambiente corrente.
 *     Accetta opzionalmente $disk (Laravel): es. 'local', 'public', 's3'.
 *     Logica:
 *       - WordPress → implementazione WP
 *       - Laravel   → adapter Laravel (propaga $disk)
 *       - PHP       → implementazione PHP pura
 *       - Altrimenti → eccezione
 **********************************************************************/
if (!function_exists('punk_env_image_service')) {
    function punk_env_image_service(?string $disk = null): PUNK_ImageServiceInterface
    {
        // WordPress
        if (PUNK_ENV === 'wp') {
            return new Punkode\Anarkode\NoFutureFrame\Environments\wp\PUNK_Image_Wp();
        }

        // Percorso environment PHP/Laravel
        $extra = __DIR__ . '/src/environments/phplaravel';
        if (is_dir($extra)) {
            // Laravel (con Facade disponibile): passa il disco (local/public/s3)
            if (PUNK_ENV === 'laravel' && class_exists('Illuminate\\Support\\Facades\\Storage')) {
                return new Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_Image_Laravel($disk);
            }
            // PHP puro
            if (PUNK_ENV === 'php') {
                return new Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_Image_Php();
            }
        }

        // Nessun servizio valido trovato
        throw new RuntimeException('NFF: no suitable ImageService for env: ' . PUNK_ENV);
    }
}

/**********************************************************************
 * FUNCTION: punk_env_log_service()
 * EN: Returns the proper Log Service instance for the current environment.
 *     Logic:
 *       - WordPress → WP logger
 *       - PHP/Laravel → generic PHP logger
 *       - Otherwise → null logger (does nothing)
 * IT: Restituisce il servizio Log adatto per l'ambiente corrente.
 *     Logica:
 *       - WordPress → logger WP
 *       - PHP/Laravel → logger PHP generico
 *       - Altrimenti → logger nullo (non fa nulla)
 **********************************************************************/
if (!function_exists('punk_env_log_service')) {
    function punk_env_log_service(): PUNK_LogServiceInterface
    {
        // WordPress
        if (PUNK_ENV === 'wp') {
            return new Punkode\Anarkode\NoFutureFrame\Environments\wp\PUNK_Log_Wp();
        }

        // PHP/Laravel
        $extra = __DIR__ . '/src/environments/phplaravel';
        if (is_dir($extra)) {
            return new Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_Log_Php();
        }

        // Fallback: logger nullo
        return new class implements PUNK_LogServiceInterface {
            public function punk_log(string $message, string $level = 'info'): void
            {
                // EN: Silent fallback.
                // IT: Fallback silenzioso.
            }
        };
    }
}

/**********************************************************************
 * FUNCTION: punk_resize()
 * EN: Resizes an image using the correct service.
 *     Parameters:
 *       $src, $dest, $w, $h, $quality(=90), $disk(=null for Laravel)
 *     - On Laravel with remote disks (e.g. S3), the adapter will:
 *       download → resize locally → upload result.
 * IT: Ridimensiona un'immagine usando il servizio corretto.
 *     Parametri:
 *       $src, $dest, $w, $h, $quality(=90), $disk(=null per Laravel)
 *     - Su Laravel con dischi remoti (es. S3), l'adapter:
 *       scarica → elabora in locale → carica il risultato.
 **********************************************************************/
if (!function_exists('punk_resize')) {
    function punk_resize(
        string $src,
        string $dest,
        int $w,
        int $h,
        int $quality = 90,
        ?string $disk = null
    ): array|false {
        return punk_env_image_service($disk)->punk_resizeTo($src, $dest, $w, $h, $quality);
    }
}

/**********************************************************************
 * FUNCTION: punk_log()
 * EN: Sends a log message to the appropriate logger.
 * IT: Invia un messaggio di log al logger appropriato.
 **********************************************************************/
if (!function_exists('punk_log')) {
    function punk_log(string $msg, string $level = 'info'): void
    {
        punk_env_log_service()->punk_log($msg, $level);
    }
}
