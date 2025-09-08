<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-image-wp-io.php
| DESCRIPTION:
| EN: WP multi-source input helper refit for the new pipeline.
|     It ingests different input kinds (attachment ID, URL, local path, uploaded tmp)
|     and produces PUNK_FileAsset(s) without performing any final save.
| IT: Helper WP per input multi-sorgente adattato alla nuova pipeline.
|     Ingerisce diversi tipi di input (ID allegato, URL, path locale, tmp upload)
|     e produce PUNK_FileAsset senza effettuare salvataggi finali.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\wp;

use Punkode\Anarkode\NoFutureFrame\Core\PUNK_FileAsset;
use function Punkode\Anarkode\NoFutureFrame\Core\punk_real_mime;
use function Punkode\Anarkode\NoFutureFrame\Core\punk_safe_filename;

class PUNK_ResizeWpIo
{
    /**
     * METHOD: punk_analyze_input()
     * EN: Detects the input shape and type.
     * IT: Rileva forma e tipo dell'input.
     *
     * @param mixed $input
     * @return array{is_array:bool,type:string}
     */
    public function punk_analyze_input($input): array
    {
        $is_array = \is_array($input);
        $item = $is_array ? \reset($input) : $input;

        $type = 'unknown';
        if (\is_int($item) || \ctype_digit((string)$item)) {
            $type = 'wp_attachment_id';
        } elseif (\is_string($item) && \filter_var($item, \FILTER_VALIDATE_URL)) {
            $type = 'url';
        } elseif (\is_string($item) && \file_exists($item)) {
            $type = 'local_path';
        } elseif (\is_string($item) && \function_exists('is_uploaded_file') && @\is_uploaded_file($item)) {
            $type = 'uploaded_temp_file';
        }

        return ['is_array' => $is_array, 'type' => $type];
    }

    /**
     * METHOD: punk_resolve_path()
     * EN: Resolves input item to a local path and whether it needs cleanup.
     * IT: Risolve l'item in un path locale e se richiede cleanup.
     *
     * @param mixed  $item
     * @param string $type
     * @return array{path:string, cleanup:bool}|false
     */
    public function punk_resolve_path($item, string $type): array|false
    {
        switch ($type) {
            case 'url':
                if (!\function_exists('download_url')) return false;
                $tmp = \download_url($item);
                if (\is_wp_error($tmp)) return false;
                // EN: downloaded files should be cleaned up later
                // IT: i file scaricati vanno ripuliti dopo
                return ['path' => $tmp, 'cleanup' => true];

            case 'local_path':
                return \file_exists($item) ? ['path' => $item, 'cleanup' => false] : false;

            case 'wp_attachment_id':
                if (!\function_exists('get_attached_file')) return false;
                $p = \get_attached_file((int)$item);
                return ($p && \file_exists($p)) ? ['path' => $p, 'cleanup' => false] : false;

            case 'uploaded_temp_file':
                if (\function_exists('is_uploaded_file') && @\is_uploaded_file($item)) {
                    return ['path' => $item, 'cleanup' => false];
                }
                return false;
        }
        return false;
    }

    /**
     * METHOD: punk_ingest_to_assets()
     * EN: Ingest any supported input into PUNK_FileAsset list (no save).
     * IT: Ingerisce input supportati e produce una lista di PUNK_FileAsset (nessun salvataggio).
     *
     * @param mixed $image_input  EN: single item or list (ID/URL/path/tmp) | IT: singolo o lista (ID/URL/path/tmp)
     * @return array<int,PUNK_FileAsset>
     */
    public function punk_ingest_to_assets($image_input): array
    {
        $assets = [];
        $a = $this->punk_analyze_input($image_input);
        $items = $a['is_array'] ? $image_input : [$image_input];
        $type  = $a['type'];

        foreach ($items as $it) {
            $resolved = $this->punk_resolve_path($it, $type);
            if ($resolved === false) {
                continue;
            }
            $path    = $resolved['path'];
            $cleanup = $resolved['cleanup'];

            // Real MIME + bytes
            $mime  = punk_real_mime($path);
            $bytes = \is_file($path) ? (\filesize($path) ?: 0) : 0;

            // Derive a reasonable original name
            $original = $this->punk_guess_original_name($it, $type, $path);

            // Safe name proposal
            $safe = punk_safe_filename($original ?: 'file');

            // Build asset (tmp path is the local file we just resolved/downloaded)
            $assets[] = new PUNK_FileAsset(
                tmp_path:      $path,
                original_name: (string)$original,
                safe_name:     $safe,
                mime:          $mime,
                bytes:         $bytes,
                meta:          [
                    'source' => [
                        'type'    => $type,
                        'item'    => $it,
                        'cleanup' => $cleanup, // EN: caller can decide whether to unlink | IT: chi usa può decidere se rimuovere
                    ],
                ]
            );
        }

        return $assets;
    }

    /**
     * METHOD: punk_resize_many()
     * EN: (Optional) Convenience: ingest → resize via provided callable, return resized assets.
     *     The callable should accept a PUNK_FileAsset and return a NEW PUNK_FileAsset (no save).
     * IT: (Opzionale) Comodità: ingest → resize con callable, ritorna asset ridimensionati.
     *     La callable deve accettare un PUNK_FileAsset e restituire un NUOVO PUNK_FileAsset (niente salvataggio).
     *
     * @param mixed $image_input
     * @param callable $resizer  function(PUNK_FileAsset $asset): PUNK_FileAsset|false
     * @return array<int,PUNK_FileAsset>
     */
    public function punk_resize_many($image_input, callable $resizer): array
    {
        $out = [];
        $assets = $this->punk_ingest_to_assets($image_input);
        foreach ($assets as $asset) {
            $r = $resizer($asset);
            if ($r instanceof PUNK_FileAsset) {
                $out[] = $r;
            }
        }
        return $out;
    }

    /**
     * INTERNAL: best-effort original name inference.
     * IT: Stima del nome originale nel modo più sensato possibile.
     */
    protected function punk_guess_original_name($item, string $type, string $path): string
    {
        switch ($type) {
            case 'wp_attachment_id':
                // try to use the actual filename from attached file
                $fn = \basename($path);
                if (\is_string($fn) && $fn !== '') return $fn;
                return 'attachment-' . (string)$item;

            case 'url':
                $url_path = \parse_url((string)$item, \PHP_URL_PATH);
                $fn = $url_path ? \basename($url_path) : '';
                if ($fn !== '') return $fn;
                // fallback to local tmp name
                return \basename($path) ?: 'remote-file';

            case 'local_path':
                return \basename($path) ?: 'local-file';

            case 'uploaded_temp_file':
                // tmp names are ugly; fallback to tmp basename
                return \basename($path) ?: 'upload-tmp';
        }
        return \basename($path) ?: 'file';
    }
}
