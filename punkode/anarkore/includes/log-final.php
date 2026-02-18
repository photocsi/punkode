<?php
declare(strict_types=1);

namespace Punkode;

/**
 * =============================================================================
 * FILE: log.php
 * -----------------------------------------------------------------------------
 * EN: Minimal logger for Punkode. Writes to PHP error_log only when PK_DEBUG=true.
 * IT: Logger minimale per Punkode. Scrive su error_log solo quando PK_DEBUG=true.
 * =============================================================================
 */
final class LOG_PK
{
    private function __construct() {}

    /**
     * EN: Debug message (only in dev).
     * IT: Messaggio di debug (solo in dev).
     */
    public static function debug(string $message, array $context = []): void
    {
        if (!defined('PK_DEBUG') || PK_DEBUG !== true) {
            return;
        }

        $ctx = self::contextToJson($context);
        error_log('[Punkode][DEBUG] ' . $message . $ctx);
    }

    /**
     * EN: Error message (logged always, also in production).
     * IT: Messaggio di errore (loggato sempre, anche in produzione).
     */
    public static function error(string $message, array $context = []): void
    {
        $ctx = self::contextToJson($context);
        error_log('[Punkode][ERROR] ' . $message . $ctx);
    }

    /**
     * EN: Convert context array into compact JSON.
     * IT: Converte il contesto in JSON compatto.
     */
    private static function contextToJson(array $context): string
    {
        if (!$context) {
            return '';
        }

        $json = json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ' {context_encode_error:true}';
        }

        return ' ' . $json;
    }
}
