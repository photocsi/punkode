<?php
/*
|------------------------------------------------------------------------------
| FILE: class-punk-save-php.php
| FROM → anarkode/nofutureframe/src/Environments/PhpLaravel
| TO   → Used wherever PUNK_ENV === 'php' (pure PHP environment)
| DESCRIPTION
| EN: Pure-PHP "Save" implementation. Persists a *local temp file* to a final
|     filesystem path. Creates destination directories, normalizes paths, prefers
|     atomic rename() and falls back to copy()+unlink (esp. across volumes on Win).
|     Honors overwrite policy, reports "skipped" instead of failing when
|     overwrite=false and destination exists. Detects MIME (finfo) for payload.
|
| IT: Implementazione "Save" in puro PHP. Salva un *file temporaneo locale* verso
|     un percorso finale del filesystem. Crea le cartelle di destinazione, normalizza
|     i path, preferisce rename() e ripiega su copy()+unlink (specie tra volumi su Win).
|     Rispetta la policy di overwrite e, quando overwrite=false e il file esiste,
|     risponde con "skipped" invece di fallire. Rileva il MIME (finfo) nel payload.
|------------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_Save;
use Exception;

class PUNK_SavePhp implements PUNK_Save
{
    /**
     * EN: Persist a local temp file to final destination.
     *     Returns a structured array on success (or skip), false on real error.
     * IT: Salva un file temporaneo locale nella destinazione finale.
     *     Ritorna un array strutturato su successo (o skip), false su errore reale.
     *
     * @param string $temp_path  EN: source temp path  | IT: percorso temp sorgente
     * @param string $dest       EN: destination path  | IT: percorso destinazione
     * @param array{
     *   overwrite?: bool,      // EN: allow overwrite; IT: consenti sovrascrittura
     *   delete_temp?: bool,    // EN: delete temp if copied; IT: elimina temp dopo copy
     *   mime?: ?string,        // EN: caller's known MIME; IT: MIME già noto dal chiamante
     *   force_copy?: bool      // EN: force copy instead of rename; IT: forza copy al posto di rename
     * } $options
     * @return array{
     *   path:string,
     *   size:int,
     *   mime:?string,
     *   url:null,
     *   extra:array{overwritten:bool,deleted_temp:bool,env:string,skipped?:bool}
     * }|false
     */
    public function punk_save_from_temp(string $temp_path, string $dest, array $options = [])
    {
        // ------------------------------------------------------------------
        // EN: 0) Defaults & helpers
        // IT: 0) Default & utility
        // ------------------------------------------------------------------
        $o = \array_replace([
            'overwrite'   => false,
            'delete_temp' => true,
            'mime'        => null,
            'force_copy'  => false,
        ], $options);

        $norm = static function (string $p): string {
            // EN: unify slashes and squeeze duplicates; IT: unifica slash e compatta doppi
            $p = \str_replace(['/', '\\'], \DIRECTORY_SEPARATOR, $p);
            return (string)\preg_replace('#' . \preg_quote(\DIRECTORY_SEPARATOR, '#') . '+#', \DIRECTORY_SEPARATOR, $p);
        };

        $isDifferentVolume = static function (string $a, string $b): bool {
            // EN: Only meaningful on Windows; IT: Ha senso solo su Windows
            if (\DIRECTORY_SEPARATOR !== '\\') return false;
            // EN: Compare drive letters (C:\ vs D:\); IT: Confronta le lettere di unità
            return \strlen($a) > 1 && \strlen($b) > 1
                && \ctype_alpha($a[0]) && \ctype_alpha($b[0])
                && \strtoupper($a[0]) !== \strtoupper($b[0]);
        };

        // ------------------------------------------------------------------
        // EN: 1) Validate temp and normalize paths
        // IT: 1) Valida il temp e normalizza i path
        // ------------------------------------------------------------------
        if (!\is_file($temp_path) || !\is_readable($temp_path)) {
            return false; // EN: real error; IT: errore reale
        }

        $temp_path = $norm($temp_path);

        $dest = $this->normalize_dest_path($norm($dest)); // allow relative, prefer absolute
        if ($dest === null) {
            return false;
        }
        $dest = $norm($dest);
        $dest_dir = \dirname($dest);

        // ------------------------------------------------------------------
        // EN: 2) Ensure destination directory exists and is writable
        // IT: 2) Garantisci esistenza cartella di destinazione e scrivibilità
        // ------------------------------------------------------------------
        if (!$this->ensure_dir($dest_dir)) {
            return false;
        }
       

        // ------------------------------------------------------------------
        // EN: 3) Overwrite policy
        // IT: 3) Policy di sovrascrittura
        // ------------------------------------------------------------------
        $overwritten = false;
        if (\file_exists($dest)) {
            if (!$o['overwrite']) {
                // EN: Do NOT treat as error — return “skipped”.
                // IT: NON considerare errore — ritorna “skipped”.
                return [
                    'path'  => $dest,
                    'size'  => (int)(\is_file($dest) ? \filesize($dest) : 0),
                    'mime'  => $o['mime'] ?? $this->detect_mime($dest),
                    'url'   => null,
                    'extra' => [
                        'overwritten'  => false,
                        'deleted_temp' => false, // temp not touched by us
                        'env'          => 'php',
                        'skipped'      => true,
                    ],
                ];
            }
            // EN: Remove existing to avoid rename edge-cases.
            // IT: Rimuovi l’esistente per evitare edge-case di rename.
            @\unlink($dest);
            $overwritten = true;
        }

        // ------------------------------------------------------------------
        // EN: 4) Move/copy from temp to dest
        // IT: 4) Sposta/copia dal temp alla destinazione
        // ------------------------------------------------------------------
        $temp_removed = false;

        $mustCopy = (bool)$o['force_copy'] || $isDifferentVolume($temp_path, $dest);

        $moved = false;
        if (!$mustCopy) {
            // EN: Try fast atomic move; IT: Prova lo spostamento atomico
            $moved = @\rename($temp_path, $dest);
            if ($moved) {
                $temp_removed = true; // rename removes the source
            }
        }

        if (!$moved) {
            // EN: Fallback to copy + optional unlink(temp); IT: Ripiega su copy + unlink(temp)
            if (!@\copy($temp_path, $dest)) {
                // EN: Last resort: streaming copy; IT: Ultima spiaggia: copia via stream
                if (!$this->copy_streaming($temp_path, $dest)) {
                    @\unlink($dest); // cleanup partial
                    return false;
                }
            }
            if (!empty($o['delete_temp']) && \is_file($temp_path)) {
                @\unlink($temp_path);
                $temp_removed = true;
            }
        }

        // ------------------------------------------------------------------
        // EN: 5) Build response
        // IT: 5) Costruisci la risposta
        // ------------------------------------------------------------------
        if (!\is_file($dest)) {
            return false;
        }

        $size = (int)(\filesize($dest) ?: 0);
        $mime = $o['mime'] ?? $this->detect_mime($dest);

        return [
            'path'  => $dest,           // EN: absolute path | IT: path assoluto
            'size'  => $size,           // EN: bytes         | IT: byte
            'mime'  => $mime ?: 'application/octet-stream',
            'url'   => null,            // EN: no public URL in pure PHP | IT: nessuna URL pubblica
            'extra' => [
                'overwritten'  => $overwritten,
                'deleted_temp' => $temp_removed,
                'env'          => 'php',
            ],
        ];
    }

    // =========================================================================
    // EN: Utilities
    // IT: Utilità
    // =========================================================================

    /**
     * EN: Ensure directory exists (mkdir -p); try core helper if available.
     * IT: Garantisce l'esistenza della cartella (mkdir -p); usa l'helper core se c'è.
     */
    private function ensure_dir(string $dir): bool
    {
        if (\is_dir($dir)) return true;

        // Prefer shared helper if present (keeps behavior consistent across envs)
        if (\class_exists('\\Punkode\\Anarkode\\NoFutureFrame\\Core\\PUNK_ResizeLogic')) {
            try {
                return (bool)\Punkode\Anarkode\NoFutureFrame\Core\PUNK_ResizeLogic::punk_ensure_dir($dir);
            } catch (Exception $e) {
                // fallback to local mkdir
            }
        }
        return @\mkdir($dir, 0777, true);
    }

    /**
     * EN: Normalize destination path; absolute is preferred, but resolve relative against CWD.
     * IT: Normalizza il path di destinazione; preferibile assoluto, risolve relativi sul CWD.
     */
    private function normalize_dest_path(string $dest): ?string
    {
        $dest = \trim($dest);
        if ($dest === '') return null;

        // EN: Absolute on Unix (/path) or Windows (C:\path)
        // IT: Assoluto su Unix (/path) o Windows (C:\path)
        $is_absolute = ($dest[0] === '/' || \preg_match('#^[A-Za-z]:\\\\#', $dest) === 1);
        if ($is_absolute) {
            return $dest;
        }

        // EN: Resolve relative against current working directory.
        // IT: Risolvi i relativi rispetto alla working directory corrente.
        $cwd = \getcwd() ?: '.';
        return \rtrim($cwd, "/\\") . \DIRECTORY_SEPARATOR . $dest;
    }

    /**
     * EN: MIME detection using finfo; returns null on failure.
     * IT: Rileva il MIME via finfo; ritorna null in caso di fallimento.
     */
    private function detect_mime(string $path): ?string
    {
        if (!\is_file($path) || !\is_readable($path)) {
            return null;
        }
        if (\function_exists('finfo_open')) {
            $f = @\finfo_open(\FILEINFO_MIME_TYPE);
            if ($f) {
                $m = @\finfo_file($f, $path);
                @\finfo_close($f);
                if (\is_string($m) && $m !== '') {
                    return $m;
                }
            }
        }
        return null;
    }

    /**
     * EN: Streaming copy fallback (for tricky environments or huge files).
     * IT: Fallback di copia via stream (per ambienti ostici o file grandi).
     */
    private function copy_streaming(string $src, string $dst): bool
    {
        $in  = @\fopen($src, 'rb');
        if (!$in) return false;
        $out = @\fopen($dst, 'wb');
        if (!$out) { @\fclose($in); return false; }

        $ok = true;
        while (!\feof($in)) {
            $buf = \fread($in, 1024 * 1024); // 1 MB chunks
            if ($buf === false) { $ok = false; break; }
            if (\fwrite($out, $buf) === false) { $ok = false; break; }
        }
        @\fclose($in);
        @\fclose($out);

        if (!$ok) {
            @\unlink($dst);
        }
        return $ok;
    }
}
