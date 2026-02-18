<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Core/class-punk-upload-core.php
| DESCRIPTION:
| EN: Upload/Ingest core: validates input files and produces PUNK_FileAsset(s).
|     No resize, no final save here. Works for PHP, WordPress, Laravel.
| IT: Core di Upload/Ingest: valida i file e produce PUNK_FileAsset.
|     Qui niente resize e niente salvataggio finale. Funziona in PHP, WP, Laravel.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Core;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use FilesystemIterator;

use Punkode\Anarkode\NoFutureFrame\Core\PUNK_FileAsset;
use function Punkode\Anarkode\NoFutureFrame\Core\punk_normalize_files;
use function Punkode\Anarkode\NoFutureFrame\Core\punk_real_mime;
use function Punkode\Anarkode\NoFutureFrame\Core\punk_safe_filename;

final class PUNK_UploadCore
{
    /**
     * METHOD: punk_upload_files()
     * EN:
     * - Validates uploaded files (from $_FILES-like input).
     * - Produces PUNK_FileAsset(s) pointing to temporary files.
     * - No storage nor resize happen here.
     *
     * IT:
     * - Valida i file ricevuti (array in stile $_FILES).
     * - Produce PUNK_FileAsset che puntano a file temporanei.
     * - Qui non avviene né salvataggio né ridimensionamento.
     *
     * @param array $files $_FILES-like or normalized list / Array stile $_FILES o lista normalizzata
     * @param array $opts  Options / Opzioni:
     *   - allowed_mimes       (array)  allowlist (finfo) of real MIME types
     *   - max_mb              (int)    max size in MB
     *   - allow_untrusted_tmp (bool)   allow non is_uploaded_file (CLI/tests)
     *   - randomize_name      (bool)   propose a randomized safe_name
     *
     * @return array<int,array{ok:bool,error:?string,asset:?PUNK_FileAsset}>
     */
    public function punk_upload_files(array $files, array $opts = []): array
    {
        $defs = [
            'allowed_mimes'       => [
                'image/jpeg',
                'image/png',
                'image/webp',
                'application/pdf',
                'application/xml',
                'text/xml',
                'text/plain',
                'application/zip',
            ],
            'max_mb'              => 20,
            'allow_untrusted_tmp' => false, // set true for CLI/tests
            'randomize_name'      => false, // propose randomized safe_name
        ];
        $o = \array_replace($defs, $opts);

        $list = punk_normalize_files($files);
        $out  = [];

        foreach ($list as $f) {
            $result = [
                'ok'    => false,
                'error' => null,
                'asset' => null,
            ];

            try {
                // 1) Basic PHP upload checks
                $err = (int)($f['error'] ?? \UPLOAD_ERR_NO_FILE);
                if ($err !== \UPLOAD_ERR_OK) {
                    throw new \RuntimeException('Upload error code ' . $err);
                }

                $tmp = (string)($f['tmp_name'] ?? '');
                if ($tmp === '' || !\is_file($tmp)) {
                    throw new \RuntimeException('Missing temp file');
                }

                // In produzione, pretendiamo un vero uploaded file.
                if (!$o['allow_untrusted_tmp'] && !\is_uploaded_file($tmp)) {
                    throw new \RuntimeException('Not an uploaded file');
                }

                // 2) Real MIME + size
                $mime  = punk_real_mime($tmp) ?: ($f['type'] ?? null);
                if (!$mime || !\in_array($mime, $o['allowed_mimes'], true)) {
                    throw new \RuntimeException('Forbidden MIME: ' . (string)$mime);
                }

                $bytes = (int)($f['size'] ?? 0);
                $max   = (int)($o['max_mb'] * 1024 * 1024);
                if ($bytes > $max) {
                    throw new \RuntimeException('File too large');
                }

                // 3) Safe name proposal
                $safe = punk_safe_filename($f['name'] ?? 'file');

                // Hardening: block dangerous extensions even if MIME looks ok
                if (\preg_match('/\.(php\d?|phtml|phar|sh|bat|cmd|exe|js)(\.|$)/i', $safe)) {
                    throw new \RuntimeException('Suspicious filename');
                }

                if (!empty($o['randomize_name'])) {
                    $ext  = \pathinfo($safe, \PATHINFO_EXTENSION);
                    $safe = \bin2hex(\random_bytes(8)) . ($ext ? '.' . \strtolower($ext) : '');
                }

                // 4) Build asset (immutable VO)
                $asset = new PUNK_FileAsset(
                    tmp_path: $tmp,
                    original_name: (string)($f['name'] ?? ''),
                    safe_name: $safe,
                    mime: $mime,
                    bytes: $bytes,
                    meta: []
                );

                $result['ok']    = true;
                $result['asset'] = $asset;
            } catch (\Throwable $e) {
                $result['error'] = $e->getMessage();
            }

            $out[] = $result;
        }

        return $out;
    }

    /**
     * METHOD: punk_files_from_path()
     * EN: Scan a source folder and build a $_FILES-like normalized list. For each
     *     source file it creates a temp via hardlink (fast) or copy (fallback).
     * IT: Scansiona una cartella sorgente e costruisce una lista stile $_FILES.
     *     Per ogni file sorgente crea un temporaneo via hardlink (veloce) o copia.
     *
     * @param string $source_dir
     * @param array{
     *   allowed_ext?: string[],
     *   follow_symlinks?: bool,
     *   max_files?: int,
     *   tmp_prefix?: string,
     *   prefer_hardlink?: bool,
     *   detect_mime?: bool,
     *   skip_hidden?: bool
     * } $opts
     * @return array<int,array{name:string,tmp_name:string,size:int,type:?string,error:int}>
     */
    public function punk_files_from_path(string $source_dir, array $opts = []): array
    {
        $o = \array_replace([
            'allowed_ext'     => ['jpg', 'jpeg', 'png', 'webp', 'gif', 'tif', 'tiff', 'pdf', 'zip', 'xml', 'txt', 'csv', 'mp4', 'mov'],
            'follow_symlinks' => false,
            'max_files'       => 0, // 0 = no limit
            'tmp_prefix'      => 'diventi_',
            'prefer_hardlink' => true,
            'detect_mime'     => true,
            'skip_hidden'     => true,
        ], $opts);

        $dir = \realpath($source_dir) ?: $source_dir;
        if (!\is_dir($dir)) {
            throw new \InvalidArgumentException("Source dir not found: {$source_dir}");
        }

        $flags = FilesystemIterator::SKIP_DOTS
            | FilesystemIterator::CURRENT_AS_FILEINFO
            | FilesystemIterator::KEY_AS_PATHNAME;

        if ($o['follow_symlinks']) {
            $flags |= FilesystemIterator::FOLLOW_SYMLINKS;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, $flags),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $out   = [];
        $count = 0;

        foreach ($it as $path => $info) {
            if (!$info->isFile()) continue;

            $basename = $info->getBasename();

            // EN: Skip hidden files/folders if requested
            // IT: Salta file/cartelle nascosti se richiesto
            if ($o['skip_hidden'] && ($basename[0] === '.' || \strpos($path, DIRECTORY_SEPARATOR . '.') !== false)) {
                continue;
            }

            // EN: Extension allowlist
            // IT: Filtro per estensione
            $ext = \strtolower(\pathinfo($basename, \PATHINFO_EXTENSION));
            if (!\in_array($ext, $o['allowed_ext'], true)) {
                continue;
            }

            // EN: Create temp via hardlink/copy
            // IT: Crea il temporaneo via hardlink/copy
            $tmp = $this->punk_make_tmp_for($path, $o['tmp_prefix'], $o['prefer_hardlink']);

            // EN: Best-effort MIME (UploadCore re-checks anyway)
            // IT: MIME indicativo (UploadCore ricontrolla comunque)
            // EN: Best-effort MIME (UploadCore re-checks anyway).
            // IT: MIME best-effort (UploadCore ricontrolla comunque).
            $mime = null;
            if (!empty($o['detect_mime'])) {
                try {
                    $mime = punk_real_mime($tmp) ?: null;
                } catch (\Throwable) {
                    $mime = null;
                }
            }
            // Fallback by extension if real MIME is unavailable
            if ($mime === null || $mime === '') {
                $mime = self::punk_guess_mime_by_ext($basename);
            }


            $out[] = [
                'name'     => $basename,
                'tmp_name' => $tmp,
                'size'     => $info->getSize(),
                'type'     => $mime,
                'error'    => \UPLOAD_ERR_OK, // mimic successful upload
                '__src_path'=> $path,  
            ];

            $count++;
            if ($o['max_files'] > 0 && $count >= $o['max_files']) break;
        }

        return $out;
    }

    /**
     * METHOD: punk_make_tmp_for()
     * EN: Create a temp file for a given source path using hardlink when possible,
     *     otherwise copy(). Returns the temp file path.
     * IT: Crea un file temporaneo per un path sorgente usando hardlink quando
     *     possibile, altrimenti copy(). Ritorna il path del temporaneo.
     */
    public function punk_make_tmp_for(string $src_path, string $prefix = 'diventi_', bool $prefer_hardlink = true): string
    {
        if (!\is_file($src_path) || !\is_readable($src_path)) {
            throw new \RuntimeException("Unreadable file: {$src_path}");
        }

        $tmp_dir = \sys_get_temp_dir();
        $ext     = \pathinfo($src_path, \PATHINFO_EXTENSION);
        $ext     = $ext ? '.' . \strtolower($ext) : '';

        // EN: Unique temp name
        // IT: Nome temporaneo univoco
        $tmp = $tmp_dir . DIRECTORY_SEPARATOR . $prefix . \bin2hex(\random_bytes(8)) . $ext;

        // EN: Try hardlink first (fast, zero-copy)
        // IT: Prova l’hardlink per primo (veloce, zero-copy)
        if ($prefer_hardlink) {
            if (@\link($src_path, $tmp)) {
                return $tmp;
            }
        }

        // EN: Fallback to copy()
        // IT: Ripiega su copy()
        if (!@\copy($src_path, $tmp)) {
            throw new \RuntimeException("Failed to create temp for: {$src_path}");
        }

        return $tmp;
    }

    /**
 * EN: Fallback MIME guesser by file extension.
 * IT: Fallback per il MIME basato sull'estensione.
 */
private static function punk_guess_mime_by_ext(string $name): ?string
{
    $ext = \strtolower(\pathinfo($name, \PATHINFO_EXTENSION));
    $map = [
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
        'webp' => 'image/webp',
        'gif'  => 'image/gif',
        'tif'  => 'image/tiff',
        'tiff' => 'image/tiff',
        'pdf'  => 'application/pdf',
        'zip'  => 'application/zip',
        'xml'  => 'application/xml',
        'txt'  => 'text/plain',
        'csv'  => 'text/csv',
        'mp4'  => 'video/mp4',
        'mov'  => 'video/quicktime',
    ];
    return $map[$ext] ?? null;
}

}
