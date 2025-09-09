<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Contracts/punk-upload-interface.php
| DESCRIPTION:
| EN: Public upload contract: validate files and delegate final storage to a
|     PUNK_StorageInterface. No resizing here.
| IT: Contratto pubblico di upload: valida i file e delega lo storage finale a
|     PUNK_StorageInterface. Niente resize qui.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Contracts;

/**********************************************************************
 * INTERFACE: PUNK_UploadInterface
 * EN: Public API for uploading (single/multi). Returns one result per file.
 * IT: API pubblica per l'upload (singolo/multiplo). Ritorna un risultato per file.
 **********************************************************************/
interface PUNK_Upload
{
    /**
     * @param array $files $_FILES-like or normalized flat list
     *                     Array stile $_FILES o lista piatta normalizzata
     * @param array $opts  Options (allowed_mimes, max_mb, dest_scheme, randomize_name, allow_untrusted_tmp)
     *                     Opzioni (allowed_mimes, max_mb, dest_scheme, randomize_name, allow_untrusted_tmp)
     * @return array       Each item:
     *                     [
     *                       'ok'   => bool,
     *                       'path' => string|null,
     *                       'url'  => string|null,
     *                       'error'=> string|null,
     *                       'meta' => [
     *                         'original_name' => string,
     *                         'safe_name'     => string|null,
     *                         'mime'          => string|null,
     *                         'bytes'         => int
     *                       ]
     *                     ]
     */
    public function punk_upload_files(array $files, array $opts = []): array;
}
