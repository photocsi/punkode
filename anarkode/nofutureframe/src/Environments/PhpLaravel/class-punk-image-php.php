<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-image-php.php
| DESCRIPTION:
| EN: Hybrid resizer: tries Imagick first (faster, better quality), falls back
|     to GD. Adds logging, EXIF auto-orient, atomic writes, progressive JPEG,
|     and reasonable performance/safety tweaks.
| IT: Resizer ibrido: prova prima Imagick (più veloce, qualità migliore),
|     con fallback a GD. Aggiunge logging, auto-orient EXIF, scrittura atomica,
|     JPEG progressivi e ottimizzazioni pragmatiche di performance/sicurezza.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ImageServiceInterface;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_ResizeLogic;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_PathUtils;

class PUNK_Image_Php implements PUNK_ImageServiceInterface
{
    /**********************************************************************
     * EN: Internal policy switches (tune as you like).
     * IT: Interruttori di policy interne (regolabili).
     **********************************************************************/
    protected bool $avoidUpscale       = true;  // EN: don't enlarge images / IT: evita upscaling
    protected bool $stripMetadata      = true;  // EN: remove EXIF/ICC (smaller files) / IT: rimuovi metadata
    protected bool $jpegProgressive    = true;  // EN: progressive JPEG / IT: JPEG progressivo
    protected float $imagickSharpenAmt = 0.0;   // EN: 0.0 = off; e.g., 0.3 for light unsharp / IT: 0.0 = disattivo

    /**********************************************************************
     * METHOD: punk_resizeTo()
     * EN: Try Imagick, fallback to GD. Logs steps and errors.
     * IT: Prova Imagick, fallback a GD. Logga passaggi ed errori.
     **********************************************************************/
    public function punk_resizeTo(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        $q = max(0, min(100, $quality));
        if (!is_file($src) || !is_readable($src)) {
            $this->log("Source not readable: {$src}", 'error');
            return false;
        }

        // read size/type early for policies
        [$ow, $oh, $t] = @getimagesize($src) ?: [0, 0, null];
        if (!$ow || !$oh) {
            $this->log("getimagesize failed: {$src}", 'error');
            return false;
        }

        // optional: avoid upscaling (shrink only)
        if ($this->avoidUpscale) {
            $w = min($w, $ow);
            $h = min($h, $oh);
        }

        // compute target fit
        [$nw, $nh] = PUNK_ResizeLogic::punk_fitBox($ow, $oh, $w, $h);

        // ensure destination dir
        PUNK_ResizeLogic::punk_ensureDir($dest);
        $tmp = $dest . '.tmp.' . bin2hex(random_bytes(4));
        $ext = PUNK_PathUtils::punk_extension($dest) ?: 'jpeg';

        // try Imagick first (faster/leaner)
        if (extension_loaded('imagick')) {
            try {
                $this->log("Imagick path for {$src} → {$dest} ({$nw}x{$nh})", 'debug');
                $ok = $this->resizeWithImagick($src, $tmp, $nw, $nh, $q, $ext);
                if ($ok) {
                    if (!$this->atomicMove($tmp, $dest)) {
                        @unlink($tmp);
                        $this->log("Atomic move failed (Imagick): {$tmp} → {$dest}", 'error');
                        return false;
                    }
                    return ['path' => $dest, 'width' => $nw, 'height' => $nh];
                }
                // fallthrough to GD if Imagick failed
                $this->log("Imagick failed, falling back to GD", 'warning');
            } catch (\Throwable $e) {
                $this->log("Imagick exception: " . $e->getMessage(), 'error');
            } finally {
                @unlink($tmp); // in case wrote partials
            }
        }

        // fallback: GD
        if (!extension_loaded('gd')) {
            $this->log("Neither Imagick nor GD available", 'error');
            return false;
        }
        try {
            $this->log("GD path for {$src} → {$dest} ({$nw}x{$nh})", 'debug');
            $ok = $this->resizeWithGd($src, $tmp, $nw, $nh, $q, $t, $ext);
            if (!$ok) {
                @unlink($tmp);
                $this->log("GD resize failed", 'error');
                return false;
            }
            if (!$this->atomicMove($tmp, $dest)) {
                @unlink($tmp);
                $this->log("Atomic move failed (GD): {$tmp} → {$dest}", 'error');
                return false;
            }
            return ['path' => $dest, 'width' => $nw, 'height' => $nh];
        } catch (\Throwable $e) {
            @unlink($tmp);
            $this->log("GD exception: " . $e->getMessage(), 'error');
            return false;
        }
    }

    /**********************************************************************
     * IMAGICK IMPLEMENTATION
     * EN: Fast path: auto-orient, thumbnail, set compression, strip metadata.
     * IT: Percorso veloce: auto-orient, thumbnail, set compressione, strip meta.
     **********************************************************************/
    protected function resizeWithImagick(string $src, string $tmp, int $nw, int $nh, int $q, string $ext): bool
    {
        $im = new \Imagick();
        $im->readImage($src);

        // auto-orient via EXIF
        if (method_exists($im, 'autoOrient')) {
            $im->autoOrient();
        }

        // multi-frame (GIF/WEBP animati): coalesce → resize → deconstruct
        $isAnimated = $im->getNumberImages() > 1;
        if ($isAnimated) {
            $im = $im->coalesceImages();
            foreach ($im as $frame) {
                $frame->thumbnailImage($nw, $nh, true, true); // bestfit + filter
                if ($this->imagickSharpenAmt > 0) {
                    // unsharp mask: radius, sigma, amount, threshold
                    $frame->unsharpMaskImage(0, 0.8, $this->imagickSharpenAmt, 0.02);
                }
                if ($this->stripMetadata) {
                    $frame->stripImage();
                }
            }
            $im = $im->deconstructImages();
        } else {
            $im->thumbnailImage($nw, $nh, true, true); // bestfit + filter lanczos
            if ($this->imagickSharpenAmt > 0) {
                $im->unsharpMaskImage(0, 0.8, $this->imagickSharpenAmt, 0.02);
            }
            if ($this->stripMetadata) {
                $im->stripImage();
            }
        }

        switch ($ext) {
            case 'jpeg':
                $im->setImageFormat('jpeg');
                $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $im->setImageCompressionQuality($q);
                if ($this->jpegProgressive) {
                    $im->setInterlaceScheme(\Imagick::INTERLACE_JPEG);
                }
                break;

            case 'png':
                $im->setImageFormat('png');
                // Imagick: usare compression + quality; q alto = meno compressione (più veloce)
                $im->setImageCompression(\Imagick::COMPRESSION_ZIP);
                $im->setImageCompressionQuality($q); // 0..100
                break;

            case 'webp':
                $im->setImageFormat('webp');
                // Lossy WebP: usa quality; per lossless servirebbe setOption('webp:lossless', true)
                $im->setImageCompressionQuality($q);
                break;

            case 'gif':
                $im->setImageFormat('gif'); // attenzione: animazioni già gestite sopra
                break;

            default:
                $im->setImageFormat('jpeg');
                $im->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $im->setImageCompressionQuality($q);
                if ($this->jpegProgressive) {
                    $im->setInterlaceScheme(\Imagick::INTERLACE_JPEG);
                }
                break;
        }

        $ok = $isAnimated ? $im->writeImages($tmp, true) : $im->writeImage($tmp);
        $im->clear();
        $im->destroy();
        return (bool)$ok;
    }

    /**********************************************************************
     * GD IMPLEMENTATION
     * EN: Alpha-preserving resample, EXIF auto-orient, progressive JPEG.
     * IT: Resample con alpha, auto-orient EXIF, JPEG progressivo.
     **********************************************************************/
    protected function resizeWithGd(string $src, string $tmp, int $nw, int $nh, int $q, int $t, string $ext): bool
    {
        // decode by type
        switch ($t) {
            case IMAGETYPE_JPEG:
                $im = @imagecreatefromjpeg($src);
                // EXIF auto-orient (if available)
                if ($im && function_exists('exif_read_data')) {
                    $exif = @exif_read_data($src);
                    $im = $this->gdApplyExifOrientation($im, $exif);
                }
                break;
            case IMAGETYPE_PNG:
                $im = @imagecreatefrompng($src);
                break;
            case IMAGETYPE_GIF:
                $im = @imagecreatefromgif($src);
                break;
            case IMAGETYPE_WEBP:
                $im = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($src) : false;
                break;
            default:
                return false;
        }
        if (!$im) return false;

        $ow = imagesx($im);
        $oh = imagesy($im);

        $out = imagecreatetruecolor($nw, $nh);

        // preserve alpha for PNG/GIF/WEBP
        if (in_array($t, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($out, false);
            imagesavealpha($out, true);
            $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
            imagefilledrectangle($out, 0, 0, $nw, $nh, $transparent);
        }

        // high-quality resampling
        imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $ow, $oh);

        // encode by destination ext
        $ok = false;
        switch ($ext) {
            case 'jpeg':
                if ($this->jpegProgressive) {
                    imageinterlace($out, true);
                }
                $ok = imagejpeg($out, $tmp, $q);
                break;

            case 'png':
                // map 0..100 → 9..0 (inverted)
                $pq = (int)round(9 - (($q / 100) * 9));
                $ok = imagepng($out, $tmp, $pq);
                break;

            case 'webp':
                $ok = function_exists('imagewebp') ? imagewebp($out, $tmp, $q) : imagejpeg($out, $tmp, $q);
                break;

            case 'gif':
                $ok = imagegif($out, $tmp);
                break;

            default:
                if ($this->jpegProgressive) {
                    imageinterlace($out, true);
                }
                $ok = imagejpeg($out, $tmp, $q);
        }

        imagedestroy($im);
        imagedestroy($out);
        return $ok;
    }

    /**********************************************************************
     * HELPERS: logging, EXIF, atomic move
     **********************************************************************/
    protected function log(string $msg, string $level = 'info'): void
    {
        if (function_exists('punk_log')) {
            punk_log("[PUNK_Image_Php] {$msg}", $level);
        }
    }

    protected function gdApplyExifOrientation($im, ?array $exif)
    {
        if (!$im || !$exif || !isset($exif['Orientation'])) return $im;
        $orientation = (int)$exif['Orientation'];
        switch ($orientation) {
            case 3:  $im = imagerotate($im, 180, 0); break;
            case 6:  $im = imagerotate($im, -90, 0); break; // 90 CW
            case 8:  $im = imagerotate($im, 90, 0);  break; // 90 CCW
            case 2:  function_exists('imageflip') && imageflip($im, IMG_FLIP_HORIZONTAL); break;
            case 4:  function_exists('imageflip') && imageflip($im, IMG_FLIP_VERTICAL); break;
            case 5:  function_exists('imageflip') && imageflip($im, IMG_FLIP_VERTICAL); $im = imagerotate($im, -90, 0); break;
            case 7:  function_exists('imageflip') && imageflip($im, IMG_FLIP_HORIZONTAL); $im = imagerotate($im, -90, 0); break;
        }
        return $im;
    }

    protected function atomicMove(string $tmp, string $dest): bool
    {
        if (@rename($tmp, $dest)) {
            return true;
        }
        // Windows fallback
        if (@copy($tmp, $dest)) {
            @unlink($tmp);
            return true;
        }
        return false;
    }
}
