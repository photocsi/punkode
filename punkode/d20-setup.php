<?php

declare(strict_types=1);

/**
 * =============================================================================
 * FILE: public/punkode/d20_setup.php
 * -----------------------------------------------------------------------------
 * EN: Single bootstrap for D20 (web + desktop). env.php is WEB only.
 *     Defines all constants together at the bottom.
 *     License logic included here, but runs ONLY on desktop/electron.
 * IT: Bootstrap unico D20 (web + desktop). env.php solo WEB.
 *     Definisce tutte le costanti insieme in fondo.
 *     Logica licenza inclusa qui, ma gira SOLO su desktop/electron.
 * =============================================================================
 */

/* -------------------------------------------------------------------------- */
/* EN/IT: Basic directories (based on your structure /dev/public/punkode)      */
/* -------------------------------------------------------------------------- */
$PUNKODE_DIR = __DIR__;                  // .../public/punkode
$PUBLIC_DIR  = dirname(__DIR__);         // .../public
$APP_DIR     = dirname($PUBLIC_DIR);     // .../dev (root app)

/* -------------------------------------------------------------------------- */
/* EN/IT: Choose env file based on PK_MODE                                     */
/* -------------------------------------------------------------------------- */
$name_file_env = (defined('PK_MODE') && PK_MODE === 'production') ? 'env.php' : 'env_dev.php';
$envPath = '/var/www/' . $name_file_env;

/* -------------------------------------------------------------------------- */
/* EN/IT: Load env file (ONE source of truth)                                 */
/* -------------------------------------------------------------------------- */
$config = [];
if (is_file($envPath)) {
    $tmp = require $envPath;
    if (is_array($tmp)) {
        $config = $tmp;
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: Single config reader (ONLY from $config, no getenv)                  */
/* -------------------------------------------------------------------------- */
if (!function_exists('d20_env')) {
    function d20_env(string $key, $default = null): mixed
    {
        // One truth: env.php array
        global $config;

        if (!is_array($config) || !array_key_exists($key, $config)) {
            return $default;
        }

        $v = $config[$key];

        // Normalize strings "true"/"false" if you keep them as strings
        if (is_string($v)) {
            $vv = strtolower(trim($v));
            if ($vv === 'true')  return true;
            if ($vv === 'false') return false;
        }

        return $v;
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: Detect platform (your current rule)                                  */
/* -------------------------------------------------------------------------- */
$PLATFORM = is_file($envPath) ? 'web' : 'desktop';

/* -------------------------------------------------------------------------- */
/* EN/IT: WEB autoload (must be BEFORE any Aws classes usage)                  */
/* -------------------------------------------------------------------------- */
if ($PLATFORM === 'web') {
    $autoloadWeb = $APP_DIR . '/vendorweb/autoload.php';
    if (is_readable($autoloadWeb)) {
        require_once $autoloadWeb;
    } else {
        error_log('[D20BOOT] vendorweb/autoload.php NOT found at: ' . $autoloadWeb);
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: Compute RAW values first (no constants yet)                          */
/* -------------------------------------------------------------------------- */

// APP_ENV (your usage: folder like "dev", "v1", or empty)
$APP_ENV_RAW = (string)(d20_env('APP_ENV', '') ?? '');
$APP_ENV_RAW = trim($APP_ENV_RAW, '/');

// Which folder (dev/live etc.) — prefer explicit D20_DEV_DIR or D20_CARTELLA
$CARTELLA = (string)(d20_env('D20_DEV_DIR', '') ?: d20_env('D20_CARTELLA', '') ?: ($APP_ENV_RAW !== '' ? $APP_ENV_RAW : 'app'));
$CARTELLA = trim($CARTELLA, '/');

// Base URL
$BASE_URL = (string)(d20_env('BASE_URL', '') ?: 'http://127.0.0.1:8899');
$BASE_URL = rtrim($BASE_URL, '/');

// Storage mode
if ($PLATFORM === 'web') {
    $STORAGE_MODE = (string)(d20_env('STORAGE_MODE', 's3') ?: 's3');
} else {
    $STORAGE_MODE = 'local';
}

// D20 root
$D20_ROOT = (string)(d20_env('D20_ROOT', '') ?: $APP_DIR);

// D20DATA paths (desktop fallback)
$DATA_ROOT = (string)(d20_env('D20_DATA_ROOT', '') ?: '');
if ($DATA_ROOT === '') {
    $fallback = $APP_DIR . DIRECTORY_SEPARATOR . 'D20DATA';
    $real = realpath($fallback);
    $DATA_ROOT = ($real !== false) ? $real : $fallback;
}

$IMG_BASE   = (string)(d20_env('D20_IMG_BASE', '') ?: ($DATA_ROOT . DIRECTORY_SEPARATOR . 'img'));
$QUEUE_BASE = (string)(d20_env('D20_QUEUE_BASE', '') ?: ($DATA_ROOT . DIRECTORY_SEPARATOR . 'queue'));

// Web-only local images root (optional)
$WEB_IMG_ROOT = (string)(d20_env('D20_WEB_IMG_ROOT', '') ?: '');

// AWS S3 params (raw)
$S3_BUCKET = (string)(d20_env('S3_BUCKET', '') ?: '');
$S3_REGION = (string)(d20_env('S3_REGION', '') ?: 'eu-south-1');
$S3_KEY    = (string)(d20_env('S3_KEY', '') ?: '');
$S3_SECRET = (string)(d20_env('S3_SECRET', '') ?: '');

// Compute PK_URL_DIR_MAIN once (no isset(d20_env()))
// WEB: "/{CARTELLA}/" (or "/" if empty or "app"), DESKTOP: "../../../" (your desired fallback)
if ($PLATFORM === 'web') {
    // tu dici: APP_ENV ti arriva già come "/app/" oppure "app"
    $raw = (string) d20_env('APP_ENV', '');

    if ($raw !== '') {
        $PK_URL_DIR_MAIN_RAW = '/' . $raw . '/';
        define('PK_URL_DIR_MAIN', $PK_URL_DIR_MAIN_RAW);
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: Register S3 stream wrapper (web only)                                */
/* -------------------------------------------------------------------------- */
if (
    $PLATFORM === 'web' &&
    class_exists(\Aws\S3\S3Client::class) &&
    $S3_BUCKET !== '' && $S3_KEY !== '' && $S3_SECRET !== ''
) {
    try {
        $s3 = new \Aws\S3\S3Client([
            'version'     => 'latest',
            'region'      => $S3_REGION,
            'credentials' => ['key' => $S3_KEY, 'secret' => $S3_SECRET],
        ]);
        $s3->registerStreamWrapper();
    } catch (\Throwable $e) {
        error_log('[D20] Unable to register S3 stream wrapper: ' . $e->getMessage());
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: LICENSE (ONLY desktop/electron)                                      */
/* -------------------------------------------------------------------------- */
$IS_PRO = false;
$LICENSE_EXPIRES_AT = 0;
$LICENSE_LIVELLO = 0;

if ($PLATFORM === 'desktop') {

    $licenseLib = $APP_DIR . '/public/includes/license-read.php';
    if (is_readable($licenseLib)) {
        require_once $licenseLib;

        $licensePath  = rtrim($DATA_ROOT, '/\\') . '/danger/punkabestia';
        $fallbackPath = rtrim($DATA_ROOT, '/\\') . '/punk.key';

        $lic = d20_read_local_license_b64($licensePath);

        if (is_array($lic) && d20_license_is_valid($lic)) {
            $IS_PRO = true;
            $payload = (array)($lic['payload'] ?? []);
            $LICENSE_EXPIRES_AT = (int)($payload['expires_at'] ?? 0);
            $LICENSE_LIVELLO    = (int)($payload['livello'] ?? 0);
        }

        // Optional fallback (desktop only)
        if (!$IS_PRO) {
            if (is_file($fallbackPath)) {
                $content = file_get_contents($fallbackPath);
                if ($content !== false && trim($content) === 'punk') {
                    $IS_PRO = true;
                }
            }
        }
    } else {
        error_log('[D20] license-read.php not found at: ' . $licenseLib);
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: DEFINE ALL CONSTANTS TOGETHER (bottom, as requested)                 */
/* -------------------------------------------------------------------------- */

// Environment / directories
if (!defined('APP_ENV'))         define('APP_ENV', $APP_ENV_RAW);

if (!defined('D20_PUNKODE_DIR')) define('D20_PUNKODE_DIR', $PUNKODE_DIR);
if (!defined('D20_PUBLIC_DIR'))  define('D20_PUBLIC_DIR',  $PUBLIC_DIR);
if (!defined('D20_APP_DIR'))     define('D20_APP_DIR',     $APP_DIR);

// Platform / core
if (!defined('D20_PLATFORM'))     define('D20_PLATFORM', $PLATFORM);
if (!defined('D20_CARTELLA'))     define('D20_CARTELLA', $CARTELLA);
if (!defined('D20_BASE_URL'))     define('D20_BASE_URL', $BASE_URL);
if (!defined('D20_STORAGE_MODE')) define('D20_STORAGE_MODE', $STORAGE_MODE);

// Root
if (!defined('D20_ROOT'))         define('D20_ROOT', $D20_ROOT);

// Data paths
if (!defined('D20_DATA_ROOT'))    define('D20_DATA_ROOT', $DATA_ROOT);
if (!defined('D20_IMG_BASE'))     define('D20_IMG_BASE',  $IMG_BASE);
if (!defined('D20_QUEUE_BASE'))   define('D20_QUEUE_BASE', $QUEUE_BASE);
if (!defined('D20_WEB_IMG_ROOT')) define('D20_WEB_IMG_ROOT', $WEB_IMG_ROOT);

// S3 constants (always defined; meaningful on web)
if (!defined('D20_S3_BUCKET')) define('D20_S3_BUCKET', $S3_BUCKET);
if (!defined('D20_S3_REGION')) define('D20_S3_REGION', $S3_REGION);
if (!defined('D20_S3_KEY'))    define('D20_S3_KEY', $S3_KEY);
if (!defined('D20_S3_SECRET')) define('D20_S3_SECRET', $S3_SECRET);

// License constants (always defined; meaningful on desktop)
if (!defined('D20_IS_PRO'))             define('D20_IS_PRO', $IS_PRO);
if (!defined('D20_LICENSE_EXPIRES_AT')) define('D20_LICENSE_EXPIRES_AT', $LICENSE_EXPIRES_AT);
if (!defined('D20_LICENSE_LIVELLO'))    define('D20_LICENSE_LIVELLO', $LICENSE_LIVELLO);

// Session mirror (optional)
if (session_status() === PHP_SESSION_NONE) session_start();
$_SESSION['D20_IS_PRO'] = D20_IS_PRO;
$_SESSION['D20_LICENSE_EXPIRES_AT'] = D20_LICENSE_EXPIRES_AT;
$_SESSION['D20_LICENSE_LIVELLO'] = D20_LICENSE_LIVELLO;

// Desktop: ensure id_azienda exists in session (default 0)
if (D20_PLATFORM === 'desktop') {
    if (!isset($_SESSION['id_azienda']) || !is_numeric($_SESSION['id_azienda'])) {
        $_SESSION['id_azienda'] = 0;
    } else {
        $_SESSION['id_azienda'] = (int)$_SESSION['id_azienda'];
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: Helpers                                                              */
/* -------------------------------------------------------------------------- */
if (!function_exists('d20_url')) {
    function d20_url(string $rel): string
    {
        return rtrim(D20_BASE_URL, '/') . '/' . ltrim($rel, '/');
    }
}
if (!function_exists('d20_path')) {
    function d20_path(string $rel): string
    {
        $rel = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $rel);
        return rtrim(D20_PUBLIC_DIR, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim($rel, DIRECTORY_SEPARATOR);
    }
}

/* -------------------------------------------------------------------------- */
/* EN/IT: Debug dump (ONE truth: config array + constants + session)           */
/* -------------------------------------------------------------------------- */
if (!function_exists('d20_debug_dump')) {

    function d20_debug_dump(bool $echo = false): array
    {
        global $config;

        $maskSensitive = function ($value, string $keyPath = '') use (&$maskSensitive) {
            $key = strtolower($keyPath);
            $suspiciousKeys = ['pass', 'password', 'pwd', 'secret', 'token', 'jwt', 'bearer', 'cookie', 'csrf', 'key', 'license', 'auth'];
            foreach ($suspiciousKeys as $needle) {
                if ($needle !== '' && str_contains($key, $needle)) {
                    if (is_string($value)) return '[MASKED:string len=' . strlen($value) . ']';
                    if (is_array($value))  return '[MASKED:array]';
                    if (is_object($value)) return '[MASKED:object]';
                    return '[MASKED]';
                }
            }
            if (is_array($value)) {
                $out = [];
                foreach ($value as $k => $v) {
                    $kp = ($keyPath === '') ? (string)$k : ($keyPath . '.' . (string)$k);
                    $out[$k] = $maskSensitive($v, $kp);
                }
                return $out;
            }
            return $value;
        };

        $sessStatus = session_status();
        $sessStatusLabel = match ($sessStatus) {
            PHP_SESSION_DISABLED => 'DISABLED',
            PHP_SESSION_NONE     => 'NONE',
            PHP_SESSION_ACTIVE   => 'ACTIVE',
            default              => (string)$sessStatus,
        };

        $sessionData = null;
        if ($sessStatus === PHP_SESSION_ACTIVE) {
            $sessionData = $maskSensitive($_SESSION ?? [], 'root');
        }

        // Mask config too (it may contain S3 secrets etc.)
        $configMasked = $maskSensitive(is_array($config) ? $config : [], 'config');

        $data = [
            '--- CONSTANTS ---' => '',
            'APP_ENV'          => defined('APP_ENV') ? APP_ENV : '(not defined)',
            'PK_URL_DIR_MAIN'  => defined('PK_URL_DIR_MAIN') ? PK_URL_DIR_MAIN : '(not defined)',
            'D20_APP_DIR'  => defined('D20_APP_DIR') ? D20_APP_DIR : '(not defined)',
            'D20_PUBLIC_DIR'  => defined('D20_PUBLIC_DIR') ? D20_PUBLIC_DIR : '(not defined)',
            'D20_BASE_URL'  => defined('D20_BASE_URL') ? D20_BASE_URL : '(not defined)',
            'D20_DATA_ROOT'  => defined('D20_DATA_ROOT') ? D20_DATA_ROOT : '(not defined)',
            'D20_IMG_BASE'  => defined('D20_IMG_BASE') ? D20_IMG_BASE : '(not defined)',
            'D20_PLATFORM'     => defined('D20_PLATFORM') ? D20_PLATFORM : '(not defined)',
            'D20_CARTELLA'     => defined('D20_CARTELLA') ? D20_CARTELLA : '(not defined)',
            'D20_BASE_URL'     => defined('D20_BASE_URL') ? D20_BASE_URL : '(not defined)',
            'D20_DATA_ROOT'    => defined('D20_DATA_ROOT') ? D20_DATA_ROOT : '(not defined)',
            'D20_IMG_BASE'     => defined('D20_IMG_BASE') ? D20_IMG_BASE : '(not defined)',
            'D20_QUEUE_BASE'   => defined('D20_QUEUE_BASE') ? D20_QUEUE_BASE : '(not defined)',
            'D20_STORAGE_MODE' => defined('D20_STORAGE_MODE') ? D20_STORAGE_MODE : '(not defined)',
            'D20_WEB_IMG_ROOT' => defined('D20_WEB_IMG_ROOT') ? D20_WEB_IMG_ROOT : '(not defined)',
            'D20_IS_PRO'       => defined('D20_IS_PRO') ? (D20_IS_PRO ? '1' : '0') : '(not defined)',
            'D20_LICENSE_LIVELLO'    => defined('D20_LICENSE_LIVELLO') ? D20_LICENSE_LIVELLO : '(not defined)',
            'D20_LICENSE_EXPIRES_AT' => defined('D20_LICENSE_EXPIRES_AT') ? D20_LICENSE_EXPIRES_AT : '(not defined)',

            ' ' => '',
            '--- CONFIG (env.php array) ---' => '',
            '$config' => $configMasked,

            ' ' => '',
            '--- SESSION ---' => '',
            'session_status' => $sessStatusLabel,
            'session_id'     => ($sessStatus === PHP_SESSION_ACTIVE ? session_id() : ''),
            'session_name'   => ($sessStatus === PHP_SESSION_ACTIVE ? session_name() : ''),
            '$_SESSION'      => ($sessStatus === PHP_SESSION_ACTIVE ? $sessionData : '[SESSION NOT ACTIVE]'),
        ];

        if ($echo) {
            echo '<pre style="text-align:left;background:#fff;border:1px solid #c7b48a;padding:10px;border-radius:8px;max-width:900px;margin:10px auto;overflow:auto;font-size:12px;">';
            foreach ($data as $k => $v) {
                if ($v === '' && str_starts_with($k, '---')) {
                    echo $k . "\n";
                    continue;
                }
                if ($k === ' ' && $v === '') {
                    echo "\n";
                    continue;
                }

                if (is_array($v) || is_object($v)) {
                    echo str_pad((string)$k, 28, ' ') . " : " . json_encode($v, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    echo str_pad((string)$k, 28, ' ') . " : " . (is_scalar($v) ? (string)$v : '') . "\n";
                }
            }
            echo "</pre>";
        }

        return $data;
    }
}
