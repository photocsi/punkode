<?php

namespace Punkode;


if (!defined('PKDIR')) {
    define('PKDIR', __DIR__);
}
// ---------------------------------------------------------
// Core includes (framework)
// ---------------------------------------------------------
require_once __DIR__ . '/includes/safety-final.php';
require_once  __DIR__ . '/includes/safety-trait-class.php';
require_once __DIR__ . '/includes/log-final.php';
require_once __DIR__ . '/includes/tool-trait-class.php';
require_once __DIR__ . '/includes/dir-trait-class.php';
require_once __DIR__ . '/includes/manag_table-trait-class.php';
require_once __DIR__ . '/includes/upload-trait-class.php';
require_once __DIR__ . '/includes/hook-trait-class.php';
require_once  __DIR__ . '/includes/table-class.php';
require_once __DIR__ . '/includes/render-class.php';
require_once  __DIR__ . '/includes/form-class.php';
require_once __DIR__ . '/includes/migrator-db.php';
require_once __DIR__ . '/includes/watermark-class.php';


/**
 * =============================================================================
 * FILE
 * -----------------------------------------------------------------------------
 * ANARKORE-SETUP — Punkode/Anarkore core bootstrap (project-agnostic)
 * =============================================================================
 */

class SETUP_PK
{
    // Default DB config (from constants)
    public string $db       = PK_DB_NAME;
    public string $host     = PK_DB_HOST;      // can be "localhost" or "127.0.0.1:3308"
    public string $user     = PK_DB_USER;
    public string $password = PK_DB_PASSWORD;

    // ✅ Base URL generica, ma overridabile dai progetti
    public static string $url_dir_main = PK_URL_DIR_MAIN;

    protected string $pk_version = '2.0';

    /**
     * Generic override container.
     * EN: Projects may fill this (from env, config file, etc.) and DB_PK can apply it.
     * IT: I progetti possono valorizzarlo (da env, config file, ecc.) e DB_PK può applicarlo.
     */
    public static array $override = [];

    public function __construct()
    {
        // Core stays generic: no getenv() here.
        // Optional: apply generic overrides if a project provided them.
        if (!empty(self::$override)) {
            foreach (self::$override as $k => $v) {
                if (property_exists($this, $k)) {
                    $this->{$k} = (string)$v;
                }
            }
        }
    }

    public static function pk_dipendenze(): void {}
}

// ---------------------------------------------------------
// OPTIONAL: project-specific extension (ex: D20)
// IMPORTANT: lo includo PRIMA di definire PKURLMAIN
//TOGLIERE PER LA DISTRIBUZIONE DI SOLO PUNKODE
// ---------------------------------------------------------
$projectGlue = __DIR__ . '/anarkore-d20-setup.php';
if (is_file($projectGlue)) {
    require_once $projectGlue;
}
//FINO QUA TOGLIERE PER LA DISTRIBUZIONE DI PUNKODE



// ---------------------------------------------------------
// Define base constants for Punkode
// ---------------------------------------------------------

if (!defined('PKURLMAIN')) {
    define('PKURLMAIN', SETUP_PK::$url_dir_main);
}

if (!defined('PKPAGE')) {
    define('PKPAGE', __FILE__);
}
