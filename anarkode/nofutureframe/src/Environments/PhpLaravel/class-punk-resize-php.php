<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Environments/PhpLaravel/class-punk-resize-php.php
| DESCRIPTION:
| EN: Pure PHP image resize (Imagick if available, else GD). Writes to $dest
|     local path only (TEMP in our pipeline). No final storage here.
| IT: Resize immagini in PHP puro (Imagick se disponibile, altrimenti GD).
|     Scrive solo su $dest locale (TEMP nella nostra pipeline). Nessuno storage finale.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ResizeInterface;

final class PUNK_ResizePhp implements PUNK_ResizeInterface
{
    public function punk_resize_to(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        if (!is_file($src)) return false;

        $w = max(1, $w);
        $h = max(1, $h);
        $quality = max(1, min(100, $quality));

        // Ensure destination directory exists
        $dir = dirname($dest);
        if (!@is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }

        // Prefer Imagick when available
        if (class_exists('\Imagick')) {
            try {
                $im = new \Imagick($src);

                // Auto-orient if EXIF present
                if (method_exists($im, 'getImageOrientation')) {
                    $orientation = $im->getImageOrientation();
                    if ($orientation && method_exists($im, 'autoOrient')) {
                        $im->autoOrient();
                    }
                }

                $origW = $im->getImageWidth();
                $origH = $im->getImageHeight();

                // Fit within box, keep aspect (no crop)
                $im->thumbnailImage($w, $h, true, true);

                // Decide format from dest extension (fallback jpg)
                $ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
                $format = $ext ?: 'jpg';
                $im->setImageFormat($format);

                // Quality hints
                $im->setImageCompressionQuality($quality);
                if (in_array($format, ['jpg', 'jpeg'], true)) {
                    $im->setImageInterlaceScheme(\Imagick::INTERLACE_PLANE);
                }

                $ok = $im->writeImage($dest);
                if (!$ok) return false;

                $mime = $im->getImageMimeType() ?: null;
                $im->clear();
                $im->destroy();

                return [
                    'path'   => $dest,
                    'width'  => (int)($w = getimagesize($dest)[0] ?? 0),
                    'height' => (int)($h = getimagesize($dest)[1] ?? 0),
                    'mime'   => $mime,
                    'bytes'  => (int)(filesize($dest) ?: 0),
                ];
            } catch (\Throwable $e) {
                // fallback to GD
            }
        }

        // ---- GD fallback ----
        [$origW, $origH, $type] = @getimagesize($src) ?: [0,0,0];
        if ($origW < 1 || $origH < 1) return false;

        $scale = min($w / $origW, $h / $origH, 1);
        $nw = max(1, (int)floor($origW * $scale));
        $nh = max(1, (int)floor($origH * $scale));

        // Create source
        $srcIm = match ($type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($src),
            IMAGETYPE_PNG  => imagecreatefrompng($src),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? imagecreatefromwebp($src) : null,
            default        => null,
        };
        if (!$srcIm) return false;

        // Create destination
        $dstIm = imagecreatetruecolor($nw, $nh);
        imagealphablending($dstIm, false);
        imagesavealpha($dstIm, true);

        imagecopyresampled($dstIm, $srcIm, 0, 0, 0, 0, $nw, $nh, $origW, $origH);

        // Decide format from dest extension (fallback jpg)
        $ext = strtolower(pathinfo($dest, PATHINFO_EXTENSION));
        $format = $ext ?: match ($type) {
            IMAGETYPE_PNG  => 'png',
            IMAGETYPE_WEBP => 'webp',
            default        => 'jpg',
        };

        $ok = match ($format) {
            'png'  => imagepng($dstIm, $dest, (int)round((100 - $quality) / 100 * 9)),
            'webp' => function_exists('imagewebp') ? imagewebp($dstIm, $dest, $quality) : false,
            default=> imagejpeg($dstIm, $dest, $quality),
        };

        imagedestroy($srcIm);
        imagedestroy($dstIm);

        if (!$ok) return false;

        [$rw, $rh] = @getimagesize($dest) ?: [0,0];
        $mime = mime_content_type($dest) ?: null;

        return [
            'path'   => $dest,
            'width'  => (int)$rw,
            'height' => (int)$rh,
            'mime'   => $mime,
            'bytes'  => (int)(filesize($dest) ?: 0),
        ];
    }
}
