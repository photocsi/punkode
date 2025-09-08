<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-imageservice.php
| DESCRIPTION:
| EN: Neutral planner for image resize operations. Does not perform the
|     resize itself, but calculates the target size based on constraints.
| IT: Pianificatore neutro per le operazioni di resize. Non ridimensiona
|     direttamente, ma calcola le dimensioni target in base ai vincoli.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Modules\Image;

use Punkode\Anarkode\NoFutureFrame\Core\PUNK_ResizeLogic;

/**********************************************************************
 * CLASS: PUNK_ImageService
 * EN: Neutral service that delegates to PUNK_ResizeLogic to compute
 *     target dimensions. Useful for planning resize operations without
 *     touching image files.
 * IT: Servizio neutro che delega a PUNK_ResizeLogic il calcolo delle
 *     dimensioni target. Utile per pianificare resize senza agire sui file.
 **********************************************************************/
class PUNK_ImageService
{
    /******************************************************************
     * METHOD: punk_planResize()
     * EN:
     * - Computes new dimensions that fit the original size ($original_w x $original_h)
     *   into the maximum box ($max_w x $max_h) while preserving aspect ratio.
     * - Delegates calculation to PUNK_ResizeLogic::punk_fitBox().
     * - Returns an array [newWidth, newHeight].
     *
     * IT:
     * - Calcola le nuove dimensioni che fanno entrare l’originale
     *   ($original_w x $original_h) nella scatola massima ($max_w x $max_h) mantenendo il
     *   rapporto di aspetto.
     * - Delega il calcolo a PUNK_ResizeLogic::punk_fitBox().
     * - Restituisce un array [nuovaLarghezza, nuovaAltezza].
     *
     * @param int $original_w EN: original width / IT: larghezza originale
     * @param int $original_h EN: original height / IT: altezza originale
     * @param int $max_w EN: max width constraint / IT: larghezza massima
     * @param int $max_h EN: max height constraint / IT: altezza massima
     * @return array [int $newWidth, int $newHeight]
     ******************************************************************/
    public function punk_planResize(int $original_w, int $original_h, int $max_w, int $max_h): array
    {
        return PUNK_ResizeLogic::punk_fitBox($original_w, $original_h, $max_w, $max_h);
    }
}
