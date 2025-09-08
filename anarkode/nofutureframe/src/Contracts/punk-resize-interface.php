<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Contracts/PUNK_ResizeInterface.php
| DESCRIPTION:
| EN: Contract for Image Services in NoFutureFrame.
|     Any environment (WordPress, Laravel, PHP, etc.) that wants to handle
|     image resizing must implement this interface.
| IT: Contratto per i Servizi Immagini in NoFutureFrame.
|     Qualsiasi ambiente (WordPress, Laravel, PHP, ecc.) che vuole gestire
|     il ridimensionamento immagini deve implementare questa interfaccia.
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

