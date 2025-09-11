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
        // EN: Create the core upload engine (low-level functions: move, validate, inspect).
        // IT: Crea il motore di upload di base (funzioni a basso livello: spostare, validare, ispezionare).
        $core = new PUNK_UploadCore();

        // EN: Wrap the core in a higher-level utility that:
        //     - merges defaults (e.g. allowed mimes, max size),
        //     - exposes consistent methods like punk_upload_files(),
        //     - can be extended for WordPress, Laravel, or pure PHP.
        // IT: Incapsula il core in una utility di livello superiore che:
        //     - applica i defaults (es. mime consentiti, dimensione massima),
        //     - espone metodi coerenti come punk_upload_files(),
        //     - può essere estesa per WordPress, Laravel o PHP puro.
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
        // EN: Normalize $_FILES into a consistent flat array of items.
        //     $_FILES può essere annidato (per input multiple) → semplifica.
        // IT: Normalizza $_FILES in un array piatto e coerente di elementi.
        //     $_FILES può essere annidato (input multipli) → qui semplifichi.
        $flat = punk_normalize_files($files);

        // EN: Build the upload utility for the current environment (PHP, WP, Laravel…).
        //     This function returns an adapter object exposing methods like punk_upload_files().
        // IT: Costruisci l’utility di upload per l’ambiente corrente (PHP, WP, Laravel…).
        //     Questa funzione restituisce un adapter con metodi come punk_upload_files().
        $upload = punk_env_upload_utils([
            // EN: You can put default settings here (e.g., allowed mime, max size).
            // IT: Puoi mettere qui impostazioni di default (es. mime consentiti, max size).
        ]);

        // EN: Delegate the actual saving to the adapter.
        //     It will:
        //       - move the temp files to safe tmp_path,
        //       - collect metadata (mime, size, safe_name…),
        //       - return an array of info for each file.
        // IT: Delega il salvataggio vero e proprio all’adapter.
        //     L’adapter:
        //       - sposta i file temporanei in un tmp_path sicuro,
        //       - raccoglie metadati (mime, size, safe_name…),
        //       - restituisce un array di info per ogni file.
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
        // EN: Just a thin wrapper — currently it delegates to punk_upload_only().
        // IT: Solo un involucro sottile — al momento delega tutto a punk_upload_only().
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
                    if ($w <= 0 && $h > 0) {
                        $w = $h;
                    }
                    if ($h <= 0 && $w > 0) {
                        $h = $w;
                    }
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

        $ensureDir = function (string $path): bool {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                return @mkdir($dir, 0777, true);
            }
            return true;
        };

        $robustMove = function (string $tmp, string $dest) use ($overwrite) {
            if (!$overwrite && is_file($dest)) return ['error' => 'exists', 'final' => null];

            if (!$ensure = (is_dir(dirname($dest)) || @mkdir(dirname($dest), 0777, true))) {
                return ['error' => 'mkdir failed: ' . dirname($dest), 'final' => null];
            }

            // Prova rename, poi copy+unlink
            if (@rename($tmp, $dest)) {
                return ['final' => $dest];
            }
            if (@copy($tmp, $dest)) {
                @unlink($tmp);
                return ['final' => $dest];
            }
            $e = error_get_last();
            return ['error' => ($e['message'] ?? 'move failed'), 'final' => null];
        };

        $serviceMove = function (string $tmp, string $relOrAbs) use ($disk, $overwrite, $opts) {
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
                    // -----------------------------------------------------------
                    // EN: Per-label destination (dir + optional suffix) + filename
                    // IT: Destinazione per label (dir + suffisso opzionale) + nome file
                    // -----------------------------------------------------------

                    // 0) Opzioni provenienti dal chiamante
                    $perLabel = $opts['sizes']        ?? [];  // EN: mirror of $sizes from caller | IT: copia del $sizes del chiamante
                    $mapDir   = $opts['dir_by_label'] ?? [];  // EN: optional override          | IT: override opzionale per dir

                    // 1) Cartella specifica per etichetta (prima da $sizes[label]['dir'], poi da dir_by_label)
                    $labelDir = null;
                    if (!empty($perLabel[$label]['dir'])) {
                        $labelDir = trim((string)$perLabel[$label]['dir'], "/\\");
                    } elseif (!empty($mapDir[$label])) {
                        $labelDir = trim((string)$mapDir[$label], "/\\");
                    }

                    // 2) Suffisso nel nome? (default: true). Se false → niente "-{label}".
                    //    ATTENZIONE: senza suffisso rischi sovrascrivere, a meno che le dir siano diverse.
                    $useSuffix = true;
                    if (array_key_exists('suffix', $perLabel[$label] ?? [])) {
                        $useSuffix = (bool)$perLabel[$label]['suffix'];
                    }

                    // 3) Orig base già iniettato a monte (es. "DSC_1234"); fallback "image"
                    $origBase = !empty($set['orig_base']) ? (string)$set['orig_base'] : 'image';

                    // 4) Estensione finale (preferisci v['ext']/['format']; fallback dalla path o 'jpg')
                    $tmpPath = $v['path'];
                    $ext = $v['ext'] ?? $v['format'] ?? strtolower(pathinfo($tmpPath, PATHINFO_EXTENSION) ?: 'jpg');
                    if ($ext === 'jpeg') $ext = 'jpg';
                    if (!in_array($ext, ['jpg', 'png', 'webp', 'gif', 'bmp', 'tiff', 'avif'], true)) {
                        $ext = 'jpg';
                    }

                    // 5) Filename finale: con o senza suffisso
                    // 5) Sanitize label → safe for filenames
                    $labelSafe = preg_replace('~[^A-Za-z0-9._-]+~', '-', (string)$label);
                    $labelSafe = trim($labelSafe, '-._');

                    // EN: Filename with or without suffix
                    // IT: Nome file con o senza suffisso
                    $finalName = $useSuffix
                        ? ($origBase . '-' . $labelSafe . '.' . $ext)
                        : ($origBase . '.' . $ext);


                    // 6) Costruisci la directory di destinazione e assicurati esista
                    $destDir = rtrim($destBase, "/\\");
                    if ($labelDir) {
                        $destDir .= DIRECTORY_SEPARATOR . $labelDir;
                    }
                    // 6) Costruisci la directory di destinazione e assicurati esista
                    $destDir = rtrim($destBase, "/\\");
                    if ($labelDir) {
                        $destDir .= DIRECTORY_SEPARATOR . $labelDir;
                    }

                    if (!is_dir($destDir)) {
                        if (!@mkdir($destDir, 0775, true) && !is_dir($destDir)) {
                            // EN: mkdir failed even after attempt → record error and skip this version
                            // IT: mkdir fallito anche dopo il tentativo → registra errore e salta questa versione
                            $entry['versions'][$label] = ['error' => 'mkdir failed: ' . $destDir];
                            continue; // salta questa versione
                        }
                    }


                    // 7) Path di destinazione completo
                    $dest = $destDir . DIRECTORY_SEPARATOR . $finalName;

                    // 8) Evita il caso rarissimo tmp==dest (se il tmp è già lì con stesso nome)
                    $rpT = realpath($tmpPath);
                    $rpD = realpath(dirname($dest));
                    if ($rpT && $rpD && (dirname($rpT) === $rpD) && (basename($rpT) === basename($dest))) {
                        $pi   = pathinfo($dest);
                        $dest = $pi['dirname'] . DIRECTORY_SEPARATOR . $pi['filename'] . '-final.' . ($pi['extension'] ?? 'jpg');
                    }



                    // 1° tentativo: adapter ufficiale
                    $res = $serviceMove($tmpPath, $dest);

                    // 2° tentativo: fallback robusto se l’adapter fallisce (solo per PHP puro/paths)
                    if (!$res || empty($res['final']) || !is_file($res['final'])) {
                        $res2 = $robustMove($tmpPath, $dest);
                        // arricchisci per debug
                        $res = [
                            'final' => $res2['final'] ?? null,
                            'error' => $res2['error'] ?? ($res['error'] ?? 'save failed'),
                            'debug_tmp'  => $tmpPath,
                            'debug_dest' => $dest,
                            'fallback'   => true,
                        ];
                    } else {
                        $res['debug_tmp']  = $tmpPath;
                        $res['debug_dest'] = $dest;
                        $res['fallback']   = false;
                    }

                    $entry['versions'][$label] = array_merge($res, [
                        'label'    => (string)$label,
                        'filename' => $finalName,
                        'ext'      => $ext,
                        // niente rel_path, niente url
                    ]);



                    $entry['versions'][$label] = $res;
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
// Sostituisci la tua punk_process_upload con questa:
if (!function_exists('punk_process_upload')) {
    function punk_process_upload(array $files, array $sizes, array $opts = []): array
    {
        // EN: If 'debug' is truthy in $opts, enable verbose traces.
        // IT: Se 'debug' è vero in $opts, abilita le tracce dettagliate.
        $debug = !empty($opts['debug']);

        // EN: Global trace array to collect step-by-step info (filled only if debug).
        // IT: Array di traccia globale per accumulare info passo-passo (usato se debug).
        $traceGlobal = [];

        // 🔹 NEW: dest_base chosen by the user (mandatory)
        // EN: Require a final destination folder from the user. If missing or not a string, abort early.
        // IT: Richiedi una cartella di destinazione finale dall’utente. Se manca o non è stringa, interrompi subito.
        if (empty($opts['dest_base']) || !is_string($opts['dest_base'])) {
            // EN: Human-readable error message for logs and UI.
            // IT: Messaggio di errore leggibile per log e interfaccia.
            $msg = '[init] Missing or invalid dest_base (user destination is required).';

            // EN: Write the error to your logging system (file, WP, Laravel… depending on your env).
            // IT: Scrivi l’errore nel sistema di logging (file, WP, Laravel… a seconda dell’ambiente).
            punk_log($msg, 'error');

            // EN: Return a standardized error “card” so the caller can show it.
            // IT: Restituisci una “scheda” di errore standardizzata così il chiamante può mostrarla.
            return [[
                'ok'    => false,                 // EN: The step failed | IT: Lo step è fallito
                'name'  => 'init',                // EN: Which phase | IT: Quale fase
                'error' => $msg,                  // EN: Error text | IT: Testo dell’errore
                'trace' => [[                     // EN: Mini trace for this failure | IT: Mini traccia per il fallimento
                    'step'  => 'init',
                    'ok'    => false,
                    'error' => $msg
                ]]
            ]];
        }

        // EN: Normalize destination path by removing trailing slashes/backslashes.
        // IT: Normalizza il percorso di destinazione rimuovendo slash finali (sia / che \).
        $destBase = rtrim($opts['dest_base'], "/\\");

        // 🔹 NEW: create/validate destination folder
        // EN: Make sure the folder exists and is writable before doing any heavy work.
        // IT: Assicurati che la cartella esista e sia scrivibile prima di fare lavori pesanti.
        try {
            // EN: If the directory doesn't exist, try to create it (recursive).
            // IT: Se la cartella non esiste, prova a crearla (ricorsivo).
            if (!is_dir($destBase)) {
                // EN: @ suppresses PHP warnings; we handle errors manually after.
                // IT: @ sopprime i warning di PHP; gestiamo gli errori manualmente dopo.
                if (!@mkdir($destBase, 0775, true) && !is_dir($destBase)) {
                    // EN: If creation still failed, throw to jump into catch.
                    // IT: Se la creazione fallisce comunque, lancia un’eccezione per entrare nel catch.
                    throw new RuntimeException('Cannot create dest_base: ' . $destBase);
                }
            }

            // EN: Check that the directory is writable by PHP (file permissions/owner).
            // IT: Controlla che la cartella sia scrivibile da PHP (permessi/proprietario).
            if (!is_writable($destBase)) {
                // EN: If not writable, bail out with a clear message.
                // IT: Se non scrivibile, interrompi con messaggio chiaro.
                throw new RuntimeException('dest_base not writable: ' . $destBase);
            }
        } catch (\Throwable $e) {
            // EN: Build a readable error including the exception message.
            // IT: Costruisci un errore leggibile includendo il messaggio dell’eccezione.
            $msg = '[init] ' . $e->getMessage();

            // EN: Log the failure for diagnostics.
            // IT: Logga il problema per la diagnostica.
            punk_log($msg, 'error');

            // EN: Return a standardized error “card” so the UI can show what went wrong.
            // IT: Restituisci una “scheda” di errore standard per mostrare cosa non ha funzionato.
            return [[
                'ok'    => false,
                'name'  => 'init',
                'error' => $msg,
                'trace' => [['step' => 'init', 'ok' => false, 'error' => $msg]]
            ]];
        }

        // ⬇️ ... (gli step successivi: upload → resize → save)


        // 1) Upload (com’era)
        try {
            // EN: Invoke the upload adapter with the raw $files input (usually from $_FILES).
            //     It should normalize single/multiple files, validate them, and move them
            //     to a safe temporary location. Returns an array of uploaded items.
            // IT: Chiama l’adattatore di upload con $files (di solito da $_FILES).
            //     Dovrebbe normalizzare singolo/multiplo, validarli e spostarli in una
            //     posizione temporanea sicura. Ritorna un array di elementi caricati.
            $files_organizzati_temp = punk_upload($files);

            // EN: If debug mode is on, append a trace entry saying upload succeeded,
            //     and how many files were processed.
            // IT: Se il debug è attivo, aggiungi una voce di traccia che indica
            //     il successo dell’upload e quanti file sono stati processati.
            if ($debug) {
                $traceGlobal[] = ['step' => 'upload', 'ok' => true, 'count' => count($files_organizzati_temp)];
            }
        } catch (\Throwable $e) {
            // EN: Build a readable error message including the exception text.
            // IT: Costruisci un messaggio di errore leggibile con il testo dell’eccezione.
            $msg = '[upload] ' . $e->getMessage();

            // EN: Log the error via your logging system (file/WP/Laravel) for diagnostics.
            // IT: Logga l’errore col tuo sistema di logging (file/WP/Laravel) per diagnostica.
            punk_log($msg, 'error');

            // EN: Return a standardized "error card" so the caller/UI can show a clear status.
            // IT: Ritorna una “scheda d’errore” standard così il chiamante/UI può mostrare lo stato chiaramente.
            return [[
                'ok'    => false,                                      // EN: step failed | IT: step fallito
                'name'  => 'upload',                                   // EN: which step  | IT: quale step
                'error' => $msg,                                       // EN: error text  | IT: testo errore
                'trace' => [['step' => 'upload', 'ok' => false, 'error' => $msg]] // EN/IT: mini-traccia
            ]];
        }

        /**************************************************
         * **************RESIZE*****************************
         *************************************************/
        // 2) Resize (tmp) — lasciamo invariato: l’utente sceglie solo la finale
        // EN: Step 2) Create resized versions into a TEMP folder.
        // IT: Step 2) Crea le versioni ridotte in una cartella TEMP.
        $versions = []; // EN: holder for resize output | IT: conterrà l’output del resize

        try {
            // EN: Call the resize engine with:
            //     - the uploaded items ($uploaded),
            //     - the requested size labels ($sizes),
            //     - options: where to put temp files and (optionally) storage disk.
            // IT: Chiama il motore di resize con:
            //     - gli item caricati ($uploaded),
            //     - le etichette/taglie richieste ($sizes),
            //     - opzioni: dove mettere i file temporanei e (opzionale) il “disk”.
            $versions = punk_resize_versions($files_organizzati_temp, $sizes, [
                'dest_dir' => $opts['dest_dir'] ?? sys_get_temp_dir(), // EN: temp dir fallback | IT: fallback cartella tmp
                'disk'     => $opts['disk']     ?? null,               // EN: fs adapter hint  | IT: hint per adapter fs
            ]);

            // EN: If debug is on, record that resize succeeded and how many sets returned.
            // IT: Se debug attivo, registra il successo e quanti set sono tornati.
            if ($debug) {
                $traceGlobal[] = ['step' => 'resize', 'ok' => true, 'items' => count($versions)];
            }
        } catch (\Throwable $e) {
            // EN: Build and log a readable error from any exception thrown during resize.
            // IT: Costruisci e logga un errore leggibile da qualunque eccezione nel resize.
            $msg = '[resize] ' . $e->getMessage();
            punk_log($msg, 'error');

            // EN: Stop the pipeline and return a standardized error card that includes prior traces.
            // IT: Ferma la pipeline e ritorna una scheda d’errore standard con le trace precedenti.
            return [[
                'ok'    => false,
                'name'  => 'resize',
                'error' => $msg,
                'trace' => array_merge($traceGlobal, [['step' => 'resize', 'ok' => false, 'error' => $msg]])
            ]];
        }

        // 3) Save — 🔹 PASSIAMO la dest_base dell’utente
        // --- Mappa tmp_path -> nome originale (senza estensione)
        // EN: Build a map from each source tmp file to its original base filename (no extension).
        // IT: Crea una mappa dal file tmp sorgente al nome originale base (senza estensione).
        $origMap = [];
        foreach ($files_organizzati_temp as $row) {
            $srcTmp = $row['meta']['tmp_path'] ?? null; // EN: our managed tmp path | IT: tmp gestito
            $nm     = $row['name'] ?? ($row['meta']['safe_name'] ?? ($srcTmp ? basename($srcTmp) : ''));
            if ($srcTmp && $nm) {
                // EN: Store "DSC_1234" for later naming ("DSC_1234-sm.jpg")
                // IT: Salva "DSC_1234" per il nome finale ("DSC_1234-sm.jpg")
                $origMap[$srcTmp] = pathinfo($nm, PATHINFO_FILENAME);
            }
        }

        // --- Inietta l'orig_base dentro ogni set del resize
        // EN: For each resized set, attach 'orig_base' (derived above) to use in the save step.
        // IT: Per ogni set del resize, aggiungi 'orig_base' (derivato sopra) da usare nel salvataggio.
        foreach ($versions as &$set) {
            $src = $set['source'] ?? null; // EN: should be the same tmp used as input to resize
            // IT: dovrebbe essere il tmp usato come input al resize
            if ($src && isset($origMap[$src])) {
                $set['orig_base'] = $origMap[$src]; // es. "DSC_1234"
            }
        }
        unset($set); // EN: break reference from foreach-by-ref | IT: chiudi il riferimento del foreach


        /**************************************************
         * **************RESIZE*****************************
         *************************************************/

        $saved = [];
        try {
            // EN: Save step — pass through all the knobs the saver may need
            // IT: Step di salvataggio — passa tutte le opzioni utili al saver
            $saved = punk_save_all($versions, [
                'dest_base' => $destBase,
                'overwrite' => $opts['overwrite'] ?? false,
                'disk'      => $opts['disk'] ?? null,
                'sizes'     => $sizes,
            ]);



            if ($debug) {
                $traceGlobal[] = [
                    'step'      => 'save',
                    'ok'        => true,
                    'items'     => count($saved),
                    'dest_base' => $destBase,
                    'disk'      => $opts['disk'] ?? null, // utile in trace
                    // 'base_url'  => $opts['base_url'] ?? null,
                ];
            }
        } catch (\Throwable $e) {
            $msg = '[save] ' . $e->getMessage();
            punk_log($msg, 'error');
            return [[
                'ok' => false,
                'name' => 'save',
                'error' => $msg,
                'trace' => array_merge($traceGlobal, [['step' => 'save', 'ok' => false, 'error' => $msg]])
            ]];
        }

        // 4) Normalizzazione (com’era)...
        $out = [];
        $nameMap = [];
        foreach ($files_organizzati_temp as $row) {
            $src = $row['meta']['tmp_path'] ?? null;
            $nm  = $row['name'] ?? ($row['meta']['safe_name'] ?? ($src ? basename($src) : ''));
            if ($src) $nameMap[$src] = $nm;
        }

        foreach ($saved as $idx => $set) {
            $source = $set['source'] ?? null;
            $name   = $nameMap[$source] ?? ($source ? basename($source) : '');

            $card = ['ok' => true, 'name' => $name, 'versions' => [], 'trace' => $traceGlobal];

            foreach ($sizes as $lbl => $_) {
                $info  = $set['versions'][$lbl] ?? null;
                $final = is_array($info) ? ($info['final'] ?? ($info['path'] ?? ($info['abs_path'] ?? null))) : null;

                if ($final && is_string($final) && is_file($final)) {
                    $card['versions'][$lbl] = ['final' => $final];
                    if ($debug) $card['trace'][] = ['step' => "save:$lbl", 'ok' => true, 'final' => $final];
                } else {
                    $card['ok'] = false;
                    $err = is_array($info) ? ($info['error'] ?? 'unknown') : 'missing';
                    $card['versions'][$lbl] = ['final' => null, 'error' => $err];
                    if ($debug) $card['trace'][] = ['step' => "save:$lbl", 'ok' => false, 'error' => $err];
                }
            }
            $out[] = $card;
        }

        return $out;
    }
}
