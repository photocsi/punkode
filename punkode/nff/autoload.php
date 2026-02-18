<?php
/*
|--------------------------------------------------------------------------
| IT: Autoloader standalone per NFF, stile WordPress con prefisso PUNK_.
|     Convenzioni file:
|       - classi     => class-punk-{slug}.php
|       - interfacce => interface-punk-{slug}.php
|       - trait      => trait-punk-{slug}.php
|     Dove {slug} è lo snake/camel trasformato in kebab-case (minuscolo).
|     Le sottocartelle seguono il namespace dopo:
|       Punkode\Anarkode\NoFutureFrame\
|--------------------------------------------------------------------------
*/
// 1) Carica la config PRIMA di tutto (se presente)
$cfg = __DIR__ . '/config.php';
if (is_file($cfg)) {
    require_once $cfg;
}

/*
|------------------------------------------------------------------------------
| EN: Minimal autoloader for NoFutureFrame-style classes and interfaces.
| IT: Autoloader minimale per classi/interfacce stile NoFutureFrame.
|------------------------------------------------------------------------------
*/

spl_autoload_register(function ($class) {
    // EN: Change this if you want a shorter prefix (e.g., 'NoFutureFrame\\')
    // IT: Cambia questo se vuoi un prefisso più corto (es. 'NoFutureFrame\\')
    $prefix = 'Punkode\\Anarkode\\NoFutureFrame\\';
    $base   = __DIR__ . '/src/';

    // EN: Not our namespace → skip
    // IT: Fuori dal nostro namespace → ignora
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;

    // EN: Relative path like 'Core/PUNK_ResizeWp'
    // IT: Percorso relativo tipo 'Core/PUNK_ResizeWp'
    $relative = substr($class, strlen($prefix));
    $relative = str_replace('\\', '/', $relative);

    $dir      = dirname($relative);           // es. 'Core' o 'Environments/wp'
    $baseName = basename($relative);          // es. 'PUNK_ResizeWp'

    // EN: Convert CamelCase → kebab-case (ResizeWp → resize-wp)
    // IT: Converte CamelCase → kebab-case (ResizeWp → resize-wp)
    $to_kebab = static function (string $s): string {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $s);
        return strtolower($s);
    };

    // EN: PUNK_* classes/interfaces map to class-punk-*.php / interface-punk-*.php
    // IT: Le PUNK_* puntano a class-punk-*.php / interface-punk-*.php
    if (strpos($baseName, 'PUNK_') === 0) {
        $tail  = substr($baseName, 5);        // es. 'ResizeWp'
        $kebab = $to_kebab($tail);            // es. 'resize-wp'

        $candidates = [
            $base . ($dir !== '.' ? $dir.'/' : '') . 'class-punk-'     . $kebab . '.php',
            $base . ($dir !== '.' ? $dir.'/' : '') . 'interface-punk-' . $kebab . '.php',
            $base . ($dir !== '.' ? $dir.'/' : '') . 'trait-punk-'     . $kebab . '.php',
        ];

        foreach ($candidates as $file) {
            if (is_file($file)) { require_once $file; return; }
        }
    }

    // EN: Fallback PSR-4 style: 'Foo\Bar\Baz' → 'src/Foo/Bar/Baz.php'
    // IT: Fallback stile PSR-4: 'Foo\Bar\Baz' → 'src/Foo/Bar/Baz.php'
    $file = $base . $relative . '.php';
    if (is_file($file)) { require_once $file; }
});



// Le funzioni globali prefissate punk_* non sono autocaricabili: includile sempre qui.

require_once __DIR__ . '/helpers.php';

