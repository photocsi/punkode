<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Environments/wp/class-punk-resize-wp.php
| DESCRIPTION:
| EN: WP resize adapter. Writes the resized image to the given $dest path
|     (which, in the new pipeline, is a TEMP file). No final storage here.
| IT: Adapter di resize per WP. Scrive l'immagine ridimensionata nel path $dest
|     (che, nella nuova pipeline, è un file TEMP). Nessuno storage finale qui.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Environments\wp;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ResizeInterface;

final class PUNK_ResizeWp implements PUNK_ResizeInterface
{
    /**
     * @inheritDoc
     */
    public function punk_resize_to(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        if (!\function_exists('\wp_get_image_editor')) {
            return false;
        }
        if (!\is_file($src)) {
            return false;
        }

        $quality = \max(1, \min(100, $quality));
        $w = \max(1, $w);
        $h = \max(1, $h);

        // Ensure destination directory exists
        $dir = \dirname($dest);
        if (!@\is_dir($dir)) {
            if (\function_exists('\wp_mkdir_p')) {
                if (!\wp_mkdir_p($dir)) {
                    return false;
                }
            } else {
                if (!@\mkdir($dir, 0775, true)) {
                    return false;
                }
            }
        }

        $editor = \wp_get_image_editor($src);
        if (\is_wp_error($editor)) {
            return false;
        }

        $editor->set_quality($quality);

        // Resize keeping aspect ratio (no crop)
        $res = $editor->resize($w, $h, false);
        if (\is_wp_error($res)) {
            return false;
        }

        // Save to the EXACT $dest provided by the caller (temp file in our pipeline)
        $saved = $editor->save($dest);
        if (\is_wp_error($saved)) {
            return false;
        }

        // WP returns: path, file, width, height, mime-type
        $outPath = $saved['path'] ?? $dest;
        $width   = (int)($saved['width']  ?? 0);
        $height  = (int)($saved['height'] ?? 0);
        $mime    = $saved['mime-type'] ?? ($saved['mime'] ?? null);
        $bytes   = \is_file($outPath) ? ((int)\filesize($outPath)) : 0;

        return [
            'path'   => $outPath,
            'width'  => $width,
            'height' => $height,
            'mime'   => $mime,
            'bytes'  => $bytes,
        ];
    }
}
