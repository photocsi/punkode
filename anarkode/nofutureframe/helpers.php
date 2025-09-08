<?php
/*
|--------------------------------------------------------------------------
| FILE: helpers.php (NFF)
| DESCRIPTION:
| EN: Minimal, user-facing helpers to use Upload (ingest-only), Resize, and Save.
|     Each section is independent. Upload produces temp files + metadata;
|     no final storage here. Resize works on paths. Save persists finals/derivatives.
| IT: Helper minimi, orientati all'uso, per Upload (solo ingest), Resize e Save.
|     Ogni sezione è indipendente. Upload produce file temporanei + metadata;
|     nessun salvataggio finale qui. Resize lavora su path. Save persiste finali/derivati.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

/**********************************************************************
 * ENVIRONMENT
 * EN: Default environment. Allowed: "wp" | "php" | "laravel".
 * IT: Ambiente di default. Ammessi: "wp" | "php" | "laravel".
 **********************************************************************/
if (!defined('PUNK_ENV')) {
    define('PUNK_ENV', 'wp');
}

/**********************************************************************
 * IMPORTS (interfaces & core)
 **********************************************************************/
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ResizeInterface;
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_LogInterface;

use Punkode\Anarkode\NoFutureFrame\Core\PUNK_UploadCore;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_UploadUtils;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_SaveUtils;

// Core pure functions (normalization/paths)
use function Punkode\Anarkode\NoFutureFrame\Core\punk_normalize_files;
use function Punkode\Anarkode\NoFutureFrame\Core\punk_build_rel_dir;

/**********************************************************************
 * ENV SELECTORS
 * EN: Pick concrete services by environment (when needed).
 * IT: Seleziona i servizi concreti per ambiente (quando serve).
 **********************************************************************/

/**
 * EN: Resize/Image service selector.
 * IT: Selettore servizio Resize/Immagini.
 */
if (!function_exists('punk_env_image_service')) {
    function punk_env_image_service(?string $disk = null): PUNK_ResizeInterface
    {
        // WordPress
        if (PUNK_ENV === 'wp') {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\wp\PUNK_ResizeWp();
        }

        // Laravel / PHP
        $extra = __DIR__ . '/src/environments/phplaravel';
        if (is_dir($extra)) {
            if (PUNK_ENV === 'laravel' && class_exists('Illuminate\\Support\\Facades\\Storage')) {
                return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_ResizeLaravel($disk);
            }
            // Fallback: pure PHP adapter hosted under PhpLaravel namespace (compat layer)
            return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_ResizePhp();
        }

        throw new \RuntimeException('NFF: no suitable ImageService for env: ' . PUNK_ENV);
    }
}

/**
 * EN: Upload façade selector (ingest-only).
 *     Upload is environment-agnostic: we wire Core + UploadUtils, no storage.
 * IT: Selettore facciata Upload (solo ingest).
 *     Upload è agnostico all’ambiente: colleghiamo Core + UploadUtils, senza storage.
 */
if (!function_exists('punk_env_upload_utils')) {
    function punk_env_upload_utils(array $defaults = []): PUNK_UploadUtils
    {
        $core = new PUNK_UploadCore();
        return new PUNK_UploadUtils($core, $defaults);
    }
}

/**
 * EN: Log service selector (optional).
 * IT: Selettore servizio Log (opzionale).
 */
if (!function_exists('punk_env_log_service')) {
    function punk_env_log_service(): PUNK_LogInterface
    {
        if (PUNK_ENV === 'wp') {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\wp\PUNK_LogWp();
        }

        $extra = __DIR__ . '/src/environments/phplaravel';
        if (is_dir($extra)) {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_LogPhp();
        }

        // Last resort: silent no-op logger
        return new class implements PUNK_LogInterface {
            public function punk_log(string $message, string $level = 'info'): void {}
        };
    }
}

/**********************************************************************
 * HIGH-LEVEL: UPLOAD (INGEST-ONLY)
 * EN: Accepts $_FILES-like (single/multi) or a normalized list.
 *     Returns standardized results with tmp_path and suggested_relative.
 * IT: Accetta $_FILES-like (singolo/multiplo) o lista normalizzata.
 *     Ritorna risultati standard con tmp_path e suggested_relative.
 **********************************************************************/

/**
 * FUNCTION: punk_upload_only
 * EN: Perform ONLY the ingestion/validation. No final storage here.
 *     $files can be:
 *       - a single $_FILES[...] array,
 *       - a multiple $_FILES structure,
 *       - or a normalized flat list (name,type,tmp_name,error,size).
 *     $opts are merged into UploadUtils defaults (e.g., allowed_mimes, max_mb,
 *     randomize_name, dest_scheme 'date'|'flat'|callable).
 * IT: Esegue SOLO ingest/validazione. Nessun salvataggio finale qui.
 *     $files può essere:
 *       - un singolo array $_FILES[...],
 *       - una struttura multipla di $_FILES,
 *       - oppure una lista piatta normalizzata (name,type,tmp_name,error,size).
 *     $opts si uniscono ai default di UploadUtils (es. allowed_mimes, max_mb,
 *     randomize_name, dest_scheme 'date'|'flat'|callable).
 */
if (!function_exists('punk_upload_only')) {
    function punk_upload_only(array $files, array $opts = []): array
    {
        // Normalize any $_FILES shape to a flat list
        $flat = punk_normalize_files($files);

        // Build upload façade (ingest-only)
        $upload = punk_env_upload_utils([
            // Set sensible global defaults here if you want
            // Imposta qui eventuali default globali
        ]);

        // Delegate to UploadUtils (maps path/url=null; exposes tmp_path & metadata)
        return $upload->punk_upload_files($flat, $opts);
    }
}

/**********************************************************************
 * HIGH-LEVEL: RESIZE
 * EN: Simple wrappers around the environment image service.
 * IT: Wrapper semplici attorno al servizio immagini per ambiente.
 **********************************************************************/

/**
 * FUNCTION: punk_resize
 * EN: Resize a single image. Returns ['path','width','height'] or false.
 * IT: Resize singolo. Ritorna ['path','width','height'] oppure false.
 */
if (!function_exists('punk_resize')) {
    function punk_resize(string $src, string $dest, int $w, int $h, int $quality = 90, ?string $disk = null): array|false
    {
        return punk_env_image_service($disk)->punk_resize_to($src, $dest, $w, $h, $quality);
    }
}

/**
 * FUNCTION: punk_resize_batch
 * EN: Batch resize. $jobs = [['src'=>..., 'dest'=>..., 'w'=>..., 'h'=>..., 'quality'=>?, 'disk'=>?], ...]
 * IT: Resize in batch. $jobs = [['src'=>..., 'dest'=>..., 'w'=>..., 'h'=>..., 'quality'=>?, 'disk'=>?], ...]
 */
if (!function_exists('punk_resize_batch')) {
    function punk_resize_batch(array $jobs): array
    {
        $out = [];
        foreach ($jobs as $job) {
            $src     = (string)($job['src'] ?? '');
            $dest    = (string)($job['dest'] ?? '');
            $w       = (int)($job['w'] ?? 0);
            $h       = (int)($job['h'] ?? 0);
            $quality = isset($job['quality']) ? (int)$job['quality'] : 90;
            $disk    = $job['disk'] ?? null;

            if ($src === '' || $dest === '' || $w <= 0 || $h <= 0) {
                $out[] = false;
                continue;
            }
            try {
                $res = punk_env_image_service($disk)->punk_resize_to($src, $dest, $w, $h, $quality);
                $out[] = $res ?: false;
            } catch (\Throwable $e) {
                punk_log('[PUNK_Resize] ' . $e->getMessage(), 'error');
                $out[] = false;
            }
        }
        return $out;
    }
}

/**********************************************************************
 * HIGH-LEVEL: SAVE
 * EN: Save/Derivatives. Works with an already-uploaded original.
 * IT: Salvataggio/Derivati. Lavora su un originale già caricato.
 **********************************************************************/

/**
 * FUNCTION: punk_save
 * EN: Generate derivatives (sizes) from an already-uploaded original and
 *     persist a manifest (strategy depends on PUNK_SaveUtils).
 *     $sizes = ['thumb'=>[320,320,'jpg'], 'large'=>[1200,1200,'webp'], ...]
 *     Returns manifest array or false.
 * IT: Genera derivati (sizes) da un originale già caricato e salva un manifest
 *     (strategia definita da PUNK_SaveUtils). Ritorna un manifest o false.
 */
if (!function_exists('punk_save')) {
    function punk_save(string $original_path, array $sizes, int $quality = 85, ?string $disk = null): array|false
    {
        if (!is_file($original_path)) {
            punk_log('punk_save: original not found: ' . $original_path, 'error');
            return false;
        }

        $dest_dir = \dirname($original_path);
        $basename = \basename($original_path);

        try {
            $resizer = punk_env_image_service($disk);
            $save    = new PUNK_SaveUtils($resizer);
            return $save->punk_save_image($original_path, $dest_dir, $basename, $sizes, $quality);
        } catch (\Throwable $e) {
            punk_log('[PUNK_Save] ' . $e->getMessage(), 'error');
            return false;
        }
    }
}

/**
 * FUNCTION: punk_save_batch
 * EN: Batch save. $originals can be a single path or a list of paths.
 * IT: Save in batch. $originals può essere un path singolo o una lista di path.
 */
if (!function_exists('punk_save_batch')) {
    function punk_save_batch(string|array $originals, array $sizes, int $quality = 85, ?string $disk = null): array
    {
        $list = \is_array($originals) ? $originals : [$originals];
        $out  = [];
        foreach ($list as $orig) {
            $out[] = punk_save((string)$orig, $sizes, $quality, $disk);
        }
        return $out;
    }
}

/**********************************************************************
 * LOGGING (optional)
 **********************************************************************/
if (!function_exists('punk_log')) {
    function punk_log(string $msg, string $level = 'info'): void
    {
        punk_env_log_service()->punk_log($msg, $level);
    }
}

/**********************************************************************
 * OPTIONAL UTILITIES
 * EN: Small convenience helpers for URL/manifest mapping (WP-focused sample).
 * IT: Piccoli helper di comodo per mapping URL/manifest (esempio centrato su WP).
 **********************************************************************/
if (!function_exists('punk_manifest_sidecar_path')) {
    function punk_manifest_sidecar_path(string $original_path): string
    {
        $dir  = \dirname($original_path);
        $base = \pathinfo($original_path, \PATHINFO_FILENAME);
        return rtrim($dir, '/\\') . '/' . $base . '.manifest.json';
    }
}

if (!function_exists('punk_path_to_url')) {
    function punk_path_to_url(string $abs_path): string
    {
        if (PUNK_ENV === 'wp' && \function_exists('wp_get_upload_dir')) {
            $uploads = \wp_get_upload_dir();
            $basedir = rtrim($uploads['basedir'] ?? '', '/\\');
            $baseurl = rtrim($uploads['baseurl'] ?? '', '/');
            if ($basedir && str_starts_with($abs_path, $basedir)) {
                $rel = str_replace('\\', '/', substr($abs_path, strlen($basedir)));
                return $baseurl . $rel;
            }
        }
        return '';
    }
}
