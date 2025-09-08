<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-resize-php.php
| DESCRIPTION:
| EN: Hybrid resizer for pure-PHP contexts: tries Imagick first (quality/speed),
|     falls back to GD. Adds logging, EXIF auto-orient, atomic writes, progressive
|     JPEG, and pragmatic safety/perf tweaks.
| IT: Resizer ibrido per contesti PHP "puri": prova prima Imagick (qualità/velocità),
|     con fallback a GD. Aggiunge logging, auto-orient EXIF, scritture atomiche,
|     JPEG progressivi e accorgimenti pragmatici di sicurezza/performance.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;
// NOTE EN: If you move this file under environments/php/, update the namespace to ...\Environments\Php
// NOTE IT: Se sposti il file in environments/php/, aggiorna il namespace in ...\Environments\Php

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ResizeInterface;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_ResizeLogic;
use Punkode\Anarkode\NoFutureFrame\Core\PUNK_PathUtils;

class PUNK_ResizePhp implements PUNK_ResizeInterface
{
    /**********************************************************************
     * EN: Internal policy switches (tune as needed).
     * IT: Interruttori di policy interne (regolali a piacere).
     **********************************************************************/
    protected bool $avoidUpscale       = true;   // EN: shrink-only / IT: solo riduzione
    protected bool $stripMetadata      = true;   // EN: strip EXIF/ICC / IT: togli metadati
    protected bool $jpegProgressive    = true;   // EN: progressive JPEG / IT: JPEG progressivo
    protected float $imagickSharpenAmt = 0.0;    // EN: 0.0=off; e.g. 0.3 light / IT: 0.0=off

    /**
     * EN: Resize entrypoint. Imagick → GD fallback. Returns ['path','width','height'] or false.
     * IT: Entrata di resize. Imagick → fallback GD. Ritorna ['path','width','height'] o false.
     */
    public function punk_resizeTo(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        $q = max(0, min(100, (int)$quality));

        if (!is_file($src) || !is_readable($src)) {
            $this->log("Source not readable: {$src}", 'error');
            return false;
        }

        // EN: Read size/type early for policies.
        // IT: Leggi dimensioni/tipo prima, per applicare le policy.
        [$ow, $oh, $t] = @getimagesize($src) ?: [0, 0, null];
        if (!$ow || !$oh) {
            $this->log("getimagesize failed: {$src}", 'error');
            return false;
        }

        if ($this->avoidUpscale) {
            $w = min($w, $ow);
            $h = min($h, $oh);
        }

        // EN: Fit inside target box.
        // IT: Adatta dentro il box bersaglio.
        [$nw, $nh] = PUNK_ResizeLogic::punk_fitBox($ow, $oh, $w, $h);

        // EN: Ensure destination dir and temp path in same FS (atomic rename).
        // IT: Assicura la directory e tmp sullo stesso FS (rename atomico).
        PUNK_ResizeLogic::punk_ensure_dir($dest);
        $tmp = $this->makeTmpPath($dest);

        $ext = strtolower(PUNK_PathUtils::punk_extension($dest) ?: 'jpeg');

        // ---------- Imagick fast path ----------
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
                $this->log("Imagick failed, falling back to GD", 'warning');
            } catch (\Throwable $e) {
                $this->log("Imagick exception: " . $e->getMessage(), 'error');
            } finally {
                @unlink($tmp); // EN/IT: cleanup eventuale
            }
        }

        // ---------- GD fallback ----------
        if (!extension_loaded('gd')) {
            $this->log("Neither Imagick nor GD available", 'error');
            return false;
        }

        $tmp = $this->makeTmpPath($dest); // EN/IT: nuovo tmp per GD
        try {
            $this->log("GD path for {$src} → {$dest} ({$nw}x{$nh})", 'debug');
            $ok = $this->resizeWithGd($src, $tmp, $nw, $nh, $q, (int)$t, $ext);
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
     **********************************************************************/
    protected function resizeWithImagick(string $src, string $tmp, int $nw, int $nh, int $q, string $ext): bool
    {
        $im = new \Imagick();
        $im->readImage($src);

        // EN: Auto-orient via EXIF if present.
        // IT: Auto-orienta via EXIF se presente.
        if (method_exists($im, 'autoOrientImage')) {
            $im->autoOrientImage();
        }

        $isAnimated = $im->getNumberImages() > 1;

        if ($isAnimated) {
            $coalesced = $im->coalesceImages();
            foreach ($coalesced as $frame) {
                $frame->thumbnailImage($nw, $nh, true, true); // bestfit + good filter
                if ($this->imagickSharpenAmt > 0) {
                    $frame->unsharpMaskImage(0, 0.8, $this->imagickSharpenAmt, 0.02);
                }
                if ($this->stripMetadata) {
                    $frame->stripImage();
                }
                $this->applyImagickFormatOptions($frame, $ext, $q);
            }
            // EN: Deconstruct recomputes deltas for animations.
            // IT: Deconstruct ricalcola i delta per le animazioni.
            $im = $coalesced->deconstructImages();
        } else {
            $im->thumbnailImage($nw, $nh, true, true);
            if ($this->imagickSharpenAmt > 0) {
                $im->unsharpMaskImage(0, 0.8, $this->imagickSharpenAmt, 0.02);
            }
            if ($this->stripMetadata) {
                $im->stripImage();
            }
            $this->applyImagickFormatOptions($im, $ext, $q);
        }

        $ok = $isAnimated ? $im->writeImages($tmp, true) : $im->writeImage($tmp);
        $im->clear();
        $im->destroy();
        return (bool)$ok;
    }

    /**
     * EN: Apply format/compression knobs per-target.
     * IT: Applica formato/compressione in base al target.
     */
    protected function applyImagickFormatOptions(\Imagick $img, string $ext, int $q): void
    {
        switch ($ext) {
            case 'jpeg':
            case 'jpg':
                $img->setImageFormat('jpeg');
                $img->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality($q);
                if ($this->jpegProgressive) {
                    $img->setInterlaceScheme(\Imagick::INTERLACE_JPEG);
                }
                break;

            case 'png':
                $img->setImageFormat('png');
                $img->setImageCompression(\Imagick::COMPRESSION_ZIP);
                // EN: map 0..100 → compression-level 9..0 (higher q = faster, bigger)
                // IT: mappa 0..100 → livello 9..0 (q alto = più veloce, file più grande)
                $level = max(0, min(9, (int)round(9 - (($q / 100) * 9))));
                $img->setOption('png:compression-level', (string)$level);
                // optional: $img->setOption('png:compression-filter','5');
                break;

            case 'webp':
                $img->setImageFormat('webp');
                if ($q >= 100) {
                    $img->setOption('webp:lossless', 'true');
                } else {
                    $img->setImageCompressionQuality($q);
                }
                break;

            case 'gif':
                $img->setImageFormat('gif');
                break;

            default:
                // EN: default to JPEG for unknown extensions
                // IT: di default salva come JPEG se estensione sconosciuta
                $img->setImageFormat('jpeg');
                $img->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $img->setImageCompressionQuality($q);
                if ($this->jpegProgressive) {
                    $img->setInterlaceScheme(\Imagick::INTERLACE_JPEG);
                }
                break;
        }
    }

    /**********************************************************************
     * GD IMPLEMENTATION
     **********************************************************************/
    protected function resizeWithGd(string $src, string $tmp, int $nw, int $nh, int $q, int $t, string $ext): bool
    {
        // EN: Decode by source type.
        // IT: Decodifica in base al tipo sorgente.
        switch ($t) {
            case IMAGETYPE_JPEG:
                $im = @imagecreatefromjpeg($src);
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

        // EN: Preserve alpha for PNG/GIF/WEBP.
        // IT: Preserva alpha per PNG/GIF/WEBP.
        if (in_array($t, [IMAGETYPE_PNG, IMAGETYPE_GIF, IMAGETYPE_WEBP], true)) {
            imagealphablending($out, false);
            imagesavealpha($out, true);
            $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
            imagefilledrectangle($out, 0, 0, $nw, $nh, $transparent);
        }

        // EN: High-quality resampling.
        // IT: Ricampionamento di qualità.
        imagecopyresampled($out, $im, 0, 0, 0, 0, $nw, $nh, $ow, $oh);

        // EN: Encode by destination extension.
        // IT: Codifica in base all'estensione di destinazione.
        $ok = false;
        switch ($ext) {
            case 'jpeg':
            case 'jpg':
                if ($this->jpegProgressive) {
                    imageinterlace($out, true);
                }
                $ok = imagejpeg($out, $tmp, $q);
                break;

            case 'png':
                $pq = (int)round(9 - (($q / 100) * 9)); // 0..100 → 9..0
                $ok = imagepng($out, $tmp, $pq);
                break;

            case 'webp':
                $ok = function_exists('imagewebp') ? imagewebp($out, $tmp, $q) : imagejpeg($out, $tmp, $q);
                break;

            case 'gif':
                // EN: Convert to palette to keep transparency in GIF.
                // IT: Converti a palette per mantenere la trasparenza nel GIF.
                if (function_exists('imagetruecolortopalette')) {
                    imagetruecolortopalette($out, true, 256);
                    $trIndex = imagecolortransparent($out);
                    if ($trIndex >= 0) {
                        // already has a transparent index
                    }
                }
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
        return (bool)$ok;
    }

    /**********************************************************************
     * HELPERS: logging, EXIF, atomic move, tmp path
     **********************************************************************/
    protected function log(string $msg, string $level = 'info'): void
    {
        if (function_exists('punk_log')) {
            punk_log("[PUNK_Resize_Php] {$msg}", $level);
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
        // EN: Windows fallback
        // IT: Fallback per Windows
        if (@copy($tmp, $dest)) {
            @unlink($tmp);
            return true;
        }
        return false;
    }

    protected function makeTmpPath(string $dest): string
    {
        $suffix = '';
        try {
            $suffix = bin2hex(random_bytes(4));
        } catch (\Throwable $e) {
            $suffix = bin2hex((string)uniqid('', true));
        }
        return $dest . '.tmp.' . $suffix;
    }
}

