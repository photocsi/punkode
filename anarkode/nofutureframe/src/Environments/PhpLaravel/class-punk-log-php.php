<?php
/*
|--------------------------------------------------------------------------
| FILE: class-punk-log-php.php
| DESCRIPTION:
| EN: Simple PHP file logger for NoFutureFrame.
|     By default writes to /nofutureframe/logs/nofutureframe.log,
|     but can be overridden by defining PUNK_LOG_DIR.
| IT: Logger PHP semplice per NoFutureFrame.
|     Per default scrive in /nofutureframe/logs/nofutureframe.log,
|     ma può essere cambiato definendo PUNK_LOG_DIR.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Environments\PhpLaravel;

use Punkode\Anarkode\NoFutureFrame\Contracts\PUNK_LogServiceInterface;

/**********************************************************************
 * CLASS: PUNK_Log_Php
 * EN: Minimalistic file logger implementation of PUNK_LogServiceInterface.
 * IT: Implementazione minimalista di logger su file, conforme a PUNK_LogServiceInterface.
 **********************************************************************/
class PUNK_Log_Php implements PUNK_LogServiceInterface
{
    /******************************************************************
     * METHOD: punk_log()
     * EN:
     * - Determines the log directory:
     *   → If PUNK_LOG_DIR is defined, use that path.
     *   → Otherwise default to /nofutureframe/logs relative to project root.
     * - Ensures the directory exists (creates it if missing).
     * - Appends a line to nofutureframe.log with format:
     *   [YYYY-MM-DD HH:MM:SS][LEVEL] message
     * - Uses @ to suppress errors and not break app flow.
     *
     * IT:
     * - Determina la cartella log:
     *   → Se PUNK_LOG_DIR è definita, usa quel percorso.
     *   → Altrimenti default su /nofutureframe/logs relativo alla libreria.
     * - Garantisce che la directory esista (creata se manca).
     * - Aggiunge una riga a nofutureframe.log con formato:
     *   [YYYY-MM-DD HH:MM:SS][LIVELLO] messaggio
     * - Usa @ per sopprimere errori e non interrompere il flusso dell’app.
     ******************************************************************/
    public function punk_log(string $message, string $level_log = 'info'): void
    {
        // EN: Resolve path to logs folder
        // IT: Determina il percorso della cartella log
        if (defined('PUNK_LOG_DIR')) {
            $dir = PUNK_LOG_DIR;
        } else {
            // EN: go two levels up from Environments/PhpLaravel → nofutureframe/
            // IT: sali di due livelli da Environments/PhpLaravel → nofutureframe/
            $dir = dirname(__DIR__, 2) . '/logs';
        }

        // EN: Ensure logs/ exists
        // IT: Garantisce che logs/ esista
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }

        // EN: Log file path
        // IT: Percorso file log
        $file_path = $dir . '/nofutureframe.log';

        // EN: Current timestamp
        // IT: Timestamp attuale
        $date = date('Y-m-d H:i:s');

        // EN: Append formatted message to log file
        // IT: Aggiunge il messaggio formattato al file di log
        @file_put_contents(
            $file_path,
            "[$date][" . strtoupper($level_log) . "] $message\n",
            FILE_APPEND | LOCK_EX
        );
    }
}
