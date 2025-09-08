<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Core/class-punk-upload-utils.php
| DESCRIPTION:
| EN: "Upload only" façade implementing PUNK_UploadInterface. It delegates
|     validation and ingestion to PUNK_UploadCore and returns standardized
|     results ready to be handed off to resize and/or save modules.
|     IMPORTANT: No final storage happens here; path and url are null.
| IT: Facciata "solo upload" che implementa PUNK_UploadInterface. Delega
|     validazione e ingest a PUNK_UploadCore e restituisce risultati
|     standardizzati, pronti per passare ai moduli di resize e/o save.
|     IMPORTANTE: Qui non avviene alcun salvataggio finale; path e url sono null.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Core;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_UploadInterface;

/**
 * CLASS: PUNK_UploadUtils (Upload-only façade)
 * EN:
 * - Implements the public upload contract without performing final storage.
 * - Calls PUNK_UploadCore to build PUNK_FileAsset(s) from $_FILES-like input.
 * - Maps results to the public shape expected by PUNK_UploadInterface.
 * - Provides rich metadata for the next stages (resize → save or save directly).
 *
 * IT:
 * - Implementa il contratto pubblico di upload senza effettuare salvataggio finale.
 * - Invoca PUNK_UploadCore per ottenere PUNK_FileAsset da input stile $_FILES.
 * - Mappa i risultati nel formato dell'interfaccia pubblica.
 * - Fornisce metadati completi utili agli step successivi (resize → save o direttamente save).
 */
final class PUNK_UploadUtils implements PUNK_UploadInterface
{
    /**
     * EN: Default options used by this façade, merged with call-time $opts.
     * IT: Opzioni di default usate dalla facciata, unite con le $opts della chiamata.
     *
     * Supported keys (forwarded to Core, documented here for clarity):
     *  - allowed_mimes       (array)  Allowlist of real MIME types (finfo).
     *  - max_mb              (int)    Max file size in MB.
     *  - allow_untrusted_tmp (bool)   Allow non is_uploaded_file (CLI/tests).
     *  - randomize_name      (bool)   Propose a randomized safe_name.
     *
     * Upload-only specific keys (not used by Core directly):
     *  - dest_scheme         (string|\Closure) Relative layout hint for later save, e.g. 'date'|'flat'|callable.
     */
    private array $defaults = [
        'allowed_mimes'       => [
            'image/jpeg','image/png','image/webp',
            'application/pdf',
            'application/xml','text/xml',
            'text/plain','application/zip',
        ],
        'max_mb'              => 20,
        'allow_untrusted_tmp' => false,
        'randomize_name'      => false,

        // EN: Hint for later save; 'date' => Y/m/d, 'flat' => '', or a custom callable
        // IT: Suggerimento per il successivo salvataggio; 'date' => Y/m/d, 'flat' => '', o callable personalizzato
        'dest_scheme'         => 'date',
    ];

    public function __construct(
        private readonly PUNK_UploadCore $core,
        array $defaults = []
    ) {
        // EN: Allow project-level overrides for sensible defaults.
        // IT: Consenti override a livello progetto per i default.
        $this->defaults = \array_replace($this->defaults, $defaults);
    }

    /**
     * METHOD: punk_upload_files()
     * EN:
     * - Delegates validation/ingest to PUNK_UploadCore.
     * - Returns standardized results with path/url = null (no storage here).
     * - Packs handoff metadata (tmp_path, safe_name, mime, bytes, suggested_relative, dest_scheme).
     *
     * IT:
     * - Delega validazione/ingest a PUNK_UploadCore.
     * - Ritorna risultati standard con path/url = null (qui nessuno storage).
     * - Inserisce metadati per il passaggio (tmp_path, safe_name, mime, bytes, suggested_relative, dest_scheme).
     *
     * @param array $files $_FILES-like or normalized flat list
     *                     Array stile $_FILES o lista piatta normalizzata
     * @param array $opts  Options merged with defaults (see class doc)
     *                     Opzioni unite ai default (vedi doc della classe)
     * @return array<int,array{
     *   ok:bool,
     *   path:?string,
     *   url:?string,
     *   error:?string,
     *   meta:array{
     *     original_name:string,
     *     safe_name:?string,
     *     mime:?string,
     *     bytes:int,
     *     tmp_path:string,
     *     suggested_relative:string,
     *     dest_scheme:string
     *   }
     * }>
     */
    public function punk_upload_files(array $files, array $opts = []): array
    {
        // 1) Merge options (call-time opts override class defaults)
        // 1) Unisci opzioni (quelle della chiamata sovrascrivono i default della classe)
        $o = \array_replace($this->defaults, $opts);

        // 2) Run Core ingest/validation
        // 2) Esegui ingest/validazione del Core
        $coreResults = $this->core->punk_upload_files($files, $o);

        // 3) Map to public contract (no storage → path/url remain null)
        // 3) Mappa al contratto pubblico (niente storage → path/url restano null)
        $out = [];
        foreach ($coreResults as $r) {
            if (!$r['ok'] ?? false) {
                $out[] = [
                    'ok'    => false,
                    'path'  => null,
                    'url'   => null,
                    'error' => $r['error'] ?? 'Upload failed',
                    'meta'  => [
                        'original_name'     => '',
                        'safe_name'         => null,
                        'mime'              => null,
                        'bytes'             => 0,
                        'tmp_path'          => '',
                        'suggested_relative'=> '',
                        'dest_scheme'       => (string)($o['dest_scheme'] ?? 'date'),
                    ],
                ];
                continue;
            }

            /** @var PUNK_FileAsset $asset */
            $asset = $r['asset'];

            // EN: Build a suggested relative path for later save (e.g. "Y/m/d/safe.ext")
            // IT: Costruisci un percorso relativo suggerito per il successivo salvataggio (es. "Y/m/d/safe.ext")
            $relDir   = punk_build_rel_dir($o['dest_scheme'] ?? 'date');
            $filename = $asset->safe_name ?: 'file.bin';
            $suggest  = $relDir ? ($relDir . '/' . $filename) : $filename;

            $out[] = [
                'ok'    => true,
                'path'  => null, // EN: not saved here / IT: qui non salviamo
                'url'   => null, // EN: not saved here / IT: qui non salviamo
                'error' => null,
                'meta'  => [
                    'original_name'      => $asset->original_name,
                    'safe_name'          => $asset->safe_name,
                    'mime'               => $asset->mime,
                    'bytes'              => $asset->bytes,
                    // EN: Handoff-critical data for resize/save
                    // IT: Dati essenziali per il passaggio a resize/save
                    'tmp_path'           => $asset->tmp_path,
                    'suggested_relative' => $suggest,
                    'dest_scheme'        => (string)($o['dest_scheme'] ?? 'date'),
                ],
            ];
        }

        return $out;
    }
}
