<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-image-laravel.php
| DESCRIPTION:
| EN: Laravel adapter that supports both local disks (via Storage::path())
|     and remote disks like S3 by downloading to temp files, delegating
|     the resize to the pure PHP adapter (PUNK_Image_Php), then uploading.
| IT: Adattatore Laravel che supporta sia dischi locali (via Storage::path())
|     sia dischi remoti tipo S3, scaricando su file temporanei, delegando
|     il resize all'adattatore PHP (PUNK_Image_Php) e poi caricando il risultato.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_ImageServiceInterface;
use Illuminate\Support\Facades\Storage;

class PUNK_Image_Laravel implements PUNK_ImageServiceInterface
{
    /******************************************************************
     * EN: Target disk name (e.g., 'local', 'public', 's3'). If null,
     *     it uses Laravel's default filesystem disk.
     * IT: Nome del disco di destinazione (es. 'local', 'public', 's3').
     *     Se null, usa il disco predefinito di Laravel.
     ******************************************************************/
    protected ?string $disk;

    public function __construct(?string $disk = null)
    {
        $this->disk = $disk;
    }

    /******************************************************************
     * METHOD: punk_resizeTo()
     * EN:
     * - If the disk is local (supports Storage::path), resolve real
     *   filesystem paths and delegate directly to PUNK_Image_Php.
     * - Otherwise (e.g., S3), download $src to a local temp file,
     *   create a local temp for $dest, delegate the resize to PHP,
     *   then upload the result to $dest on the chosen disk.
     *
     * IT:
     * - Se il disco è locale (supporta Storage::path), risolve path reali
     *   e delega direttamente a PUNK_Image_Php.
     * - Altrimenti (es. S3), scarica $src in un file temp, crea un temp
     *   per $dest, delega il resize al PHP, poi carica il risultato su $dest
     *   nel disco scelto.
     ******************************************************************/
    public function punk_resizeTo(string $src, string $dest, int $w, int $h, int $quality = 90): array|false
    {
        // Se la facade Storage non esiste (contesto non-Laravel), fallback PHP.
        if (!class_exists(\Illuminate\Support\Facades\Storage::class)) {
            return (new PUNK_Image_Php())->punk_resizeTo($src, $dest, $w, $h, $quality);
        }

        $disk = $this->disk ?? config('filesystems.default');
        $fs   = Storage::disk($disk);

        // Proviamo se il disco fornisce un "vero" path locale
        if ($this->supportsPath($fs)) {
            try {
                $srcPath  = $fs->path($src);
                // Assicura che la directory di destinazione esista sul filesystem locale
                $destPath = $fs->path($dest);
                $this->ensureLocalDirectory(dirname($destPath));

                return (new PUNK_Image_Php())->punk_resizeTo($srcPath, $destPath, $w, $h, $quality);
            } catch (\Throwable $e) {
                // Se qualcosa va storto con path() (disco non locale o errore inatteso), cade sul flusso "remoto".
            }
        }

        // Flusso dischi remoti (S3, FTP, ecc.)
        $tmpSrc  = $this->makeTempFile('nff_src_');
        $tmpDest = $this->makeTempFile('nff_dst_');

        try {
            // 1) Download sorgente in $tmpSrc (stream se disponibile, altrimenti get)
            if (method_exists($fs, 'readStream')) {
                $read = $fs->readStream($src);
                if (!$read) {
                    throw new \RuntimeException("Unable to read source stream: {$src}");
                }
                $out = fopen($tmpSrc, 'wb');
                if (!$out) {
                    throw new \RuntimeException("Unable to open temp file for writing: {$tmpSrc}");
                }
                stream_copy_to_stream($read, $out);
                fclose($out);
                if (is_resource($read)) fclose($read);
            } else {
                // Fallback: carica in memoria (attenzione ai file grandi)
                $contents = $fs->get($src);
                file_put_contents($tmpSrc, $contents);
            }

            // 2) Delego il resize all'adattatore PHP (lavora su file locali)
            $result = (new PUNK_Image_Php())->punk_resizeTo($tmpSrc, $tmpDest, $w, $h, $quality);
            if ($result === false) {
                throw new \RuntimeException('Resize failed in PUNK_Image_Php.');
            }

            // 3) Upload del risultato su $dest (stream se possibile)
            $this->ensureRemoteDirectory($fs, $dest);

            if (method_exists($fs, 'writeStream')) {
                $in = fopen($tmpDest, 'rb');
                if (!$in) {
                    throw new \RuntimeException("Unable to open resized temp file for reading: {$tmpDest}");
                }

                // Nota: con Flysystem v3, writeStream richiede il percorso e lo stream.
                // Puoi anche passare opzioni aggiuntive (visibilità, mime, ecc.) come terzo parametro.
                $ok = $fs->writeStream($dest, $in);
                if (is_resource($in)) fclose($in);

                if (!$ok) {
                    throw new \RuntimeException("Unable to write destination stream: {$dest}");
                }
            } else {
                // Fallback: put con contenuto in memoria
                $data = file_get_contents($tmpDest);
                $ok   = $fs->put($dest, $data);
                if (!$ok) {
                    throw new \RuntimeException("Unable to put destination: {$dest}");
                }
            }

            return $result;
        } catch (\Throwable $e) {
            // qui potresti loggare: punk_log((string)$e, 'error');
            return false;
        } finally {
            // 4) Ripulisci sempre i temporanei
            $this->safeUnlink($tmpSrc);
            $this->safeUnlink($tmpDest);
        }
    }

    /******************************************************************
     * EN: Check if the filesystem adapter supports ->path() (local disks).
     * IT: Verifica se l'adapter del filesystem supporta ->path() (dischi locali).
     ******************************************************************/
    protected function supportsPath($fs): bool
    {
        return method_exists($fs, 'path');
    }

    /******************************************************************
     * EN: Ensure a local directory exists (mkdir -p).
     * IT: Assicura l'esistenza di una directory locale (mkdir -p).
     ******************************************************************/
    protected function ensureLocalDirectory(string $dir): void
    {
        if ($dir && !is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
    }

    /******************************************************************
     * EN: Ensure a "remote" directory exists, if the adapter supports it.
     *     Many disks (S3) don't need explicit directories, but for others
     *     (like local) this is useful. We try catch to be safe.
     * IT: Assicura esistenza della "directory" remota se supportata.
     *     Molti dischi (S3) non necessitano directory esplicite, ma per
     *     altri (local) è utile. Usiamo try/catch per sicurezza.
     ******************************************************************/
    protected function ensureRemoteDirectory($filesystem, string $path): void
    {
        $dir = trim(dirname($path), '/\\');
        if ($dir === '.' || $dir === '') {
            return;
        }
        try {
            if (method_exists($filesystem, 'makeDirectory')) {
                $filesystem->makeDirectory($dir);
            }
        } catch (\Throwable $e) {
            // silenzio: su S3 di solito non serve creare directory
        }
    }

    /******************************************************************
     * EN: Create a unique temporary filename.
     * IT: Crea un nome file temporaneo univoco.
     ******************************************************************/
    protected function makeTempFile(string $prefix = 'nff_'): string
    {
        $file = tempnam(sys_get_temp_dir(), $prefix);
        if ($file === false) {
            throw new \RuntimeException('Unable to create temporary file.');
        }
        return $file;
    }

    /******************************************************************
     * EN: Safe file deletion (ignore errors).
     * IT: Eliminazione file sicura (ignora eventuali errori).
     ******************************************************************/
    protected function safeUnlink(?string $file): void
    {
        if ($file && is_file($file)) {
            @unlink($file);
        }
    }
}

