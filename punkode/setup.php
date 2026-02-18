<?php

declare(strict_types=1);


$mode = 'dev'; // ambiente dev , staging, production

$pk_db_name  = "indigest";
$pk_db_host = "127.0.0.1:3308";
$pk_db_user = "root";
$pk_db_password = "root";

// director main (generic default)
$pk_url_dir_main = '../../';



define('PK_DB_NAME', $pk_db_name);
define('PK_DB_HOST', $pk_db_host);
define('PK_DB_USER', $pk_db_user);
define('PK_DB_PASSWORD', $pk_db_password);


if (!defined('PK_MODE')) {
    define('PK_MODE', $mode);
}


if (!defined('PK_DEBUG')) {
    define('PK_DEBUG', PK_MODE === 'dev');  //solo se mode=dev allora debug true
}

// -------------------------------------------------
// 0) Composer autoload (se esiste) — lo carico sempre prima
// -------------------------------------------------
$autoload = dirname(__DIR__, 2) . '/vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}


// -------------------------------------------------
// 1) Se esiste d20-setup.php → bootstrap D20 (ma NON return)
// -------------------------------------------------
$d20Setup = __DIR__ . '/d20-setup.php';
if (is_file($d20Setup)) {
    require_once $d20Setup;
}

//definisco url per js se non è definito prima
if(!defined('PK_URL_DIR_MAIN'))  define('PK_URL_DIR_MAIN', $pk_url_dir_main);
// -------------------------------------------------
// 2) Bootstrap Punkode (sempre)
// -------------------------------------------------

// Anarkore
require_once __DIR__ . '/anarkore/anarkore-setup.php';

// NFF
require_once __DIR__ . '/nff/autoload.php';
