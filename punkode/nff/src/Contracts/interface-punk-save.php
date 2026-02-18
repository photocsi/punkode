<?php
/*
|--------------------------------------------------------------------------
| FILE: punk-save-interface.php
| FROM → anarkode/nofutureframe/src/Contracts
| TO   → Implementations:
|        - anarkode/nofutureframe/src/Environments/wp/class-punk-save-wp.php
|        - anarkode/nofutureframe/src/Environments/PhpLaravel/class-punk-save-php.php
|        - anarkode/nofutureframe/src/Environments/PhpLaravel/class-punk-save-laravel.php
| DESCRIPTION:
| EN: Contract for the "Save" step. It takes a *local temporary file*
|     (produced by Resize or any ingest step) and persists it to the final
|     storage (local FS, WordPress uploads, Laravel Storage/S3). It returns
|     a normalized array with path/key, size, mime, and (if available) URL.
|
| IT: Contratto per lo step "Save". Riceve un *file temporaneo locale*
|     (prodotto da Resize o da uno step di ingest) e lo salva nello storage
|     finale (FS locale, upload di WordPress, Laravel Storage/S3). Restituisce
|     un array normalizzato con path/key, size, mime e, se disponibile, URL.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Contracts;

interface PUNK_Save
{
    /**
     * EN:
     * Persist a local temporary file to the target storage.
     *
     * @param string $temp_path  Absolute path to the local temporary file (must exist).
     * @param string $dest       Destination hint (final absolute path or storage key).
     *                           Examples:
     *                             - PHP:     '/var/www/site/media/img/out.jpg'
     *                             - WP:      'my-folder/out.jpg'        (relative to uploads)
     *                             - Laravel: 'images/out.jpg'           (relative to disk root)
     * @param array  $options    Extra options:
     *                           [
     *                             'overwrite'   => bool,                 // default false
     *                             'visibility'  => 'public'|'private',   // Laravel/S3
     *                             'mime'        => 'image/jpeg',         // optional override
     *                             'delete_temp' => bool,                 // default true (unlink)
     *                             'metadata'    => array,                // WP attachment extras
     *                           ]
     *
     * @return array|false       Normalized result on success, false on failure.
     *                           On success:
     *                           [
     *                             'path'   => string,  // absolute path or storage key
     *                             'size'   => int,     // bytes
     *                             'mime'   => string,  // detected or overridden
     *                             'url'    => string|null, // public URL if available
     *                             'extra'  => array,   // implementation-specific (id, disk, etc.)
     *                           ]
     *
     * IT:
     * Salva un file temporaneo locale nello storage di destinazione.
     *
     * @param string $temp_path  Percorso assoluto del file temporaneo (deve esistere).
     * @param string $dest       Suggerimento destinazione (percorso assoluto o storage key).
     *                           Esempi:
     *                             - PHP:     '/var/www/site/media/img/out.jpg'
     *                             - WP:      'my-folder/out.jpg'        (relativo a uploads)
     *                             - Laravel: 'images/out.jpg'           (relativo alla root del disk)
     * @param array  $options    Opzioni extra:
     *                           [
     *                             'overwrite'   => bool,                 // default false
     *                             'visibility'  => 'public'|'private',   // Laravel/S3
     *                             'mime'        => 'image/jpeg',         // override opzionale
     *                             'delete_temp' => bool,                 // default true (unlink)
     *                             'metadata'    => array,                // dati extra WP attachment
     *                           ]
     *
     * @return array|false       Risultato normalizzato in caso di successo, false altrimenti.
     *                           In caso di successo:
     *                           [
     *                             'path'   => string,       // percorso assoluto o storage key
     *                             'size'   => int,          // byte
     *                             'mime'   => string,       // rilevato o forzato
     *                             'url'    => string|null,  // URL pubblico se disponibile
     *                             'extra'  => array,        // specifico dell'implementazione (id, disk, ecc.)
     *                           ]
     */
    public function punk_save_from_temp(string $temp_path, string $dest, array $options = []);
}
