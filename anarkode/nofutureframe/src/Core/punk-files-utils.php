<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Core/punk-files-utils.php
| DESCRIPTION:
| EN: Core utilities for file handling in NoFutureFrame.
|     Pure functions usable across PHP, WordPress and Laravel environments.
| IT: Utility core per la gestione dei file in NoFutureFrame.
|     Funzioni pure riusabili in PHP, WordPress e Laravel.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Core;

/**
 * FUNCTION: punk_normalize_files()
 * EN: Normalize a $_FILES-like array (single or multiple) into a flat list.
 * IT: Normalizza un array stile $_FILES (singolo o multiplo) in una lista piatta.
 *
 * @param array $files
 * @return array<int,array{name:string,type:string,tmp_name:string,error:int,size:int}>
 */
function punk_normalize_files(array $files): array
{
    if (isset($files['tmp_name'])) {
        if (is_array($files['tmp_name'])) {
            $out = [];
            foreach ($files['tmp_name'] as $i => $tmp) {
                $out[] = [
                    'name'     => $files['name'][$i] ?? '',
                    'type'     => $files['type'][$i] ?? '',
                    'tmp_name' => $tmp,
                    'error'    => (int)($files['error'][$i] ?? \UPLOAD_ERR_NO_FILE),
                    'size'     => (int)($files['size'][$i] ?? 0),
                ];
            }
            return $out;
        }
        return [[
            'name'     => $files['name']     ?? '',
            'type'     => $files['type']     ?? '',
            'tmp_name' => $files['tmp_name'] ?? '',
            'error'    => (int)($files['error']    ?? \UPLOAD_ERR_NO_FILE),
            'size'     => (int)($files['size']     ?? 0),
        ]];
    }

    $out = [];
    foreach ($files as $f) {
        if (isset($f['tmp_name'])) {
            $out[] = [
                'name'     => $f['name']     ?? '',
                'type'     => $f['type']     ?? '',
                'tmp_name' => $f['tmp_name'] ?? '',
                'error'    => (int)($f['error']    ?? \UPLOAD_ERR_NO_FILE),
                'size'     => (int)($f['size']     ?? 0),
            ];
        }
    }
    return $out;
}

/**
 * FUNCTION: punk_real_mime()
 * EN: Get authoritative MIME type using ext-fileinfo (finfo).
 * IT: Ottiene il MIME reale usando ext-fileinfo (finfo).
 */
function punk_real_mime(string $path): ?string
{
    $fi = \finfo_open(\FILEINFO_MIME_TYPE);
    if (!$fi) return null;
    $m = \finfo_file($fi, $path) ?: null;
    \finfo_close($fi);
    return $m;
}

/**
 * FUNCTION: punk_safe_filename()
 * EN: Sanitize filename: ASCII + [A-Za-z0-9_.-], remove leading dots,
 *     collapse multiple dots, keep only last extension.
 * IT: Sanifica il nome file: ASCII + [A-Za-z0-9_.-], rimuove punti iniziali,
 *     comprime punti multipli, mantiene solo l’ultima estensione.
 */
function punk_safe_filename(string $name): string
{
    $name = \preg_replace('/[^\w\.\-]+/u', '_', $name);
    $name = \ltrim($name, '.');
    $name = \preg_replace('/\.+/', '.', $name);
    return $name ?: 'file';
}

/**
 * FUNCTION: punk_build_rel_dir()
 * EN: Build a relative folder path.
 *     - 'date'   → 'Y/m/d'
 *     - 'flat'   → ''
 *     - callable → custom string
 * IT: Costruisce un percorso relativo di cartelle.
 *     - 'date'   → 'Y/m/d'
 *     - 'flat'   → ''
 *     - callable → stringa personalizzata
 */
function punk_build_rel_dir(string|\Closure $scheme = 'date'): string
{
    if (\is_callable($scheme)) {
        $rel = (string)\call_user_func($scheme);
        return \trim(\str_replace('\\', '/', $rel), '/');
    }
    if ($scheme === 'flat') return '';
    return \date('Y/m/d');
}

/**
 * FUNCTION: punk_unique_name()
 * EN: Ensure a unique filename using a custom existence checker.
 * IT: Garantisce un nome file univoco usando un checker personalizzato.
 *
 * @param \Closure(string):bool $exists Closure that returns true if filename exists.
 * @param string $filename
 * @return string Unique filename
 */
function punk_unique_name(\Closure $exists, string $filename): string
{
    $ext  = \strtolower(\pathinfo($filename, \PATHINFO_EXTENSION));
    $base = $ext ? \substr($filename, 0, - (strlen($ext) + 1)) : $filename;

    $i = 0;
    $candidate = $filename;
    while ($exists($candidate)) {
        $i++;
        $candidate = $base . '-' . $i . ($ext ? '.' . $ext : '');
    }
    return $candidate;
}
