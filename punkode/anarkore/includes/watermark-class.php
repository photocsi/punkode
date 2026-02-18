<?php
namespace Punkode;

/**
 * =============================================================================
 * FILE: watermark-class.php
 * =============================================================================
 * EN: Apply a REPEATED (tiled) PNG watermark (with alpha) on top of an existing
 *     image (NO resize). Safer alpha handling (no black artifacts).
 * IT: Applica un watermark PNG RIPETUTO (tiled, con alpha) sopra un'immagine
 *     esistente (SENZA resize). Gestione alpha “pulita” (niente nero puntinato).
 * =============================================================================
 */

class WATERMARK_PK
{
    private string $watermarkPngPath;

    /**
     * EN: Watermark width as a fraction of image width (0.18 = 18%).
     * IT: Larghezza watermark come frazione della larghezza immagine (0.18 = 18%).
     */
    private float $wmScale;

    /**
     * EN: Opacity 0..100 (additional opacity on top of PNG alpha).
     * IT: Opacità 0..100 (opacità aggiuntiva sopra l'alpha del PNG).
     */
    private int $opacity;

    /**
     * EN: Spacing (px) between tiles (both X and Y).
     * IT: Spaziatura (px) tra i watermark ripetuti (sia X che Y).
     */
    private int $spacing;

    /**
     * EN: Rotation angle in degrees (e.g. -30 for diagonal). 0 = no rotation.
     * IT: Angolo di rotazione in gradi (es. -30 diagonale). 0 = nessuna rotazione.
     */
    private int $angle;

    public function __construct(
        string $watermarkPngPath,
        float $wmScale = 0.18,
        int $opacity = 45,
        int $spacing = 160,
        int $angle = -30
    ) {
        $this->watermarkPngPath = $watermarkPngPath;
        $this->wmScale          = max(0.01, min(0.6, $wmScale));
        $this->opacity          = max(0, min(100, $opacity));
        $this->spacing          = max(0, $spacing);
        $this->angle            = (int)$angle;
    }

    /**
     * EN: Apply tiled watermark, keep original image dimensions (NO resize).
     * IT: Applica watermark ripetuto mantenendo le dimensioni originali (NO resize).
     *
     * @param string $srcPath  Path sorgente (jpg/png/webp se GD supporta)
     * @param string $destPath Path destinazione (consigliato .jpg)
     * @param int    $jpegQ    Qualità JPEG 0..100
     * @return bool
     */
    public function render(string $srcPath, string $destPath, int $jpegQ = 82): bool
    {
        if (!is_file($srcPath) || !is_readable($srcPath)) return false;
        if (!is_file($this->watermarkPngPath) || !is_readable($this->watermarkPngPath)) return false;

        $img = $this->loadImage($srcPath);
        if (!$img) return false;

        // IMPORTANT: destination must blend alpha sources correctly
        imagealphablending($img, true);

        $wm = @imagecreatefrompng($this->watermarkPngPath);
        if (!$wm) {
            imagedestroy($img);
            return false;
        }

        // Ensure watermark keeps alpha
        imagealphablending($wm, false);
        imagesavealpha($wm, true);

        // ---------------------------
        // 1) SCALE watermark
        // ---------------------------
        $imgW = imagesx($img);
        $wmW  = imagesx($wm);
        $wmH  = imagesy($wm);

        $targetWmW = (int)max(1, round($imgW * $this->wmScale));
        $ratio     = ($wmW > 0) ? ($targetWmW / $wmW) : 1.0;
        $targetWmH = (int)max(1, round($wmH * $ratio));

        $wmScaled = imagescale($wm, $targetWmW, $targetWmH, IMG_BICUBIC_FIXED);
        imagedestroy($wm);

        if (!$wmScaled) {
            imagedestroy($img);
            return false;
        }

        imagealphablending($wmScaled, false);
        imagesavealpha($wmScaled, true);

        // ---------------------------
        // 2) ROTATE watermark (alpha-safe)
        // ---------------------------
        if ($this->angle !== 0) {
            $wmRot = $this->rotatePngWithAlpha($wmScaled, $this->angle);
            imagedestroy($wmScaled);

            if (!$wmRot) {
                imagedestroy($img);
                return false;
            }

            $wmScaled = $wmRot;
        }

        // ---------------------------
        // 3) APPLY additional opacity by adjusting alpha ONCE
        //    (avoids imagecopymerge() black artifacts)
        // ---------------------------
        if ($this->opacity < 100) {
            $wmOpacity = $this->applyOpacityToPng($wmScaled, $this->opacity);
            imagedestroy($wmScaled);

            if (!$wmOpacity) {
                imagedestroy($img);
                return false;
            }
            $wmScaled = $wmOpacity;
        } else {
            // ensure blend-ready
            imagealphablending($wmScaled, false);
            imagesavealpha($wmScaled, true);
        }

        // ---------------------------
        // 4) TILE across the image
        // ---------------------------
        $this->applyTiledWatermark($img, $wmScaled);

        imagedestroy($wmScaled);

        // ---------------------------
        // 5) SAVE JPEG
        // ---------------------------
        @imageinterlace($img, true);
        $jpegQ = max(40, min(95, $jpegQ));

        $saved = @imagejpeg($img, $destPath, $jpegQ);
        imagedestroy($img);

        return (bool)$saved;
    }

    /**
     * EN: Tile watermark over the image (uses imagecopy, alpha-safe).
     * IT: Ripete il watermark su tutta l'immagine (usa imagecopy, alpha-safe).
     */
    private function applyTiledWatermark(\GdImage $img, \GdImage $wm): void
    {
        $imgW = imagesx($img);
        $imgH = imagesy($img);

        $wmW = imagesx($wm);
        $wmH = imagesy($wm);

        $stepX = $wmW + $this->spacing;
        $stepY = $wmH + $this->spacing;

        // Start negative to avoid gaps on borders
        for ($y = -$wmH; $y < $imgH + $wmH; $y += $stepY) {
            $rowShift = ((int)(($y + $wmH) / max(1, $stepY)) % 2 === 0) ? 0 : (int)round($stepX / 2);

            for ($x = -$wmW - $rowShift; $x < $imgW + $wmW; $x += $stepX) {
                // Alpha-safe copy (NO imagecopymerge)
                @imagecopy($img, $wm, $x, $y, 0, 0, $wmW, $wmH);
            }
        }
    }

    /**
     * EN: Rotate a PNG keeping full alpha (no black background).
     * IT: Ruota un PNG mantenendo alpha pieno (niente sfondo nero).
     */
    private function rotatePngWithAlpha(\GdImage $src, int $angle): \GdImage|false
    {
        // Create a fully transparent background color
        imagealphablending($src, false);
        imagesavealpha($src, true);

        $transparent = imagecolorallocatealpha($src, 0, 0, 0, 127);

        $rot = @imagerotate($src, $angle, $transparent);
        if (!$rot) return false;

        imagealphablending($rot, false);
        imagesavealpha($rot, true);

        // Sometimes imagerotate leaves edge pixels with wrong alpha;
        // force-fill corners as transparent (cheap safety).
        $w = imagesx($rot);
        $h = imagesy($rot);
        $t = imagecolorallocatealpha($rot, 0, 0, 0, 127);
        // no full fill (would wipe); we just ensure alpha mode is correct.

        return $rot;
    }

    /**
     * EN: Apply additional opacity (0..100) by modifying per-pixel alpha ONCE.
     * IT: Applica opacità aggiuntiva (0..100) modificando alpha per-pixel UNA VOLTA.
     *
     * Why: avoids imagecopymerge() artifacts.
     */
    private function applyOpacityToPng(\GdImage $src, int $opacity): \GdImage|false
    {
        $opacity = max(0, min(100, $opacity));

        $w = imagesx($src);
        $h = imagesy($src);

        $out = imagecreatetruecolor($w, $h);
        if (!$out) return false;

        imagealphablending($out, false);
        imagesavealpha($out, true);

        $transparent = imagecolorallocatealpha($out, 0, 0, 0, 127);
        imagefilledrectangle($out, 0, 0, $w, $h, $transparent);

        // Copy source
        imagecopy($out, $src, 0, 0, 0, 0, $w, $h);

        // Convert opacity 0..100 to multiplier
        $mul = $opacity / 100.0;

        // Adjust alpha per pixel: newAlpha = 127 - (127 - oldAlpha) * mul
        // (so 0 opacity => fully transparent; 100 => unchanged)
        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                $rgba = imagecolorat($out, $x, $y);

                $a = ($rgba & 0x7F000000) >> 24; // 0..127
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                // If fully transparent, skip (micro-optimization)
                if ($a >= 127) continue;

                $newA = (int)round(127 - ((127 - $a) * $mul));
                $newA = max(0, min(127, $newA));

                $col = imagecolorallocatealpha($out, $r, $g, $b, $newA);
                imagesetpixel($out, $x, $y, $col);
            }
        }

        return $out;
    }

    /**
     * EN: Load JPG/PNG/WEBP depending on extension/mime.
     * IT: Carica JPG/PNG/WEBP in base a estensione/mime.
     */
    private function loadImage(string $srcPath): \GdImage|false
    {
        $ext = strtolower(pathinfo($srcPath, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg'], true)) return @imagecreatefromjpeg($srcPath);
        if ($ext === 'png')  return @imagecreatefrompng($srcPath);
        if ($ext === 'webp' && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($srcPath);

        $mime = @mime_content_type($srcPath) ?: '';
        if (str_contains($mime, 'jpeg')) return @imagecreatefromjpeg($srcPath);
        if (str_contains($mime, 'png'))  return @imagecreatefrompng($srcPath);
        if (str_contains($mime, 'webp') && function_exists('imagecreatefromwebp')) return @imagecreatefromwebp($srcPath);

        return false;
    }
}
