<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-save-wp.php
| FROM → anarkode/nofutureframe/src/Environments/wp
| TO   → Used whenever PUNK_ENV === 'wp' (WordPress environment)
| DESCRIPTION:
| EN: WordPress "Save" implementation. Persists a *local temp file* into the
|     uploads directory, supports overwrite policy, filename sanitization,
|     attachment creation + metadata, and returns a public URL.
|
| IT: Implementazione "Save" per WordPress. Salva un *file temporaneo locale*
|     nella cartella uploads, gestisce overwrite, sanificazione del filename,
|     creazione attachment + metadata e restituisce una URL pubblica.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\Wp;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_Save;
use Exception;

if (!defined('ABSPATH')) {
    // EN: Avoid direct access outside WordPress
    // IT: Evita l'accesso diretto fuori da WordPress
    exit;
}

class PUNK_SaveWp implements PUNK_Save
{
    /**
     * EN: Persist a local temp file to WordPress uploads and optionally create an attachment.
     * IT: Salva un file temporaneo negli uploads di WordPress e opzionalmente crea un attachment.
     *
     * $dest accetta:
     *   - percorso relativo a uploads, es. 'my-folder/out.jpg' (preferito)
     *   - (tollerato) percorso assoluto *dentro* wp-content/uploads
     */
    public function punk_save_from_temp(string $temp_path, string $dest, array $options = [])
    {
        // --------------------------------
        // 1) Preconditions / WP functions
        // --------------------------------
        if (!is_file($temp_path) || !is_readable($temp_path)) {
            return false;
        }
        if (!function_exists('wp_upload_dir') || !function_exists('wp_mkdir_p')) {
            return false;
        }

        $uploads = wp_upload_dir();
        if (!empty($uploads['error'])) {
            return false;
        }

        $base_dir  = rtrim($uploads['basedir'], "/\\"); // filesystem dir
        $base_url  = rtrim($uploads['baseurl'], "/\\"); // public base url

        $overwrite         = isset($options['overwrite'])   ? (bool)$options['overwrite']   : false;
        $delete_temp       = array_key_exists('delete_temp', $options) ? (bool)$options['delete_temp'] : true;
        $forced_mime       = isset($options['mime'])        ? (string)$options['mime']      : null;
        $create_attachment = array_key_exists('create_attachment', $options) ? (bool)$options['create_attachment'] : true;

        // --------------------------------
        // 2) Normalize destination (relative to uploads)
        // --------------------------------
        $rel_path = $this->normalize_relative_path($dest, $base_dir);
        if ($rel_path === null) {
            return false;
        }

        // Sanitize filename and ensure subdirs exist
        $dest_dir_rel = ltrim(\dirname($rel_path), "/\\");
        $dest_dir_abs = $dest_dir_rel === '.' ? $base_dir : $base_dir . DIRECTORY_SEPARATOR . $dest_dir_rel;

        if (!$this->ensure_dir($dest_dir_abs)) {
            return false;
        }

        // Sanitize + handle overwrite policy using WP helpers
        $filename = $this->sanitize_filename(basename($rel_path));
        $dest_abs = $dest_dir_abs . DIRECTORY_SEPARATOR . $filename;

        if (file_exists($dest_abs)) {
            if (!$overwrite) {
                // No overwrite: pick a unique filename (like core media does)
                if (function_exists('wp_unique_filename')) {
                    $filename = wp_unique_filename($dest_dir_abs, $filename);
                    $dest_abs = $dest_dir_abs . DIRECTORY_SEPARATOR . $filename;
                } else {
                    // fallback simple suffix
                    $dest_abs = $this->unique_suffix($dest_dir_abs, $filename);
                    $filename = basename($dest_abs);
                }
            } else {
                @unlink($dest_abs);
            }
        }

        // --------------------------------
        // 3) Move/copy temp → uploads
        // --------------------------------
        $moved = @rename($temp_path, $dest_abs);
        if (!$moved) {
            // Cross-device: copy + unlink
            if (!@copy($temp_path, $dest_abs)) {
                // Stream fallback
                if (!$this->copy_streaming($temp_path, $dest_abs)) {
                    @unlink($dest_abs);
                    return false;
                }
            }
            if ($delete_temp && is_file($temp_path)) {
                @unlink($temp_path);
            }
        } else {
            // rename consumed temp
            $delete_temp = false;
        }

        if (!is_file($dest_abs)) {
            return false;
        }

        // --------------------------------
        // 4) MIME + size + URL
        // --------------------------------
        $size = @filesize($dest_abs);

        $mime = $forced_mime ?: $this->detect_mime_wp_first($dest_abs);
        if (!$mime) {
            $mime = 'application/octet-stream';
        }

        // Build public URL based on uploads baseurl and relative path
        $final_rel = ($dest_dir_rel === '.' ? '' : $dest_dir_rel . '/'). $filename;
        $final_url = $base_url . '/' . str_replace(DIRECTORY_SEPARATOR, '/', ltrim($final_rel, '/\\'));

        $result = [
            'path'  => $dest_abs,
            'size'  => is_int($size) ? $size : 0,
            'mime'  => $mime,
            'url'   => $final_url,
            'extra' => [
                'relative_path' => $final_rel,
                'env'           => 'wp',
            ],
        ];

        // --------------------------------
        // 5) Create attachment (optional)
        // --------------------------------
        if ($create_attachment && function_exists('wp_insert_attachment')) {
            $attach_id = $this->create_or_update_attachment($dest_abs, $final_rel, $mime, $options);
            if ($attach_id) {
                $result['extra']['attachment_id'] = $attach_id;
            }
        }

        return $result;
    }

    // ---------------------------------------------------------------------
    // Utilities
    // ---------------------------------------------------------------------

    /**
     * EN: Normalize $dest to a path relative to uploads; accept absolute paths
     *     only if they are inside uploads, otherwise return null.
     * IT: Normalizza $dest come path relativo alla cartella uploads; accetta
     *     assoluti solo se interni a uploads, altrimenti null.
     */
    private function normalize_relative_path(string $dest, string $uploads_basedir): ?string
    {
        $dest = trim($dest);
        if ($dest === '') return null;

        // Absolute?
        $is_abs = ($dest[0] === '/' || preg_match('#^[A-Za-z]:\\\\#', $dest) === 1);
        if ($is_abs) {
            // Must be inside uploads dir
            $uploads_basedir = rtrim($uploads_basedir, "/\\");
            $norm = $this->normalize_fs($dest);
            $base = $this->normalize_fs($uploads_basedir) . DIRECTORY_SEPARATOR;
            if (strpos($norm, $base) === 0) {
                return ltrim(substr($norm, strlen($base)), "/\\");
            }
            return null; // outside uploads → reject
        }

        // Relative: keep as-is (will create subdirs if needed)
        return ltrim($this->normalize_fs($dest), "/\\");
    }

    private function normalize_fs(string $path): string
    {
        return preg_replace('#[\\/]+#', DIRECTORY_SEPARATOR, $path);
    }

    private function sanitize_filename(string $filename): string
    {
        if (function_exists('sanitize_file_name')) {
            return sanitize_file_name($filename);
        }
        // Fallback minimal sanitization
        $filename = preg_replace('/[^\w\.\-]+/u', '_', $filename);
        return trim($filename, '._');
    }

    private function unique_suffix(string $dir, string $filename): string
    {
        $name = pathinfo($filename, PATHINFO_FILENAME);
        $ext  = pathinfo($filename, PATHINFO_EXTENSION);
        $i = 1;
        do {
            $candidate = $dir . DIRECTORY_SEPARATOR . $name . '-' . $i . ($ext ? '.' . $ext : '');
            $i++;
        } while (file_exists($candidate));
        return $candidate;
    }

    private function ensure_dir(string $dir): bool
    {
        // Prefer WP helper
        if (function_exists('wp_mkdir_p')) {
            return wp_mkdir_p($dir);
        }
        if (is_dir($dir)) return true;
        return @mkdir($dir, 0775, true);
    }

    /**
     * EN: Detect MIME using WordPress first, then finfo fallback.
     * IT: Rileva MIME usando prima WordPress, poi finfo in fallback.
     */
    private function detect_mime_wp_first(string $path): ?string
    {
        if (function_exists('wp_check_filetype')) {
            $ft = wp_check_filetype(basename($path), null);
            if (!empty($ft['type'])) {
                return $ft['type'];
            }
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
     * EN: Create an attachment post for the saved file and generate metadata.
     * IT: Crea un attachment per il file salvato e genera i metadata.
     *
     * $options['metadata'] può contenere: post_title, post_content, post_excerpt, post_parent, post_author, post_status
     */
    private function create_or_update_attachment(string $abs_path, string $rel_path, string $mime, array $options): ?int
    {
        if (!function_exists('wp_insert_attachment')) {
            return null;
        }

        $meta = isset($options['metadata']) && is_array($options['metadata']) ? $options['metadata'] : [];

        $attachment = [
            'post_mime_type' => $mime,
            'post_title'     => $meta['post_title']   ?? preg_replace('/\.[^.]+$/', '', basename($abs_path)),
            'post_content'   => $meta['post_content'] ?? '',
            'post_excerpt'   => $meta['post_excerpt'] ?? '',
            'post_status'    => $meta['post_status']  ?? 'inherit',
            'post_author'    => isset($meta['post_author']) ? (int)$meta['post_author'] : 0,
            'post_parent'    => isset($meta['post_parent']) ? (int)$meta['post_parent'] : 0,
        ];

        // Insert attachment
        $attach_id = wp_insert_attachment($attachment, $abs_path, $attachment['post_parent']);
        if (is_wp_error($attach_id) || !$attach_id) {
            return null;
        }

        // Generate attachment metadata (image sizes, etc.)
        // Ensure core image functions are available
        if (!function_exists('wp_generate_attachment_metadata')) {
            require_once ABSPATH . 'wp-admin/includes/image.php';
        }

        $metadata = wp_generate_attachment_metadata($attach_id, $abs_path);
        if (is_array($metadata)) {
            wp_update_attachment_metadata($attach_id, $metadata);
        }

        return (int)$attach_id;
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
