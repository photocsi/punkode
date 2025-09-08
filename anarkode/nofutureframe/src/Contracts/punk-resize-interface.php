<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Contracts/punk-resize-interface.php
| DESCRIPTION:
| EN: Contract for Resize Services in NoFutureFrame.
|     Environments (WordPress, Laravel, PHP) implement this to perform
|     image resizing into a destination path (which in the new pipeline
|     will be a TEMPORARY local file).
| IT: Contratto per i servizi di Resize in NoFutureFrame.
|     Gli ambienti (WordPress, Laravel, PHP) lo implementano per eseguire
|     il ridimensionamento su un percorso di destinazione (che nella nuova
|     pipeline sarà un file locale TEMPORANEO).
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Contracts;

/**********************************************************************
 * INTERFACE: PUNK_ResizeInterface
 * EN: Defines the mandatory method for image resizing.
 * IT: Definisce il metodo obbligatorio per il ridimensionamento immagini.
 **********************************************************************/
interface PUNK_ResizeInterface
{
    /******************************************************************
     * METHOD: punk_resizeTo()
     * EN:
     * - Resizes an image from a source path to a destination path.
     * - Parameters:
     *   $src     → source path or URL
     *   $dest    → destination path or URL
     *   $w       → target width in pixels
     *   $h       → target height in pixels
     *   $quality → image quality (0-100), default 90
     * - Returns:
     *   array → details of the operation (implementation-specific)
     *   false → if the resize fails
     *
     * IT:
     * - Ridimensiona un'immagine da un percorso sorgente a uno di destinazione.
     * - Parametri:
     *   $src     → percorso o URL sorgente
     *   $dest    → percorso o URL destinazione
     *   $w       → larghezza desiderata in pixel
     *   $h       → altezza desiderata in pixel
     *   $quality → qualità immagine (0-100), default 90
     * - Restituisce:
     *   array → dettagli dell’operazione (dipende dall’implementazione)
     *   false → se il ridimensionamento fallisce
     ******************************************************************/
    public function punk_resize_to(string $src, string $dest, int $w, int $h, int $quality = 90): array|false;
}

