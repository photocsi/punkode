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
// ------------------------------------------------
// - Qui si cerca un file "config.php" nella stessa cartella di questo file.
// - Se esiste, lo si include una sola volta con require_once.
// - Scopo: permettere di definire costanti/opzioni (es. PUNK_ENV, ecc.) prima che l'autoloader giri.
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

// 2) Registrazione dell’autoloader con una closure
// ------------------------------------------------
// - spl_autoload_register accetta una funzione che riceve il nome completo della classe ($class).
// - Quando il codice esegue "new Qualcosa\Di\NomeClasse", PHP richiamerà automaticamente questa funzione
//   nel caso il file non sia ancora stato incluso, così possiamo risolvere il percorso file e includerlo.
spl_autoload_register(function ($class) {
    // EN: Change this if you want a shorter prefix (e.g., 'NoFutureFrame\\')
    // IT: Cambia questo se vuoi un prefisso più corto (es. 'NoFutureFrame\\')
    // ----------------------------------------------------------------------
    // $prefix: porzione iniziale del namespace che identifica "il nostro" spazio di nomi.
    //          Solo le classi che iniziano con questo prefisso verranno gestite da questo autoloader.
    $prefix = 'Punkode\\Anarkode\\NoFutureFrame\\';

    // $base: cartella base in cui risiede il codice sorgente mappato (di solito "src/").
    //        __DIR__ è la directory del file corrente; aggiungiamo "/src/" dove si aspettano le classi.
    $base   = __DIR__ . '/src/';

    // EN: Not our namespace → skip
    // IT: Fuori dal nostro namespace → ignora
    // ---------------------------------------
    // Se la classe richiesta NON inizia con il prefisso atteso, significa che non ci riguarda.
    // Evitiamo di fare qualunque cosa e lasciamo ad altri autoloader (o a PHP) il compito.
    if (strncmp($prefix, $class, strlen($prefix)) !== 0) return;

    // EN: Relative path like 'Core/PUNK_ResizeWp'
    // IT: Percorso relativo tipo 'Core/PUNK_ResizeWp'
    // ----------------------------------------------
    // Ritagliamo dal nome completo della classe la parte dopo il prefisso di namespace.
    // Esempio:
    //   $class   = "Punkode\Anarkode\NoFutureFrame\Environments\wp\PUNK_ResizeWp"
    //   $prefix  = "Punkode\Anarkode\NoFutureFrame\"
    //   $relative= "Environments\wp\PUNK_ResizeWp"
    $relative = substr($class, strlen($prefix));

    // Sostituiamo i separatori di namespace "\" con "/" per ottenere un percorso di cartella valido.
    // Risultato: "Environments/wp/PUNK_ResizeWp"
    $relative = str_replace('\\', '/', $relative);

    // $dir: cartella (parte del percorso) che precede il nome della classe vera e propria.
    //       Con dirname ricaviamo la directory del relativo ("Environments/wp").
    //       Se non ci sono slash (classe alla radice), dirname(...) restituisce ".".
    $dir      = dirname($relative);           // es. 'Core' o 'Environments/wp'

    // $baseName: nome "foglia" della classe, ovvero l'ultimo segmento dopo l'ultimo "/".
    //            Esempio: "PUNK_ResizeWp"
    $baseName = basename($relative);          // es. 'PUNK_ResizeWp'

    // EN: Convert CamelCase → kebab-case (ResizeWp → resize-wp)
    // IT: Converte CamelCase → kebab-case (ResizeWp → resize-wp)
    // ---------------------------------------------------------
    // Definiamo una funzione anonima di utilità che trasforma una stringa CamelCase in kebab-case.
    // - "ResizeWp" -> "Resize-Wp" (inserimento del "-")
    // - poi strtolower -> "resize-wp"
    // Nota: se la stringa contiene già underscore, qui non li tocchiamo (li gestiamo più avanti con slug).
    $to_kebab = static function (string $s): string {
        $s = preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $s);
        return strtolower($s);
    };

    // EN: PUNK_* classes/interfaces map to class-punk-*.php / interface-punk-*.php
    // IT: Le PUNK_* puntano a class-punk-*.php / interface-punk-*.php
    // ----------------------------------------------------------------
    // Qui applichiamo la convenzione "WordPress style" con prefisso PUNK_.
    // Se il nome della classe inizia con "PUNK_", costruiamo i possibili file target secondo lo schema:
    //   - class-punk-{slug}.php
    //   - interface-punk-{slug}.php
    //   - trait-punk-{slug}.php
    // dove {slug} è il nome della classe senza "PUNK_" e convertito in kebab-case.
    if (strpos($baseName, 'PUNK_') === 0) {
        // $tail: nome della classe senza il prefisso "PUNK_". Esempio: "ResizeWp" (o "Resize_Interface", ecc.).
        $tail  = substr($baseName, 5);        // es. 'ResizeWp'

        // $kebab: conversione del tail in kebab-case. Esempio: "ResizeWp" -> "resize-wp".
        $kebab = $to_kebab($tail);            // es. 'resize-wp'

        // $candidates: lista di file che proveremo a caricare, in ordine.
        // - Se la classe fosse un'interfaccia o un trait, qui NON lo deduciamo: proviamo tutte e tre le varianti.
        // - Questo approccio è semplice e robusto: basta che uno dei file esista.
        $candidates = [
            $base . ($dir !== '.' ? $dir.'/' : '') . 'class-punk-'     . $kebab . '.php',
            $base . ($dir !== '.' ? $dir.'/' : '') . 'interface-punk-' . $kebab . '.php',
            $base . ($dir !== '.' ? $dir.'/' : '') . 'trait-punk-'     . $kebab . '.php',
        ];

        // Ciclo su tutti i candidati: al primo file esistente, lo includo e ritorno (stop caricamento).
        foreach ($candidates as $file) {
            if (is_file($file)) { require_once $file; return; }
        }
    }

    // EN: Fallback PSR-4 style: 'Foo\Bar\Baz' → 'src/Foo/Bar/Baz.php'
    // IT: Fallback stile PSR-4: 'Foo\Bar\Baz' → 'src/Foo/Bar/Baz.php'
    // ----------------------------------------------------------------
    // Se non era una classe PUNK_ o non abbiamo trovato i file secondo lo schema "class/interface/trait-punk-*",
    // tentiamo un fallback PSR-4 "puro": mappiamo direttamente il namespace a cartelle e aggiungiamo ".php".
    // Esempio:
    //   relative = "Core/PUNK_Qualcosa" => cercherà "src/Core/PUNK_Qualcosa.php".
    // Questo permette, se vuoi, di avere anche file/classi in stile PSR-4 tradizionale dentro "src/".
    $file = $base . $relative . '.php';
    if (is_file($file)) { require_once $file; }
});



// 3) Inclusione delle funzioni globali (helpers)
// ----------------------------------------------
// Le funzioni globali con prefisso "punk_*" NON possono essere caricate via autoload (PHP non autocarica funzioni).
// Per questo motivo, qui includiamo sempre "helpers.php", che dovrebbe definire tutte le funzioni globali necessarie.
// Nota: require_once evita inclusioni multiple se questo file viene caricato più volte.
require_once __DIR__ . '/helpers.php';
