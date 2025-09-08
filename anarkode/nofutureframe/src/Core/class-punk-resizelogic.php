<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-resizelogic.php
| DESCRIPTION: EN: ratio math & dir ensure. IT: matematica ratio & creazione dir.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Core;

class PUNK_ResizeLogic
{

    /* *****************************************************************
 * [METHOD / METODO] punk_fitBox
 * -----------------------------------------------------------------
 * EN:
 * Compute the target width and height that make an image fit inside
 * a bounding box while preserving aspect ratio.
 * - Input: original width/height ($ow, $original_h), max width/height ($max_w, $max_h)
 * - Output: array [newWidth, newHeight]
 * - The image is never upscaled: if it is already smaller than the box,
 *   the scale factor will be 1.0 (keeps original size).
 *
 * IT:
 * Calcola la larghezza e l’altezza finali per far entrare un’immagine
 * dentro un riquadro massimo mantenendo il rapporto d’aspetto.
 * - Input: larghezza/altezza originali ($ow, $original_h), massime ($max_w, $max_h)
 * - Output: array [nuovaLarghezza, nuovaAltezza]
 * - L’immagine non viene mai ingrandita: se è già più piccola del box,
 *   il fattore di scala sarà 1.0 (mantiene la dimensione originale).
 * ***************************************************************** */

    public static function punk_fitBox(int $original_w, int $original_h, int $max_w, int $max_h): array
    {
        $r = min($max_w / max(1, $original_w), $max_h / max(1, $original_h), 1.0);
        return [(int)floor($original_w * $r), (int)floor($original_h * $r)];
    }

    /* *****************************************************************
 * [METHOD / METODO] punk_ensureDir
 * -----------------------------------------------------------------
 * EN:
 * Utility to guarantee that the destination directory exists
 * before saving a file. 
 * - Input: full file path (including filename, e.g. "processed/img/foto.jpg")
 * - Action: extracts the parent folder ("processed/img") and creates it
 *   recursively if it does not exist.
 * - Avoids errors like "failed to open stream: No such file or directory"
 *   when saving images.
 *
 * IT:
 * Utility per garantire che la cartella di destinazione esista
 * prima di salvare un file.
 * - Input: percorso completo del file (compreso il nome, es. "processed/img/foto.jpg")
 * - Azione: estrae la cartella padre ("processed/img") e la crea
 *   ricorsivamente se non esiste.
 * - Evita errori come "failed to open stream: No such file or directory"
 *   durante il salvataggio delle immagini.
 * ***************************************************************** */

    public static function punk_ensureDir(string $filePath): void
    {
        $dirname = dirname($filePath);
        if (!is_dir($dirname)) @mkdir($dirname, 0755, true);
    }
}
