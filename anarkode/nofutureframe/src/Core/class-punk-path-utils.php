<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-pathutils.php
| DESCRIPTION:
| EN: Path utilities for NoFutureFrame.
|     Provides helper methods for working with file paths and extensions.
| IT: Utility per i percorsi in NoFutureFrame.
|     Fornisce metodi di supporto per lavorare con percorsi ed estensioni.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Core;

/**********************************************************************
 * CLASS: PUNK_PathUtils
 * EN: Static helper class for path-related operations.
 * IT: Classe helper statica per operazioni sui percorsi.
 **********************************************************************/
class PUNK_PathUtils
{
    /******************************************************************
     * METHOD: punk_extension()
     * EN:
     * - Returns the normalized extension of a given file path.
     * - Uses PHP's pathinfo() to extract the extension.
     * - Converts it to lowercase for consistency.
     * - Special case: if extension is "jpg", it returns "jpeg"
     *   (to align with libraries like GD or Imagick that expect "jpeg").
     * - Returns an empty string if no extension is found.
     *
     * IT:
     * - Restituisce l'estensione normalizzata di un file.
     * - Usa pathinfo() di PHP per estrarre l'estensione.
     * - Converte in minuscolo per coerenza.
     * - Caso speciale: se l'estensione è "jpg", restituisce "jpeg"
     *   (per allinearsi a librerie come GD o Imagick che si aspettano "jpeg").
     * - Restituisce stringa vuota se non trova estensione.
     *
     * @param string $photo  EN: file path / IT: percorso file
     * @return string        EN: normalized extension / IT: estensione normalizzata
     ******************************************************************/
    public static function punk_extension(string $photo): string
    {
        // EN: Extract extension and force lowercase
        // IT: Estrae l'estensione e la converte in minuscolo
        $exstension = strtolower(pathinfo($photo, PATHINFO_EXTENSION) ?: '');

        // EN: Normalize "jpg" → "jpeg"
        // IT: Normalizza "jpg" → "jpeg"
        return $exstension === 'jpg' ? 'jpeg' : $exstension;
    }
}

