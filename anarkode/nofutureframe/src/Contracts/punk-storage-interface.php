<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Contracts/punk-storage-interface.php
| DESCRIPTION:
| EN: Storage contract: move a temp file to its final relative destination,
|     return absolute path and optional public URL.
| IT: Contratto di storage: sposta un file temporaneo nella destinazione relativa
|     finale, restituisce path assoluto ed eventuale URL pubblico.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Contracts;

/**********************************************************************
 * INTERFACE: PUNK_StorageInterface
 * EN: Abstraction for storage backends (PHP FS, WordPress, Laravel).
 * IT: Astrazione per backend di storage (FS PHP, WordPress, Laravel).
 **********************************************************************/
interface PUNK_StorageInterface
{
    /**
     * EN: Persist a temp file under the given relative destination (e.g. "Y/m/d/name.ext").
     *     Ensure directories exist, handle filename collisions, and move securely.
     * IT: Salva un file temporaneo sotto la destinazione relativa (es. "Y/m/d/name.ext").
     *     Crea le directory, gestisce collisioni e sposta in modo sicuro.
     *
     * @param string $tmp_path      Absolute path to the temp file / Percorso assoluto al file temporaneo
     * @param string $relative_dest Relative destination path / Percorso relativo di destinazione
     * @return array{path:string, url:?string}
     */
    public function put_file(string $tmp_path, string $relative_dest): array;
}
