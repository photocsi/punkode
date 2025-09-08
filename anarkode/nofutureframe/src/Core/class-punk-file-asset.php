<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Core/class-punk-file-asset.php
| DESCRIPTION:
| EN: Value object representing a file handled in the NoFutureFrame pipeline.
|     It stores metadata like path, safe name, mime, size, and optional meta info.
| IT: Value object che rappresenta un file nella pipeline di NoFutureFrame.
|     Contiene metadati come path, safe name, mime, size e informazioni extra.
|--------------------------------------------------------------------------
*/

declare(strict_types=1);

namespace Punkode\Anarkode\NoFutureFrame\Core;

/**********************************************************************
 * CLASS: PUNK_FileAsset
 * EN: Immutable object that represents a file (tmp or processed).
 * IT: Oggetto immutabile che rappresenta un file (tmp o processato).
 **********************************************************************/
final class PUNK_FileAsset
{
    /**
     * @param string $tmp_path      EN: Absolute path of the file (usually tmp). 
     *                              IT: Percorso assoluto del file (di solito tmp).
     * @param string $original_name EN: Original filename from client. 
     *                              IT: Nome file originale dal client.
     * @param string|null $safe_name EN: Sanitized filename (ready for saving). 
     *                               IT: Nome file sanificato (pronto per salvataggio).
     * @param string|null $mime     EN: Real MIME type (from finfo). 
     *                              IT: MIME reale (da finfo).
     * @param int $bytes            EN: File size in bytes. 
     *                              IT: Dimensione file in byte.
     * @param array $meta           EN: Extra info (e.g. resize details). 
     *                              IT: Info extra (es. dettagli resize).
     */
    public function __construct(
        public readonly string $tmp_path,
        public readonly string $original_name,
        public readonly ?string $safe_name,
        public readonly ?string $mime,
        public readonly int $bytes,
        public readonly array $meta = []
    ) {}

    /******************************************************************
     * METHOD: with()
     * EN: Returns a new instance with updated fields (immutability helper).
     * IT: Ritorna una nuova istanza con campi aggiornati (immutabilità).
     *
     * Example / Esempio:
     *   $new = $asset->with(['tmp_path' => '/new/tmp/path']);
     ******************************************************************/
    public function with(array $changes): self
    {
        return new self(
            $changes['tmp_path']      ?? $this->tmp_path,
            $changes['original_name'] ?? $this->original_name,
            $changes['safe_name']     ?? $this->safe_name,
            $changes['mime']          ?? $this->mime,
            $changes['bytes']         ?? $this->bytes,
            $changes['meta']          ?? $this->meta
        );
    }
}
