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
                'image/jpeg','image/png','image/webp',
                'application/pdf',
                'application/xml','text/xml',
                'text/plain','application/zip',
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
                    tmp_path:      $tmp,
                    original_name: (string)($f['name'] ?? ''),
                    safe_name:     $safe,
                    mime:          $mime,
                    bytes:         $bytes,
                    meta:          []
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
}
