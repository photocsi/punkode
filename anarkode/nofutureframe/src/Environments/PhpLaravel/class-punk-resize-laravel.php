<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Environments/PhpLaravel/class-punk-resize-laravel.php
| DESCRIPTION:
| EN: Laravel resize adapter. Resolves $src to a LOCAL temp if needed and
|     delegates the actual resize to PUNK_ResizePhp writing to LOCAL $dest.
|     No final storage/upload here – SaveCore will handle that later.
| IT: Adapter Laravel per il resize. Risolve $src in un tmp LOCALE se serve
|     e delega il resize a PUNK_ResizePhp scrivendo su $dest LOCALE.
|     Nessun upload/salvataggio finale qui – ci penserà il SaveCore.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ResizeInterface;

final class PUNK_ResizeLaravel implements PUNK_ResizeInterface
{
    public function __construct(private ?string $disk = null) {}

    public function punk_resize_to(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        $cleanup = false;
        $localSrc = $src;

        // 1) Se $src non è un file locale, prova a risolvere via Storage (se disponibile)
        if (!is_file($localSrc) && class_exists('\Illuminate\Support\Facades\Storage')) {
            $disk = $this->disk ?? (\function_exists('config') ? (string)config('filesystems.default') : 'public');
            $fs   = \Illuminate\Support\Facades\Storage::disk($disk);

            // Esiste sul disco?
            try {
                if ($fs->exists($src)) {
                    // Se il disco espone path reale (locale)
                    if (method_exists($fs, 'path')) {
                        try {
                            $maybePath = $fs->path($src);
                            if (is_string($maybePath) && is_file($maybePath)) {
                                $localSrc = $maybePath;
                            }
                        } catch (\Throwable $e) {
                            // ignora, tentiamo stream
                        }
                    }

                    // Se non è ancora locale, stream su tmp
                    if (!is_file($localSrc)) {
                        $tmp = $this->makeTempFile('nff_src_');
                        $cleanup = true;

                        if (method_exists($fs, 'readStream')) {
                            $in = $fs->readStream($src);
                            if (!$in) return false;
                            $out = fopen($tmp, 'wb');
                            if (!$out) {
                                if (is_resource($in)) fclose($in);
                                return false;
                            }
                            stream_copy_to_stream($in, $out);
                            fclose($out);
                            if (is_resource($in)) fclose($in);
                        } else {
                            // fallback: carica in memoria
                            $data = $fs->get($src);
                            file_put_contents($tmp, $data);
                        }

                        $localSrc = $tmp;
                    }
                }
            } catch (\Throwable $e) {
                // se Storage fallisce, si proverà comunque con il path originale
            }
        }

        // 2) Assicura che la dir di destinazione esista (locale)
        $dir = dirname($dest);
        if (!@is_dir($dir) && !@mkdir($dir, 0775, true)) {
            if ($cleanup && is_file($localSrc)) @unlink($localSrc);
            return false;
        }

        // 3) Delega il lavoro al resizer PHP (lavora su file locali)
        $phpResizer = new PUNK_ResizePhp();
        $result = $phpResizer->punk_resize_to($localSrc, $dest, max(1,$w), max(1,$h), max(1,min(100,$quality)));

        // 4) Cleanup eventuale del tmp sorgente scaricato
        if ($cleanup && is_file($localSrc)) {
            @unlink($localSrc);
        }

        return $result;
    }

    protected function makeTempFile(string $prefix = 'nff_'): string
    {
        $f = tempnam(sys_get_temp_dir(), $prefix);
        if ($f === false) {
            throw new \RuntimeException('Unable to create temporary file.');
        }
        return $f;
    }
}
