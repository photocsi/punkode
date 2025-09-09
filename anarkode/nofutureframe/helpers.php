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
 * EN: Default environment only if not already defined by config.php.
 * IT: Ambiente di fallback solo se non già definito da config.php.
 **********************************************************************/
if (!defined('PUNK_ENV')) {
    define('PUNK_ENV', 'php'); // 'php' | 'laravel' | 'wp'
}

/**********************************************************************
 * IMPORTS (interfaces & core)
 **********************************************************************/
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_Resize;
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_Log;
use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_Save;

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

// Carica le funzioni core (le funzioni NON passano dall'autoloader)
$__punk_fn = __DIR__ . '/src/Core/punk-files-utils.php';
if (!is_file($__punk_fn)) {
    throw new \RuntimeException('NFF: missing file ' . $__punk_fn);
}
require_once $__punk_fn;
try {
    $rf = new \ReflectionFunction('\\Punkode\\Anarkode\\NoFutureFrame\\Core\\punk_build_rel_dir');
    error_log('USO punk_build_rel_dir da ' . $rf->getFileName() . ':' . $rf->getStartLine());
    $lines = @file($rf->getFileName());
    if ($lines && isset($lines[96])) { // 97 = index 96
        error_log('Contenuto linea 97: ' . rtrim($lines[96]));
    }
} catch (\Throwable $e) {
    error_log('Reflection failed: ' . $e->getMessage());
}




/**
 * EN: Resize/Image service selector.
 * IT: Selettore servizio Resize/Immagini.
 */
if (!function_exists('punk_env_image_service')) {
    function punk_env_image_service(?string $disk = null): PUNK_Resize
    {
        if (PUNK_ENV === 'wp') {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\Wp\PUNK_ResizeWp();
        }
        if (PUNK_ENV === 'laravel' && class_exists('\Illuminate\Support\Facades\Storage')) {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_ResizeLaravel($disk);
        }
        // PHP puro (adapter nel namespace PhpLaravel come compat layer)
        return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_ResizePhp();
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
 * EN: Log service selector.
 * IT: Selettore servizio Log.
 */
if (!function_exists('punk_env_log_service')) {
    function punk_env_log_service(): PUNK_Log
    {
        if (PUNK_ENV === 'wp') {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\Wp\PUNK_LogWp();
        }
        // PHP & Laravel (logger semplice su file)
        return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_LogPhp();
    }
}

/**
 * EN: Save-to-storage service selector.
 * IT: Selettore servizio di salvataggio finale su storage.
 */
if (!function_exists('punk_env_save_service')) {
    function punk_env_save_service(?string $disk = null): PUNK_Save
    {
        if (PUNK_ENV === 'wp') {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\Wp\PUNK_SaveWp();
        }
        if (PUNK_ENV === 'laravel' && class_exists('\Illuminate\Support\Facades\Storage')) {
            return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_SaveLaravel($disk ?? 'public');
        }
        return new \Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel\PUNK_SavePhp();
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
 * IT: Esegue SOLO ingest/validazione. Nessun salvataggio finale qui.
 */
if (!function_exists('punk_upload_only')) {
    function punk_upload_only(array $files, array $opts = []): array
    {
        // Normalize any $_FILES shape to a flat list
        $flat = punk_normalize_files($files);

        // Build upload façade (ingest-only)
        $upload = punk_env_upload_utils([
            // EN: set global defaults here if you want
            // IT: imposta eventuali default globali qui
        ]);

        // Delegate to UploadUtils (exposes tmp_path & metadata)
        return $upload->punk_upload_files($flat, $opts);
    }
}

/**
 * FUNCTION: punk_upload
 * EN: High-level alias of upload-only (ingest/validate), accepts $_FILES single/multi.
 * IT: Alias alto livello di upload-only (ingest/validate), accetta $_FILES singolo/multiplo.
 */
if (!function_exists('punk_upload')) {
    function punk_upload(array $files, array $opts = []): array
    {
        return punk_upload_only($files, $opts);
    }
}

/**********************************************************************
 * HIGH-LEVEL: RESIZE
 * EN: Thin wrappers around the environment image service.
 * IT: Wrapper sottili attorno al servizio immagini per ambiente.
 **********************************************************************/

/**
 * FUNCTION: punk_resize
 * EN: Resize a single image to dest. Returns ['path','width','height'] or false.
 * IT: Resize singolo verso dest. Ritorna ['path','width','height'] oppure false.
 */
if (!function_exists('punk_resize')) {
    function punk_resize(string $src, string $dest, int $w, int $h, int $quality = 90, ?string $disk = null): array|false
    {
        return punk_env_image_service($disk)->punk_resize_to($src, $dest, $w, $h, $quality);
    }
}

/**
 * FUNCTION: punk_resize_batch
 * EN: Batch resize with explicit jobs.
 * IT: Resize in batch con job espliciti.
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

/**
 * FUNCTION: punk_resize_versions
 * EN: Create multiple resized versions per input.
 *     - $items: output from punk_upload()/punk_upload_only() OR array of absolute paths
 *     - $sizes: map label => int|maxSide  OR label => ['w'=>..,'h'=>..,'quality'=>..]
 *     - $opts:  ['dest_dir' => '/abs/path' (default: sys_temp_dir), 'disk' => null]
 * RETURNS: array like:
 *   [
 *     [
 *       'source' => '/abs/src.jpg',
 *       'versions' => ['sm'=>['path'=>...,'width'=>...,'height'=>...], ...]
 *     ],
 *     ...
 *   ]
 * IT: Crea più versioni ridotte per input multipli (da upload o da path).
 */
if (!function_exists('punk_resize_versions')) {
    function punk_resize_versions(array $items, array $sizes, array $opts = []): array
    {
        $destDir = isset($opts['dest_dir']) && is_string($opts['dest_dir']) && $opts['dest_dir'] !== ''
            ? rtrim($opts['dest_dir'], "/\\")
            : sys_get_temp_dir();

        $disk = $opts['disk'] ?? null;

        // Normalizza sorgenti: accetta output di upload_* oppure array di path
        $sources = [];
        $looksLikeUpload = isset($items[0]['meta']) || (isset($items[0][0]['meta']));
        if ($looksLikeUpload) {
            foreach ($items as $row) {
                if (isset($row['meta']['tmp_path'])) {
                    $sources[] = [
                        'path' => (string)$row['meta']['tmp_path'],
                        'name' => (string)($row['meta']['safe_name'] ?? $row['name'] ?? basename($row['meta']['tmp_path'])),
                    ];
                }
            }
        } else {
            foreach ($items as $p) {
                $p = (string)$p;
                if (is_file($p)) {
                    $sources[] = [
                        'path' => $p,
                        'name' => pathinfo($p, PATHINFO_FILENAME),
                    ];
                }
            }
        }

        $out = [];
        foreach ($sources as $src) {
            $srcPath = $src['path'];
            $name    = preg_replace('/\.[^.]+$/', '', $src['name']);
            $ext     = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION)) ?: 'jpg';

            $one = ['source' => $srcPath, 'versions' => []];

            foreach ($sizes as $label => $def) {
                // Consenti sia formato semplice (int => lato max) sia array dettagliato
                if (is_array($def)) {
                    $w = (int)($def['w'] ?? 0);
                    $h = (int)($def['h'] ?? 0);
                    $q = isset($def['quality']) ? (int)$def['quality'] : 90;
                    if ($w <= 0 && $h > 0) { $w = $h; }
                    if ($h <= 0 && $w > 0) { $h = $w; }
                } else {
                    $w = (int)$def;
                    $h = (int)$def;
                    $q = 90;
                }
                if ($w <= 0 || $h <= 0) {
                    $one['versions'][$label] = ['error' => 'invalid size'];
                    continue;
                }

                $dest = $destDir . DIRECTORY_SEPARATOR . $name . '-' . $label . '.' . $ext;
                try {
                    $res = punk_env_image_service($disk)->punk_resize_to($srcPath, $dest, $w, $h, $q);
                    if ($res && is_file($dest)) {
                        $one['versions'][$label] = [
                            'path'   => $dest,
                            'width'  => $res['width']  ?? null,
                            'height' => $res['height'] ?? null
                        ];
                    } else {
                        $one['versions'][$label] = ['error' => 'resize failed'];
                    }
                } catch (\Throwable $e) {
                    punk_log('[punk_resize_versions] ' . $e->getMessage(), 'error');
                    $one['versions'][$label] = ['error' => $e->getMessage()];
                }
            }

            $out[] = $one;
        }

        return $out;
    }
}

/**********************************************************************
 * HIGH-LEVEL: SAVE (DERIVATIVES/MANIFEST) — editoriale
 * EN: Works with an already-uploaded original file on disk.
 * IT: Lavora con un originale già presente su disco.
 **********************************************************************/

/**
 * FUNCTION: punk_save
 * EN: Generate derivatives (sizes) from an already-uploaded original and
 *     persist a manifest (strategy depends on PUNK_SaveUtils).
 *     $sizes = ['thumb'=>[320,320,'jpg'], 'large'=>[1200,1200,'webp'], ...]
 *     Returns manifest array or false.
 * IT: Genera derivati (sizes) da un originale già caricato e salva un manifest.
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
 * SAVE TO STORAGE (final copy/move from temp)
 * EN: Persist resized temp files into final storage (PHP path, WP uploads, Laravel disk).
 * IT: Salva i file temporanei ridimensionati nello storage finale (path PHP, uploads WP, disk Laravel).
 **********************************************************************/

/**
 * FUNCTION: punk_save_from_temp
 * EN: Single temp → final destination (abs path in PHP; relative path in WP/Laravel).
 * IT: Singolo temp → destinazione finale (path assoluto in PHP; path relativo in WP/Laravel).
 */
if (!function_exists('punk_save_from_temp')) {
    function punk_save_from_temp(string $tmp, string $dest, array $opts = []): array|false
    {
        return punk_env_save_service($opts['disk'] ?? null)->punk_save_from_temp($tmp, $dest, $opts);
    }
}

/**
 * FUNCTION: punk_save_all
 * EN: Persist a whole set of resized versions into final storage.
 *     - $versionSets: output of punk_resize_versions()
 *     - $opts: ['dest_base' => '/abs/dir' OR 'folder/rel' (WP/Laravel),
 *               'disk' => 'public', 'overwrite' => true]
 * RETURNS: same structure with final info per version (adapter-dependent).
 * IT: Salva in blocco tutte le versioni ridotte nella destinazione finale.
 */
if (!function_exists('punk_save_all')) {
    function punk_save_all(array $versionSets, array $opts = []): array
    {
        $destBase  = rtrim((string)($opts['dest_base'] ?? ''), "/\\");
        $overwrite = (bool)($opts['overwrite'] ?? false);
        $disk      = $opts['disk'] ?? null;

        $saveOne = function (string $tmp, string $relOrAbs) use ($disk, $overwrite, $opts) {
            $o = $opts;
            $o['disk'] = $disk;
            $o['overwrite'] = $overwrite;
            return punk_save_from_temp($tmp, $relOrAbs, $o);
        };

        $out = [];
        foreach ($versionSets as $set) {
            $entry = [
                'source'   => $set['source'] ?? null,
                'versions' => [],
            ];
            foreach (($set['versions'] ?? []) as $label => $v) {
                if (!empty($v['path']) && is_file($v['path'])) {
                    $baseName = basename($v['path']);
                    $dest     = $destBase ? $destBase . DIRECTORY_SEPARATOR . $baseName : $baseName;
                    $res      = $saveOne($v['path'], $dest);
                    $entry['versions'][$label] = $res ?: ['error' => 'save failed', 'tmp' => $v['path']];
                } else {
                    $entry['versions'][$label] = ['error' => 'missing tmp'];
                }
            }
            $out[] = $entry;
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

/**
 * FUNCTION: punk_process_upload
 * EN: One-shot helper: upload files, generate multiple versions, save finals.
 * IT: Helper unico: carica i file, genera versioni multiple, salva i finali.
 *
 * @param array $files  $_FILES[...] (singolo o multiplo)
 * @param array $sizes  ['sm'=>800,'md'=>1600,'lg'=>2400]  (lato max o array dettagli)
 * @param array $opts   ['dest_base'=>..., 'overwrite'=>true, 'disk'=>..., 'dest_dir'=>...]
 * @return array        Risultati normalizzati per viste (name, versions[sm|md|lg][final])
 */
if (!function_exists('punk_process_upload')) {
    function punk_process_upload(array $files, array $sizes, array $opts = []): array
    {
        // 1) Upload
        $uploaded = punk_upload($files);

        // 2) Resize (temp)
        $versions = punk_resize_versions($uploaded, $sizes, [
            'dest_dir' => $opts['dest_dir'] ?? sys_get_temp_dir(),
            'disk'     => $opts['disk']     ?? null,
        ]);

        // 3) Save finale
        $saved = punk_save_all($versions, [
            'dest_base' => $opts['dest_base'] ?? (__DIR__.'/img'),
            'overwrite' => $opts['overwrite'] ?? false,
            'disk'      => $opts['disk']      ?? null,
        ]);

        // 4) Normalizza per vista
        $out = [];
        foreach ($saved as $set) {
            $name = basename($set['source'] ?? '');
            $card = ['ok'=>true, 'name'=>$name, 'versions'=>[]];
            foreach ($sizes as $lbl => $_) {
                $final = $set['versions'][$lbl]['final'] ?? ($set['versions'][$lbl]['path'] ?? null);
                if ($final && is_file($final)) {
                    $card['versions'][$lbl] = ['final' => $final];
                } else {
                    $card['versions'][$lbl] = ['final'=>null];
                    $card['ok'] = false;
                }
            }
            $out[] = $card;
        }
        return $out;
    }
}

