<?php
/*
|--------------------------------------------------------------------------
| FILE: anarkode/nofutureframe/src/Contracts/PUNK_LogServiceInterface.php
| DESCRIPTION:
| EN: Contract for Log Services in NoFutureFrame.
|     Any environment (WordPress, Laravel, PHP, etc.) that wants to handle
|     logging must implement this interface.
| IT: Contratto per i Servizi di Log in NoFutureFrame.
|     Qualsiasi ambiente (WordPress, Laravel, PHP, ecc.) che vuole gestire
|     il logging deve implementare questa interfaccia.
|--------------------------------------------------------------------------
*/

namespace Punkode\Anarkode\NoFutureFrame\Contracts;

/**********************************************************************
 * INTERFACE: PUNK_LogServiceInterface
 * EN: Defines the mandatory method for logging messages.
 * IT: Definisce il metodo obbligatorio per loggare messaggi.
 **********************************************************************/
interface PUNK_Log
{
    /******************************************************************
     * METHOD: punk_log()
     * EN:
     * - Logs a message with a given severity level.
     * - Parameters:
     *   $message → the log message
     *   $level   → the log level (default: "info")
     *              Examples: "debug", "info", "warning", "error"
     * - Returns:
     *   void (no return value)
     *
     * IT:
     * - Registra un messaggio con un certo livello di gravità.
     * - Parametri:
     *   $message → il messaggio da loggare
     *   $level   → il livello di log (default: "info")
     *              Esempi: "debug", "info", "warning", "error"
     * - Restituisce:
     *   void (nessun valore di ritorno)
     ******************************************************************/
    public function punk_log(string $message, string $level = 'info'): void;
}

