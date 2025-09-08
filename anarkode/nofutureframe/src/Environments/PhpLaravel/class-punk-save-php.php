<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-save-php.php
| FROM → anarkode/nofutureframe/src/Environments/PhpLaravel
| TO   → Used wherever PUNK_ENV === 'php' (pure PHP environment)
| DESCRIPTION:
| EN: Pure-PHP "Save" implementation. Persists a *local temp file* to a final
|     filesystem path. Supports overwrite policy, MIME detection (finfo), size
|     calculation, and temp cleanup. Does not generate a public URL.
|
| IT: Implementazione "Save" in puro PHP. Salva un *file temporaneo locale*
|     in un percorso finale del filesystem. Supporta policy di overwrite,
|     rilevazione MIME (finfo), calcolo dimensione e cleanup del temp.
|     Non genera alcuna URL pubblica.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_SaveInterface;
use Exception;

class PUNK_SavePhp implements PUNK_SaveInterface
{
    /**
     * EN: Persist a local temp file to a final filesystem path.
     * IT: Salva un file temporaneo locale in un percorso finale del filesystem.
     */
    public function punk_save_from_temp(string $temp_path, string $dest, array $options = [])
    {
        // ---------------------------
        // EN: 1) Validate input
        // IT: 1) Valida input
        // ---------------------------
        if (!is_file($temp_path) || !is_readable($temp_path)) {
            return false;
        }

        // Allow relative $dest by resolving against CWD; prefer absolute.
        $dest = $this->normalize_dest_path($dest);
        if ($dest === null) {
            return false;
        }

        $overwrite   = isset($options['overwrite'])   ? (bool)$options['overwrite']   : false;
        $delete_temp = array_key_exists('delete_temp', $options) ? (bool)$options['delete_temp'] : true;
        $forced_mime = isset($options['mime']) ? (string)$options['mime'] : null;

        // ---------------------------
        // EN: 2) Ensure destination dir exists
        // IT: 2) Garantisci esistenza cartella di destinazione
        // ---------------------------
        $dest_dir = \dirname($dest);
        if (!$this->ensure_dir($dest_dir)) {
            return false;
        }

        // ---------------------------
        // EN: 3) Overwrite policy
        // IT: 3) Gestione overwrite
        // ---------------------------
        $overwritten = false;
        if (file_exists($dest)) {
            if (!$overwrite) {
                // No overwrite allowed → bail out
                return false;
            }
            // Try to remove to avoid cross-FS rename surprises
            @unlink($dest);
            $overwritten = true;
        }

        // ---------------------------
        // EN: 4) Move/copy from temp to dest (prefer atomic rename)
        // IT: 4) Sposta/copia dal temp alla destinazione (preferisci rename atomico)
        // ---------------------------
        $moved = @rename($temp_path, $dest);
        if (!$moved) {
            // Cross-device? Try copy + unlink
            if (!@copy($temp_path, $dest)) {
                // As a last resort, write via streams
                if (!$this->copy_streaming($temp_path, $dest)) {
                    // Cleanup partials
                    @unlink($dest);
                    return false;
                }
            }
            // Only unlink temp if we copied it
            if ($delete_temp && is_file($temp_path)) {
                @unlink($temp_path);
            }
        } else {
            // rename already moved the file → temp no longer exists
            $delete_temp = false;
        }

        // ---------------------------
        // EN: 5) Build normalized response
        // IT: 5) Costruisci risposta normalizzata
        // ---------------------------
        if (!is_file($dest)) {
            return false;
        }

        $size = @filesize($dest);
        $mime = $forced_mime ?: $this->detect_mime($dest);

        return [
            'path'  => $dest,           // absolute path
            'size'  => is_int($size) ? $size : 0,
            'mime'  => $mime ?: 'application/octet-stream',
            'url'   => null,            // No public URL in pure PHP
            'extra' => [
                'overwritten' => $overwritten,
                'deleted_temp'=> (bool)$delete_temp,
                'env'         => 'php',
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // EN: Utilities
    // IT: Utilità
    // ---------------------------------------------------------------------

    /**
     * EN: Ensure directory exists (mkdir -p); try to reuse core helper if present.
     * IT: Garantisce l'esistenza della cartella (mkdir -p); prova a usare l'helper core se presente.
     */
    private function ensure_dir(string $dir): bool
    {
        // If core helper exists, use it for consistency
        if (class_exists('\\Punkode\\Anarkode\\NoFutureFrame\\Core\\PUNK_ResizeLogic')) {
            try {
                return (bool)\Punkode\Anarkode\NoFutureFrame\Core\PUNK_ResizeLogic::punk_ensure_dir($dir);
            } catch (Exception $e) {
                // fallback to local mkdir
            }
        }
        if (is_dir($dir)) return true;
        return @mkdir($dir, 0775, true);
    }

    /**
     * EN: Normalize destination path; absolute is preferred, but resolve relative against CWD.
     * IT: Normalizza il path di destinazione; preferibile assoluto, risolve relativi contro CWD.
     */
    private function normalize_dest_path(string $dest): ?string
    {
        $dest = trim($dest);
        if ($dest === '') return null;

        // Absolute on Unix or Windows
        $is_absolute = ($dest[0] === '/' || preg_match('#^[A-Za-z]:\\\\#', $dest) === 1);
        if ($is_absolute) {
            return $dest;
        }
        // Resolve against current working directory
        $cwd = getcwd() ?: '.';
        return rtrim($cwd, "/\\") . DIRECTORY_SEPARATOR . $dest;
    }

    /**
     * EN: MIME detection using finfo; fallbacks to null.
     * IT: Rilevazione MIME con finfo; fallback a null.
     */
    private function detect_mime(string $path): ?string
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }
        if (function_exists('finfo_open')) {
            $f = @finfo_open(FILEINFO_MIME_TYPE);
            if ($f) {
                $m = @finfo_file($f, $path);
                @finfo_close($f);
                if (is_string($m) && $m !== '') {
                    return $m;
                }
            }
        }
        return null;
    }

    /**
     * EN: Streaming copy fallback for cross-device moves.
     * IT: Copia via stream in fallback per move cross-device.
     */
    private function copy_streaming(string $src, string $dst): bool
    {
        $in  = @fopen($src, 'rb');
        if (!$in) return false;
        $out = @fopen($dst, 'wb');
        if (!$out) { @fclose($in); return false; }

        $ok = true;
        while (!feof($in)) {
            $buf = fread($in, 1024 * 1024); // 1MB
            if ($buf === false) { $ok = false; break; }
            if (fwrite($out, $buf) === false) { $ok = false; break; }
        }
        @fclose($in);
        @fclose($out);

        if (!$ok) {
            @unlink($dst);
        }
        return $ok;
    }
}
