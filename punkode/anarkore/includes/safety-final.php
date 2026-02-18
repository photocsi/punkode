<?php

namespace Punkode;

final class SAFETY_PK
{

    private function __construct() {} // nessuna istanza


    /**
 * EN:
 * Sanitize a string so it can safely be used as a filesystem path segment
 * (folder name or file name part) across Windows, Linux and macOS.
 *
 * IT:
 * Sanifica una stringa per poterla usare in sicurezza come segmento di percorso
 * (nome cartella o parte di nome file) su Windows, Linux e macOS.
 */

    //SANITIZZO PER IL NOME DI UNA CARTELLA O DI UN FILE
public static function pk_sanitize_dir_file_name(string $value, int $maxlen = 64): string
{
    /**
     * EN: Trim leading and trailing whitespace.
     * IT: Rimuove spazi iniziali e finali.
     */
    $s = trim($value);

    /**
     * EN:
     * Attempt transliteration from UTF-8 to ASCII.
     * Example: "Matrimónio d'Été" → "Matrimonio d'Ete"
     * This improves cross-platform filesystem compatibility.
     *
     * IT:
     * Prova a convertire caratteri UTF-8 in ASCII.
     * Esempio: "Matrimónio d'Été" → "Matrimonio d'Ete"
     * Migliora la compatibilità tra filesystem diversi.
     */
    $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($t === false) {
        // EN: Fallback to original string if transliteration fails.
        // IT: Se la conversione fallisce, usa la stringa originale.
        $t = $s;
    }

    /**
     * EN:
     * Replace common separators and unsafe characters with underscore.
     * This prevents directory traversal and illegal filename characters.
     *
     * IT:
     * Sostituisce separatori comuni e caratteri non sicuri con underscore.
     * Previene path traversal e caratteri illegali nei nomi file.
     */
    $t = str_replace(
        [' ', '-', '.', "'", '"', '/', '\\', ':', '*', '?', '<', '>', '|'],
        '_',
        $t
    );

    /**
     * EN:
     * Keep only alphanumeric characters and underscore.
     * Everything else becomes underscore.
     *
     * IT:
     * Mantiene solo caratteri alfanumerici e underscore.
     * Tutto il resto viene sostituito con underscore.
     */
    $t = preg_replace('/[^A-Za-z0-9_]/', '_', $t) ?? '';

    /**
     * EN:
     * Collapse multiple consecutive underscores into one.
     * Example: "file___name" → "file_name"
     *
     * IT:
     * Compatta underscore consecutivi.
     * Esempio: "file___name" → "file_name"
     */
    $t = preg_replace('/_+/', '_', $t) ?? $t;

    /**
     * EN:
     * Avoid Windows reserved filenames.
     * Windows forbids names like CON, PRN, AUX, NUL, COM1–COM9, LPT1–LPT9.
     *
     * IT:
     * Evita nomi riservati di Windows.
     * Windows vieta nomi come CON, PRN, AUX, NUL, COM1–COM9, LPT1–LPT9.
     */
    if (preg_match('/^(CON|PRN|AUX|NUL|COM[1-9]|LPT[1-9])$/i', $t)) {
        $t = '0' . $t;
    }

    /**
     * EN:
     * Remove trailing dots or spaces.
     * Windows does not allow filenames ending with dot or space.
     *
     * IT:
     * Rimuove punto o spazio finali.
     * Windows non permette nomi che terminano con punto o spazio.
     */
    $t = rtrim($t, " .");

    /**
     * EN:
     * If the result is empty or just underscore, assign a safe fallback name.
     *
     * IT:
     * Se il risultato è vuoto o solo underscore, assegna un nome di fallback.
     */
    if ($t === '' || $t === '_') {
        $t = 'insert_name';
    }

    /**
     * EN:
     * Enforce maximum length to avoid filesystem limits.
     * Default 64 characters (safe for most filesystems).
     *
     * IT:
     * Limita la lunghezza massima per evitare problemi di filesystem.
     * Default 64 caratteri (sicuro per la maggior parte dei sistemi).
     */
    if (strlen($t) > $maxlen) {
        $t = substr($t, 0, $maxlen);

        // EN: Remove trailing underscores after trimming.
        // IT: Rimuove eventuali underscore finali dopo il taglio.
        $t = rtrim($t, '_');

        if ($t === '') {
            $t = 'insert_name';
        }
    }

    return $t;
}

}



