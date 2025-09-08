<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-save-laravel.php
| FROM → anarkode/nofutureframe/src/Environments/PhpLaravel
| TO   → Used whenever PUNK_ENV === 'laravel' (Laravel environment)
| DESCRIPTION:
| EN: Laravel "Save" implementation. Persists a *local temp file* to a
|     configured Storage disk (local/public/S3/...).
|     - Honors overwrite policy (or generates unique filename).
|     - Sets visibility and mimetype when provided.
|     - Cleans up temp (by default).
|     - Returns storage key, size, mime, and public URL (when available).
|
| IT: Implementazione "Save" per Laravel. Salva un *file temporaneo locale*
|     su un disk di Storage (local/public/S3/...).
|     - Gestisce overwrite (oppure genera un filename univoco).
|     - Imposta visibility e mimetype se forniti.
|     - Pulisce il temp (default).
|     - Ritorna storage key, size, mime e URL pubblica (se disponibile).
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_SaveInterface;
use Illuminate\Support\Facades\Storage;
use Exception;

class PUNK_SaveLaravel implements PUNK_SaveInterface
{
    /** @var string */
    protected $disk;

    /**
     * EN: Pass the disk name (e.g. 'public', 's3', 'local').
     * IT: Passa il nome del disk (es. 'public', 's3', 'local').
     */
    public function __construct(string $disk = 'public')
    {
        $this->disk = $disk;
    }

    /**
     * EN: Persist local temp file to Laravel Storage disk.
     * IT: Salva un file temp locale su un disk di Laravel Storage.
     *
     * @param string $temp_path Absolute path to local temp file.
     * @param string $dest      Storage key relative to the disk root (e.g. 'images/out.jpg').
     *                          Absolute filesystem paths are NOT accepted here.
     * @param array  $options   [
     *   'overwrite'   => bool,                 // default false
     *   'visibility'  => 'public'|'private',   // optional
     *   'mime'        => 'image/jpeg',         // optional override
     *   'delete_temp' => bool,                 // default true
     * ]
     *
     * @return array|false
     */
    public function punk_save_from_temp(string $temp_path, string $dest, array $options = [])
    {
        // -------------------------------------------------
        // 1) Validate / normalize inputs
        // -------------------------------------------------
        if (!is_file($temp_path) || !is_readable($temp_path)) {
            return false;
        }
        $key = $this->normalize_key($dest);
        if ($key === null) {
            return false;
        }

        $overwrite   = isset($options['overwrite'])   ? (bool)$options['overwrite']   : false;
        $delete_temp = array_key_exists('delete_temp', $options) ? (bool)$options['delete_temp'] : true;
        $forced_mime = isset($options['mime'])        ? (string)$options['mime']      : null;
        $visibility  = isset($options['visibility'])  ? (string)$options['visibility'] : null;

        $disk = Storage::disk($this->disk);

        // -------------------------------------------------
        // 2) Overwrite policy / unique filename
        // -------------------------------------------------
        if ($disk->exists($key)) {
            if ($overwrite) {
                try { $disk->delete($key); } catch (Exception $e) { /* ignore */ }
            } else {
                $key = $this->unique_key($disk, $key);
            }
        }

        // -------------------------------------------------
        // 3) Upload with streams (safe for large files)
        // -------------------------------------------------
        $stream = @fopen($temp_path, 'rb');
        if (!$stream) {
            return false;
        }

        $putOptions = [];
        if ($visibility === 'public' || $visibility === 'private') {
            $putOptions['visibility'] = $visibility;
        }
        if ($forced_mime) {
            // Some drivers (S3) respect 'ContentType' / 'mimetype'. Laravel normalizes to 'mimetype'.
            $putOptions['mimetype'] = $forced_mime;
        }

        $ok = $disk->put($key, $stream, $putOptions);
        @fclose($stream);

        if (!$ok) {
            return false;
        }

        // Temp cleanup
        if ($delete_temp && is_file($temp_path)) {
            @unlink($temp_path);
        }

        // -------------------------------------------------
        // 4) Build normalized response
        // -------------------------------------------------
        // Size: try disk->size(), fallback to local filesize (before deletion)
        $size = null;
        try {
            $size = $disk->size($key);
        } catch (Exception $e) {
            $size = null;
        }
        if (!is_int($size)) {
            $size = @filesize($temp_path) ?: 0; // if temp still exists or as last resort 0
        }

        // MIME: forced or detected from key (best-effort)
        $mime = $forced_mime ?: $this->guess_mime_from_extension($key);
        if (!$mime && method_exists($disk, 'mimeType')) {
            try { $mime = $disk->mimeType($key); } catch (Exception $e) { /* ignore */ }
        }
        if (!$mime) {
            $mime = 'application/octet-stream';
        }

        // URL (if available)
        $url = null;
        try {
            // Storage::url works for public disks (e.g., 'public', S3 with URL configured)
            $url = $disk->url($key);
        } catch (Exception $e) {
            $url = null;
        }

        return [
            'path'  => $key,                   // storage key on the disk
            'size'  => (int)$size,
            'mime'  => $mime,
            'url'   => $url,                   // may be null for private disks
            'extra' => [
                'disk'         => $this->disk,
                'visibility'   => $visibility,
                'env'          => 'laravel',
            ],
        ];
    }

    // ---------------------------------------------------------------------
    // Utilities
    // ---------------------------------------------------------------------

    /**
     * EN: Accept only storage keys (no absolute filesystem paths).
     * IT: Accetta solo storage key (niente path assoluti di filesystem).
     */
    private function normalize_key(string $dest): ?string
    {
        $dest = trim($dest);
        if ($dest === '') {
            return null;
        }
        // Reject absolute paths (Unix/Windows)
        $is_abs = ($dest[0] === '/' || preg_match('#^[A-Za-z]:\\\\#', $dest) === 1);
        if ($is_abs) {
            return null;
        }
        // Normalize separators to forward slashes for storage keys
        $dest = preg_replace('#[\\\\]+#', '/', $dest);
        // Remove redundant slashes
        $dest = preg_replace('#/+#', '/', $dest);
        // Trim leading slash
        $dest = ltrim($dest, '/');
        return $dest;
    }

    /**
     * EN: Produce a unique key if one exists already (similar to WP style).
     * IT: Genera una key univoca se esiste già (stile WP).
     */
    private function unique_key($disk, string $key): string
    {
        $dir  = '';
        $name = $key;

        // Split into directory and filename
        if (false !== ($pos = strrpos($key, '/'))) {
            $dir  = substr($key, 0, $pos);
            $name = substr($key, $pos + 1);
        }

        $filename = pathinfo($name, PATHINFO_FILENAME);
        $ext      = pathinfo($name, PATHINFO_EXTENSION);

        $i = 1;
        do {
            $candidate = ($dir ? $dir.'/' : '')
                       . $filename . '-' . $i
                       . ($ext !== '' ? '.'.$ext : '');
            $i++;
        } while ($disk->exists($candidate));

        return $candidate;
    }

    /**
     * EN: Quick MIME guess from extension.
     * IT: Stima rapida del MIME dall'estensione.
     */
    private function guess_mime_from_extension(string $key): ?string
    {
        $ext = strtolower(pathinfo($key, PATHINFO_EXTENSION));
        if ($ext === '') return null;
        // minimal map
        $map = [
            'jpg' => 'image/jpeg',
            'jpeg'=> 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp'=> 'image/webp',
            'svg' => 'image/svg+xml',
            'pdf' => 'application/pdf',
            'txt' => 'text/plain',
        ];
        return $map[$ext] ?? null;
    }
}
