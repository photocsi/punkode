<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-save-service.php
| DESCRIPTION:
| EN: Orchestrates derivatives generation (via resizer) and writes a sidecar
|     JSON manifest next to the original. Pure "save" layer: no upload, no UI.
| IT: Orquestra la generazione dei derivati (via resizer) e salva un manifest
|     JSON accanto all’originale. Livello "save" puro: niente upload, niente UI.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Core;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_Resize;

class PUNK_SaveUtils
{
    public function __construct(protected PUNK_Resize $resizer) {}

    /**
     * EN: Generate derivatives and persist a JSON manifest.
     * IT: Genera i derivati e persiste un manifest JSON.
     */
    public function punk_save_image(
        string $original_path,
        string $dest_dir,
        string $basename,
        array $sizes,     // label => [w, h, ext]
        int $quality      // 0..100
    ): array|false {
        // sanity checks
        if (!is_file($original_path) || !is_readable($original_path)) {
            if (function_exists('punk_log')) punk_log('[PUNK_Save] Original not readable: '.$original_path, 'error');
            return false;
        }
        if (!is_dir($dest_dir) && !@mkdir($dest_dir, 0775, true)) {
            if (function_exists('punk_log')) punk_log('[PUNK_Save] Cannot create dir: '.$dest_dir, 'error');
            return false;
        }

        // manifest base
        $manifest = [
            'original' => $original_path,
            'sizes'    => [],
            'meta'     => [
                'bytes_original' => @filesize($original_path) ?: 0,
                'mime'           => function_exists('mime_content_type') ? (mime_content_type($original_path) ?: 'application/octet-stream') : 'application/octet-stream',
                'hash_original'  => is_readable($original_path) ? ('sha256:' . hash_file('sha256', $original_path)) : '',
            ],
        ];

        // loop sizes → call resizer
        $name_no_ext = pathinfo($basename, PATHINFO_FILENAME);
        foreach ($sizes as $label => $cfg) {
            if (!is_array($cfg) || count($cfg) < 3) {
                if (function_exists('punk_log')) punk_log("[PUNK_Save] Bad size cfg for '{$label}'", 'error');
                return false;
            }
            [$w, $h, $ext] = $cfg;
            $w = (int)$w; $h = (int)$h; $ext = strtolower((string)$ext);
            if ($w <= 0 || $h <= 0 || $ext === '') {
                if (function_exists('punk_log')) punk_log("[PUNK_Save] Invalid size params for '{$label}'", 'error');
                return false;
            }

            $dest = rtrim($dest_dir, '/\\') . '/' . $name_no_ext . "-{$label}.{$ext}";
            $out  = $this->resizer->punk_resize_to($original_path, $dest, $w, $h, $quality);
            if ($out === false) {
                if (function_exists('punk_log')) punk_log("[PUNK_Save] Resize failed for label '{$label}'", 'error');
                return false;
            }
            // $out = ['path'=>..., 'width'=>..., 'height'=>...]
            $manifest['sizes'][$label] = $out;
        }

        // write sidecar JSON manifest
        $json_path = rtrim($dest_dir, '/\\') . '/' . $name_no_ext . '.manifest.json';
        $json_ok   = @file_put_contents($json_path, json_encode($manifest, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT));
        if ($json_ok === false) {
            if (function_exists('punk_log')) punk_log('[PUNK_Save] Failed writing manifest: '.$json_path, 'error');
            // nota: anche se il JSON fallisce, i derivati sono già stati creati
            // puoi decidere di tornare false o comunque il manifest in memoria:
            return $manifest;
        }

        return $manifest;
    }
}
