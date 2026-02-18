<?php

namespace Punkode;



class DB_PK extends SETUP_PK
{

    public  $pk_conn;
    public  $field = array();
    public string $table = '';
    public string $port = '';
    public $where = array();
    public $value = array();

    use SAFE_PK;
    use TOOL_PK;
    use DIR_PK;
    use UPLOAD_PK;


    public function __construct(string $table)
    {
        $this->table = $table;

        // Inizializza i default dal core (PK_DB_*), senza leggere env qui.
        parent::__construct(); // anche se vuoto, è ok e futuro-proof

        // Host può essere "localhost" oppure "127.0.0.1:3308"
        $hostRaw = (string)$this->host;

        // Default porta MySQL se non specificata
        $host = $hostRaw;
        $port = '3306';

        // Se l'host include già ":port", la estraggo
        if (strpos($hostRaw, ':') !== false) {
            [$host, $maybePort] = explode(':', $hostRaw, 2);
            if ($maybePort !== '') {
                $port = $maybePort;
            }
        }

        // DSN TCP
        $dsn = "mysql:host={$host};port={$port};dbname={$this->db};charset=utf8mb4";

        $this->pk_conn = new \PDO($dsn, $this->user, $this->password, [
            \PDO::ATTR_ERRMODE            => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES   => false,
            \PDO::ATTR_TIMEOUT            => 5,
        ]);
    }



    public function __destruct()
    {
        $this->pk_conn  = null;
    }




    /**
     * Avvolge un identificatore SQL tra backtick (`), es.: id -> `id`
     * ATTENZIONE: passare qui dentro SOLO identificatori già sanificati con pk_sanitize_campo_db()
     */
    private static function bt(string $id): string
    {
        return '`' . $id . '`';
    }

    /**
     * Seleziona uno o più campi da una tabella con un semplice WHERE = :v.
     *
     * Sicurezza:
     *  - I N O M I (tabella/colonne) vengono sanificati con pk_sanitize_campo_db() + backtick.
     *  - I V A L O R I vengono bindati con :param (PDO), non concatenati.
     *
     * Retro-compatibilità:
     *  - Il vecchio parametro $param (stringa libera tipo "ORDER BY ... LIMIT ...")
     *    viene ancora accettato, ma "parsato" in modo whitelist per estrarne SOLO:
     *      • ORDER BY <col> [ASC|DESC]
     *      • GROUP BY <col>
     *      • LIMIT <int>
     *      • OFFSET <int>
     *    Altre parole/frammenti nel vecchio $param vengono IGNORATI per sicurezza.
     *
     * Parametri "nuovo stile" (consigliati):
     *  - $limit (int), $orderBy (string), $orderDir ('ASC'|'DESC'), $offset (int), $groupBy (string)
     *    Questi sono tipizzati e sicuri: usa questi nei nuovi sviluppi.
     *
     * Comportamento di ritorno:
     *  - Se $return === null  => restituisce il PDOStatement (compatibilità con tuo codice esistente).
     *    (Ricorda: in questo caso lo statement resta aperto, va "consumato" o chiuso dal chiamante.)
     *  - Se $return !== null  => richiama TOOL_PK::pk_fetch($stmt, $return), chiude lo statement e
     *    restituisce già i dati (array, oggetto, ecc. a seconda della tua implementazione di pk_fetch).
     */
    public function pk_select(
        array $array_field,        // es. ['id', 'nome'] oppure ['*']
        string $where,             // es. 'id_album'
        mixed $value,             // es. '42' (valore per WHERE colonna = :v)
        ?string $return = null,    // es. 'FETCH_ASSOC' oppure null per avere lo statement
        string $param = '',        // (retro-compat) es. 'ORDER BY id DESC LIMIT 10' — verrà parsato in whitelist
        ?int $limit = null,        // nuovo stile: LIMIT tipizzato
        ?string $orderBy = null,   // nuovo stile: ORDER BY (colonna)
        string $orderDir = 'ASC',  // nuovo stile: direzione ORDER BY (ASC|DESC)
        ?int $offset = null,       // nuovo stile: OFFSET tipizzato
        ?string $groupBy = null    // nuovo stile: GROUP BY (colonna)
    ) {
        // =========================================================
        // (0) PARSING RETRO-COMPAT DEL VECCHIO $param (SE PASSATO)
        // =========================================================
        // ESTRAGGO SOLO QUELLO CHE È SICURO E PREVISTO:
        //   ORDER BY <col> [ASC|DESC]
        //   GROUP BY <col>
        //   LIMIT <int>
        //   OFFSET <int>
        // TUTTO IL RESTO VIENE IGNORATO (non vogliamo concatenare SQL arbitrario).
        if ($param) {
            // ORDER BY
            if (preg_match('/ORDER\s+BY\s+([A-Za-z0-9_]+)(?:\s+(ASC|DESC))?/i', $param, $m)) {
                // Se non già impostato via argomento tipizzato, prendo dal $param
                $orderBy  = $orderBy  ?: $m[1];
                // Direzione se presente, altrimenti mantengo quella passata
                $orderDir = isset($m[2]) ? strtoupper($m[2]) : $orderDir;
            }
            // GROUP BY
            if (preg_match('/GROUP\s+BY\s+([A-Za-z0-9_]+)/i', $param, $m)) {
                $groupBy = $groupBy ?: $m[1];
            }
            // LIMIT
            if (preg_match('/LIMIT\s+(\d+)/i', $param, $m)) {
                $limit = $limit ?? (int)$m[1];
            }
            // OFFSET
            if (preg_match('/OFFSET\s+(\d+)/i', $param, $m)) {
                $offset = $offset ?? (int)$m[1];
            }
            // Qualsiasi altro frammento nel vecchio $param viene volutamente ignorato.
        }

        // ========================================
        // (1) COSTRUZIONE LISTA CAMPI (SELECT ...)
        // ========================================
        // Caso speciale: SELECT * (ammesso SOLO se l’array è esattamente ['*'])
        // Non si backtickka l’asterisco.
        if (count($array_field) === 1 && $array_field[0] === '*') {
            $cols = '*';
        } else {
            // Per ogni campo richiesto, sanifico e backtickko l’identificatore
            $cols = implode(',', array_map(function ($c) {
                return self::bt(self::pk_sanitize_campo_db($c));
            }, $array_field));
        }

        // ========================================
        // (2) SANIFICO TABELLA E COLONNA WHERE
        // ========================================
        // N.B.: $this->table deve essere un nome tabella valido (se proviene da sessione/utente, validarla a monte!)
        $table    = self::bt(self::pk_sanitize_campo_db($this->table));
        $whereCol = self::bt(self::pk_sanitize_campo_db($where));

        // ========================================
        // (3) SQL BASE (senza clausole opzionali)
        // ========================================
        $sql = "SELECT $cols FROM $table WHERE $whereCol = :v";

        // ========================================
        // (4) CLAUSOLE OPZIONALI (sicure e tipizzate)
        // ========================================
        // GROUP BY (se presente): sanifico identificatore
        if ($groupBy !== null && $groupBy !== '') {
            $gb  = self::bt(self::pk_sanitize_campo_db($groupBy));
            $sql .= " GROUP BY $gb";
        }

        // ORDER BY (se presente): sanifico identificatore + whitelist direzione
        if ($orderBy !== null && $orderBy !== '') {
            $ob  = self::bt(self::pk_sanitize_campo_db($orderBy));
            $dir = (strtoupper($orderDir) === 'DESC') ? 'DESC' : 'ASC'; // whitelist su ASC/DESC
            $sql .= " ORDER BY $ob $dir";
        }

        // LIMIT / OFFSET (se presenti): forzo a interi
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        // ========================================
        // (5) PREPARO, BINDO, ESEGUO
        // ========================================
        $stmt = $this->pk_conn->prepare($sql);
        // Il VALORE per la clausola WHERE viene sempre bindato (mai concatenato)
        $stmt->bindValue(':v', $value);
        $stmt->execute();

        // ========================================
        // (6) RITORNO: COMPATIBILITÀ O FETCH IMMEDIATO
        // ========================================
        if ($return === null) {
            // Compat: restituisco lo statement aperto (attenzione a chiuderlo poi!)
            return $stmt;
        } else {
            // Uso il tuo helper per ottenere l’array/forma desiderata
            $out  = DB_PK::pk_fetch($stmt, $return);
            // CHIUDO SEMPRE lo statement (evita leak di risorse)
            $stmt = null;
            return $out;
        }
    }


    /**
     * Seleziona uno o più campi con WHERE <colonna> LIKE :v (con wildcard automatiche).
     *
     * Sicurezza:
     *  - Identificatori (tabella/colonne) sanificati con pk_sanitize_campo_db() + backtick.
     *  - Valori SEMPRE bindati (uso placeholder :v) — mai concatenati.
     *
     * Retro-compat:
     *  - $param (string) accettato ma parsato in whitelist per estrarre SOLO:
     *      • ORDER BY <col> [ASC|DESC]
     *      • GROUP BY <col>
     *      • LIMIT <int>
     *      • OFFSET <int>
     *    Altri frammenti ignorati.
     *
     * Parametri tipizzati:
     *  - $limit, $orderBy, $orderDir, $offset, $groupBy
     *
     * Extra:
     *  - $position definisce dove mettere il valore rispetto ai wildcard:
     *      'any'   → '%val%'   (default, contiene)
     *      'left'  → '%val'    (termina con)
     *      'right' → 'val%'    (inizia con)
     */
    public function pk_select_like(
        array $array_field,         // es. ['id','nome'] o ['*']
        string $where,              // colonna su cui applicare LIKE
        string $value,              // valore da cercare
        ?string $return = null,
        string $param = '',         // legacy: verrà parsato (ORDER/GROUP/LIMIT/OFFSET)
        ?int $limit = null,
        ?string $orderBy = null,
        string $orderDir = 'ASC',
        ?int $offset = null,
        ?string $groupBy = null,
        string $position = 'any'    // 'any' | 'left' | 'right'
    ) {
        // (1) Parsing retro-compat del vecchio $param
        if ($param) {
            if (preg_match('/ORDER\s+BY\s+([A-Za-z0-9_]+)(?:\s+(ASC|DESC))?/i', $param, $m)) {
                $orderBy  = $orderBy ?: $m[1];
                $orderDir = isset($m[2]) ? strtoupper($m[2]) : $orderDir;
            }
            if (preg_match('/GROUP\s+BY\s+([A-Za-z0-9_]+)/i', $param, $m)) {
                $groupBy = $groupBy ?: $m[1];
            }
            if (preg_match('/LIMIT\s+(\d+)/i', $param, $m)) {
                $limit = $limit ?? (int)$m[1];
            }
            if (preg_match('/OFFSET\s+(\d+)/i', $param, $m)) {
                $offset = $offset ?? (int)$m[1];
            }
        }

        // (2) Colonne da SELECT — gestisco ['*'] senza backtick
        if (count($array_field) === 1 && $array_field[0] === '*') {
            $cols = '*';
        } else {
            $cols = implode(',', array_map(fn($c) => self::bt(self::pk_sanitize_campo_db($c)), $array_field));
        }

        // (3) Identificatori sicuri
        $table   = self::bt(self::pk_sanitize_campo_db($this->table));
        $whereCol = self::bt(self::pk_sanitize_campo_db($where));

        // (4) Wildcard per LIKE secondo $position
        switch (strtolower($position)) {
            case 'left':
                $like = '%' . $value;
                break; // ...termina con valore
            case 'right':
                $like = $value . '%';
                break; // inizia con valore...
            default:
                $like = '%' . $value . '%'; // contiene valore (default)
        }

        // (5) SQL base con LIKE
        $sql = "SELECT $cols FROM $table WHERE $whereCol LIKE :v";

        // (6) Clausole opzionali
        if ($groupBy !== null && $groupBy !== '') {
            $gb = self::bt(self::pk_sanitize_campo_db($groupBy));
            $sql .= " GROUP BY $gb";
        }
        if ($orderBy !== null && $orderBy !== '') {
            $ob  = self::bt(self::pk_sanitize_campo_db($orderBy));
            $dir = (strtoupper($orderDir) === 'DESC') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $ob $dir";
        }
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        // (7) Esecuzione
        $stmt = $this->pk_conn->prepare($sql);
        $stmt->bindValue(':v', $like);
        $stmt->execute();

        // (8) Ritorno
        if ($return === null) {
            return $stmt; // compat
        } else {
            $out  = self::pk_fetch($stmt, $return);
            $stmt = null;
            return $out;
        }
    }




    /**
     * Seleziona TUTTI i record dalla tabella corrente.
     *
     * Sicurezza:
     *  - I nomi di tabella/colonne sono sanificati con pk_sanitize_campo_db() e backtickati.
     *  - Niente concatenazioni “libere”: ORDER BY / LIMIT / OFFSET sono parametri tipizzati.
     *
     * Parametri:
     *  - $field   string  Elenco colonne separato da virgola, oppure "*" (default).
     *  - $return  ?string Se null → ritorna PDOStatement (compatibilità); altrimenti usa TOOL_PK::pk_fetch.
     *  - $limit   ?int    LIMIT N (opzionale).
     *  - $offset  ?int    OFFSET N (opzionale; ha senso solo se c’è un LIMIT).
     *  - $orderBy ?string Nome colonna per ORDER BY (opzionale).
     *  - $orderDir string Direzione 'ASC' | 'DESC' (default 'ASC').
     *
     * Note:
     *  - Evito il vecchio $param “libero” per prevenire injection.
     *  - Se passi colonne come stringa “a, b, c”, vengono sanificate singolarmente.
     */
    public function pk_select_all(
        string $field = '*',
        ?string $return = null,
        ?int $limit = null,
        ?int $offset = null,
        ?string $orderBy = null,
        string $orderDir = 'ASC'
    ) {
        // ---------------------------------------------
        // (1) Costruzione lista colonne (SELECT ...)
        // ---------------------------------------------
        $field = trim($field);

        if ($field === '*') {
            // Caso speciale: SELECT * (non si backtickka l’asterisco)
            $cols = '*';
        } else {
            // Divido per virgola, trimmo, filtro vuoti, sanifico e backtickko ogni colonna
            $parts = array_filter(array_map('trim', explode(',', $field)), fn($s) => $s !== '');
            if (empty($parts)) {
                // Se dopo il parsing non resta nulla, ripiego su *
                $cols = '*';
            } else {
                $cols = implode(',', array_map(function ($c) {
                    return self::bt(self::pk_sanitize_campo_db($c));
                }, $parts));
            }
        }

        // ---------------------------------------------
        // (2) Sanifico il nome della tabella corrente
        // ---------------------------------------------
        $table = self::bt(self::pk_sanitize_campo_db($this->table));

        // ---------------------------------------------
        // (3) SQL base
        // ---------------------------------------------
        $sql = "SELECT $cols FROM $table";

        // ---------------------------------------------
        // (4) ORDER BY (opzionale, sicuro)
        // ---------------------------------------------
        if ($orderBy !== null && $orderBy !== '') {
            $ob  = self::bt(self::pk_sanitize_campo_db($orderBy));
            $dir = (strtoupper($orderDir) === 'DESC') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $ob $dir";
        }

        // ---------------------------------------------
        // (5) LIMIT / OFFSET (opzionali, tipizzati)
        // ---------------------------------------------
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        // ---------------------------------------------
        // (6) Esecuzione
        // ---------------------------------------------
        $stmt = $this->pk_conn->prepare($sql);
        $stmt->execute();

        // ---------------------------------------------
        // (7) Ritorno: compatibilità oppure fetch + chiusura
        // ---------------------------------------------
        if ($return === null) {
            // Compat: ritorno lo statement (chiusura a carico del chiamante)
            return $stmt;
        } else {
            $out  = self::pk_fetch($stmt, $return);
            $stmt = null; // chiudo subito per evitare leak
            return $out;
        }
    }





    /**
     * Seleziona uno o più campi con DUE condizioni WHERE in AND.
     *
     * Sicurezza:
     *  - I nomi (tabella/colonne) sono sanificati con pk_sanitize_campo_db() e backtickati con bt().
     *  - I valori vengono SEMPRE bindati (mai concatenati).
     *
     * Retro-compatibilità:
     *  - Il vecchio $param (es. "ORDER BY x DESC LIMIT 10") è ancora accettato ma
     *    viene "parsato" in whitelist per estrarre SOLO:
     *      • ORDER BY <col> [ASC|DESC]
     *      • GROUP BY <col>
     *      • LIMIT <int>
     *      • OFFSET <int>
     *    Qualunque altro frammento viene ignorato per sicurezza.
     *
     * Parametri “nuovo stile” (consigliati):
     *  - $limit, $orderBy, $orderDir, $offset, $groupBy — tipizzati e sicuri.
     *
     * Ritorno:
     *  - Se $return === null  → ritorna il PDOStatement (compatibilità; ricordati di chiuderlo).
     *  - Se $return !== null  → fa TOOL_PK::pk_fetch(...), chiude lo statement e ritorna i dati.
     */
    public function pk_select_2where(
        array $array_field,          // es. ['id_album','nome'] oppure ['*']
        array $where,                // es. ['stato','id_operatore']
        array $value,                // es. ['attivo','42']
        ?string $return = null,      // es. 'FETCH_ASSOC' oppure null per avere lo statement
        string $param = '',          // (legacy) "ORDER BY ... LIMIT ...", verrà parsato in whitelist
        ?int $limit = null,          // nuovo stile: LIMIT N
        ?string $orderBy = null,     // nuovo stile: ORDER BY colonna
        string $orderDir = 'ASC',    // nuovo stile: ASC|DESC (whitelist)
        ?int $offset = null,         // nuovo stile: OFFSET N
        ?string $groupBy = null      // nuovo stile: GROUP BY colonna
    ) {
        // =========================================================
        // (0) VALIDAZIONE MINIMA SUGLI ARRAY WHERE/VALUES
        // =========================================================
        // Mi aspetto esattamente 2 colonne e 2 valori.
        if (count($where) < 2 || count($value) < 2) {
            throw new \InvalidArgumentException('pk_select_2where richiede due campi WHERE e due valori.');
        }

        // =========================================================
        // (1) PARSING RETRO-COMPAT DEL VECCHIO $param (SE PASSATO)
        //     Estraggo SOLO ORDER BY / GROUP BY / LIMIT / OFFSET
        // =========================================================
        if ($param) {
            if (preg_match('/ORDER\s+BY\s+([A-Za-z0-9_]+)(?:\s+(ASC|DESC))?/i', $param, $m)) {
                $orderBy  = $orderBy ?: $m[1];
                $orderDir = isset($m[2]) ? strtoupper($m[2]) : $orderDir;
            }
            if (preg_match('/GROUP\s+BY\s+([A-Za-z0-9_]+)/i', $param, $m)) {
                $groupBy = $groupBy ?: $m[1];
            }
            if (preg_match('/LIMIT\s+(\d+)/i', $param, $m)) {
                $limit = $limit ?? (int)$m[1];
            }
            if (preg_match('/OFFSET\s+(\d+)/i', $param, $m)) {
                $offset = $offset ?? (int)$m[1];
            }
            // qualunque altro pezzo nel vecchio $param viene ignorato
        }

        // =========================================================
        // (2) COSTRUZIONE LISTA COLONNE (SELECT ...)
        //     Gestisco il caso speciale ['*'] senza backtick
        // =========================================================
        if (count($array_field) === 1 && $array_field[0] === '*') {
            $cols = '*';
        } else {
            $cols = implode(',', array_map(function ($c) {
                return self::bt(self::pk_sanitize_campo_db($c));
            }, $array_field));
        }

        // =========================================================
        // (3) SANIFICO NOMI TABELLA E COLONNE WHERE
        // =========================================================
        $table  = self::bt(self::pk_sanitize_campo_db($this->table));
        $w0_col = self::bt(self::pk_sanitize_campo_db($where[0]));
        $w1_col = self::bt(self::pk_sanitize_campo_db($where[1]));

        // =========================================================
        // (4) COSTRUISCO LA QUERY BASE
        // =========================================================
        $sql = "SELECT $cols FROM $table WHERE ($w0_col = :v0 AND $w1_col = :v1)";

        // =========================================================
        // (5) CLAUSOLE OPZIONALI (GROUP BY / ORDER BY / LIMIT / OFFSET)
        // =========================================================
        if ($groupBy !== null && $groupBy !== '') {
            $gb = self::bt(self::pk_sanitize_campo_db($groupBy));
            $sql .= " GROUP BY $gb";
        }

        if ($orderBy !== null && $orderBy !== '') {
            $ob  = self::bt(self::pk_sanitize_campo_db($orderBy));
            $dir = (strtoupper($orderDir) === 'DESC') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $ob $dir";
        }

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        // =========================================================
        // (6) PREPARO, BINDO E ESEGUO LA QUERY
        // =========================================================
        $stmt = $this->pk_conn->prepare($sql);
        $stmt->bindValue(':v0', $value[0]); // bind sicuri, nessuna concatenazione
        $stmt->bindValue(':v1', $value[1]);
        $stmt->execute();

        // =========================================================
        // (7) RITORNO: COMPATIBILITÀ O FETCH + CHIUSURA STATEMENT
        // =========================================================
        if ($return === null) {
            // Compatibilità: ritorno lo statement (chiuderlo sarà responsabilità del chiamante)
            return $stmt;
        } else {
            $out  = self::pk_fetch($stmt, $return);
            $stmt = null; // chiudo subito per evitare leak
            return $out;
        }
    }


    /**
     * Seleziona uno o più campi con TRE condizioni WHERE in AND.
     *
     * Sicurezza:
     *  - Identificatori (tabella/colonne) sanificati con pk_sanitize_campo_db() + backtick.
     *  - Valori SEMPRE bindati (:v0, :v1, :v2) — mai concatenati.
     *
     * Retro-compat:
     *  - $param (string) accettato ma parsato in whitelist per estrarre SOLO:
     *      • ORDER BY <col> [ASC|DESC]
     *      • GROUP BY <col>
     *      • LIMIT <int>
     *      • OFFSET <int>
     *    Tutto il resto viene ignorato.
     *
     * Parametri tipizzati consigliati:
     *  - $limit, $orderBy, $orderDir, $offset, $groupBy
     *
     * Ritorno:
     *  - $return === null  → restituisce PDOStatement (compat; chiudi tu lo statement)
     *  - $return !== null  → fa pk_fetch(), chiude statement e restituisce i dati
     */
    public function pk_select_3where(
        array $array_field,         // es. ['id','nome'] o ['*']
        array $where,               // es. ['stato','id_operatore','anno']
        array $value,               // es. ['attivo','42','2025']
        ?string $return = null,
        string $param = '',         // legacy: "ORDER BY ... LIMIT ...", viene parsato in whitelist
        ?int $limit = null,
        ?string $orderBy = null,
        string $orderDir = 'ASC',
        ?int $offset = null,
        ?string $groupBy = null
    ) {
        // (0) Validazione minima
        if (count($where) < 3 || count($value) < 3) {
            throw new \InvalidArgumentException('pk_select_3where richiede tre campi WHERE e tre valori.');
        }

        // (1) Parsing retro-compat del vecchio $param
        if ($param) {
            if (preg_match('/ORDER\s+BY\s+([A-Za-z0-9_]+)(?:\s+(ASC|DESC))?/i', $param, $m)) {
                $orderBy  = $orderBy ?: $m[1];
                $orderDir = isset($m[2]) ? strtoupper($m[2]) : $orderDir;
            }
            if (preg_match('/GROUP\s+BY\s+([A-Za-z0-9_]+)/i', $param, $m)) {
                $groupBy = $groupBy ?: $m[1];
            }
            if (preg_match('/LIMIT\s+(\d+)/i', $param, $m)) {
                $limit = $limit ?? (int)$m[1];
            }
            if (preg_match('/OFFSET\s+(\d+)/i', $param, $m)) {
                $offset = $offset ?? (int)$m[1];
            }
        }

        // (2) Colonne da SELECT — gestisco ['*'] senza backtick
        if (count($array_field) === 1 && $array_field[0] === '*') {
            $cols = '*';
        } else {
            $cols = implode(',', array_map(fn($c) => self::bt(self::pk_sanitize_campo_db($c)), $array_field));
        }

        // (3) Identificatori sicuri
        $table  = self::bt(self::pk_sanitize_campo_db($this->table));
        $w0_col = self::bt(self::pk_sanitize_campo_db($where[0]));
        $w1_col = self::bt(self::pk_sanitize_campo_db($where[1]));
        $w2_col = self::bt(self::pk_sanitize_campo_db($where[2]));

        // (4) SQL base
        $sql = "SELECT $cols FROM $table WHERE ($w0_col = :v0 AND $w1_col = :v1 AND $w2_col = :v2)";

        // (5) Clausole opzionali
        if ($groupBy !== null && $groupBy !== '') {
            $gb = self::bt(self::pk_sanitize_campo_db($groupBy));
            $sql .= " GROUP BY $gb";
        }
        if ($orderBy !== null && $orderBy !== '') {
            $ob  = self::bt(self::pk_sanitize_campo_db($orderBy));
            $dir = (strtoupper($orderDir) === 'DESC') ? 'DESC' : 'ASC';
            $sql .= " ORDER BY $ob $dir";
        }
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        // (6) Esecuzione
        $stmt = $this->pk_conn->prepare($sql);
        $stmt->bindValue(':v0', $value[0]);
        $stmt->bindValue(':v1', $value[1]);
        $stmt->bindValue(':v2', $value[2]);
        $stmt->execute();

        // (7) Ritorno
        if ($return === null) {
            return $stmt; // compat
        } else {
            $out  = self::pk_fetch($stmt, $return);
            $stmt = null;
            return $out;
        }
    }


    /**
     * Seleziona campi con 1–3 WHERE, ordinando e limitando i risultati.
     *
     * Retro-compat:
     *  - $quantity  → usato come LIMIT
     *  - $asc_desc  → direzione ORDER BY ('ASC'|'DESC')
     *  - Se $orderBy è nullo, prova a usare il primo campo di $array_field (se non è '*')
     *
     * Nuovi parametri:
     *  - $orderBy  : colonna per ORDER BY (sanificata)
     *  - $offset   : OFFSET (opzionale)
     *  - $groupBy  : GROUP BY (opzionale, sanificato)
     *
     * Ritorno:
     *  - $return === null  → PDOStatement (chiuderlo è responsabilità del chiamante)
     *  - $return !== null  → pk_fetch + chiusura statement
     */
    public function pk_select_order(
        array $array_field,           // es. ['id', 'nome'] oppure ['*']
        int $quantity,                // retro: LIMIT
        array $array_where,           // 1..3 colonne per WHERE
        array $array_value,           // 1..3 valori per WHERE (ordine corrispondente)
        string $asc_desc = 'ASC',     // retro: direzione
        ?string $return = null,       // retro: 'FETCH_ASSOC' | null
        ?string $orderBy = null,      // nuovo: colonna per ORDER BY
        ?int $offset = null,          // nuovo: OFFSET
        ?string $groupBy = null       // nuovo: GROUP BY
    ) {
        // ---------------------------
        // (0) Validazioni minime
        // ---------------------------
        $wc = count($array_where);
        $vc = count($array_value);
        if ($wc < 1 || $wc > 3 || $vc !== $wc) {
            throw new \InvalidArgumentException('pk_select_order richiede 1–3 campi WHERE e lo stesso numero di valori.');
        }

        // ---------------------------
        // (1) Colonne SELECT
        // ---------------------------
        if (count($array_field) === 1 && $array_field[0] === '*') {
            $cols = '*';
        } else {
            $cols = implode(',', array_map(fn($c) => self::bt(self::pk_sanitize_campo_db($c)), $array_field));
        }

        // ---------------------------
        // (2) Identificatori sicuri
        // ---------------------------
        $table = self::bt(self::pk_sanitize_campo_db($this->table));

        // WHERE columns sanificate
        $whereCols = [];
        for ($i = 0; $i < $wc; $i++) {
            $whereCols[$i] = self::bt(self::pk_sanitize_campo_db($array_where[$i]));
        }

        // ---------------------------
        // (3) SQL base + WHERE dinamico
        // ---------------------------
        $sql = "SELECT $cols FROM $table WHERE ";
        $parts = [];
        for ($i = 0; $i < $wc; $i++) {
            $parts[] = "{$whereCols[$i]} = :w{$i}";
        }
        $sql .= '(' . implode(' AND ', $parts) . ')';

        // ---------------------------
        // (4) GROUP BY (opzionale)
        // ---------------------------
        if ($groupBy !== null && $groupBy !== '') {
            $gb = self::bt(self::pk_sanitize_campo_db($groupBy));
            $sql .= " GROUP BY $gb";
        }

        // ---------------------------
        // (5) ORDER BY (tipizzato)
        // ---------------------------
        // Se non passi $orderBy: provo a usare il primo campo (se non '*')
        if ($orderBy === null || $orderBy === '') {
            if ($cols !== '*') {
                // prendo il primo campo originale (non quello sanificato già unito)
                $first = $array_field[0] ?? null;
                if ($first && $first !== '*') {
                    $orderBy = $first;
                }
            }
        }
        if ($orderBy !== null && $orderBy !== '') {
            $ob  = self::bt(self::pk_sanitize_campo_db($orderBy));
            $dir = (strtoupper($asc_desc) === 'DESC') ? 'DESC' : 'ASC'; // whitelist direzione
            $sql .= " ORDER BY $ob $dir";
        }

        // ---------------------------
        // (6) LIMIT / OFFSET
        // ---------------------------
        $sql .= " LIMIT " . (int)$quantity;
        if ($offset !== null) {
            $sql .= " OFFSET " . (int)$offset;
        }

        // ---------------------------
        // (7) Esecuzione
        // ---------------------------
        $stmt = $this->pk_conn->prepare($sql);
        for ($i = 0; $i < $wc; $i++) {
            $stmt->bindValue(":w{$i}", $array_value[$i]);
        }
        $stmt->execute();

        // ---------------------------
        // (8) Ritorno
        // ---------------------------
        if ($return === null) {
            return $stmt; // compat: statement aperto
        } else {
            $out  = self::pk_fetch($stmt, $return);
            $stmt = null;
            return $out;
        }
    }




    public function pk_select_distinct(string $field, ?string $return = NULL)
    {

        $this->pk_sanitizie_name_column($field);
        $result = $this->pk_conn->prepare("SELECT DISTINCT $field FROM $this->table ORDER BY $field ASC");
        $result->execute();

        if (!empty($result)) {
            if ($return === NULL) {
                return $result;
            } else {
                return self::pk_fetch($result, $return);
            }
        } else {
            $result = array();
            return $result;
        }
    }


    public function pk_select_innerjoin($field, $comparison_value, $table_1, $table_2, $where, $value, $return = NULL)
    {


        $result = $this->pk_conn->prepare("SELECT $field FROM $table_1 INNER JOIN $table_2 ON $table_1.$comparison_value=$table_2.$comparison_value WHERE $where = $value ");
        $result->execute();

        if (!empty($result)) {
            if ($return === NULL) {
                return $result;
            } else {
                return self::pk_fetch($result, $return);
            }
        } else {
            $result = array();
            return $result;
        }
    }

    /*  inserisco un nuovo record con i suoi vari campi */
    public function pk_insert(array $array_fields, array $array_values)
    {
        // 0) GUARDRAIL: insert di una riga => campi e valori devono combaciare
        if (empty($array_fields)) {
            LOG_PK::debug(
                'pk_insert: empty fields array',
                ['table' => $this->table]
            );
            return false;
        }

        if (count($array_fields) !== count($array_values)) {

            LOG_PK::debug(
                'pk_insert: fields/values mismatch',
                [
                    'table'  => $this->table,
                    'fields' => count($array_fields),
                    'values' => count($array_values)
                ]
            );
            return false;
        }


        $array_param = array();
        $count_array_fields = count($array_fields);
        for ($i = 0; $i < $count_array_fields; $i++) {
            $array_param[$i] = ':' . $array_fields[$i];
        }
        $string_fields = implode(",", $array_fields);
        $string_param = implode(",", $array_param);
        $insert = $this->pk_conn->prepare("INSERT INTO $this->table ($string_fields) VALUES ($string_param) ");
        $count_array_param = count($array_param);
        for ($i = 0; $i < $count_array_param; $i++) {
            $insert->bindparam($array_param[$i], $array_values[$i]);
        }
        $result = $insert->execute();

        if ($result) {
            return $this->pk_conn->lastInsertId(); // id appena inserito
        }

        return false;
    }


    public function pk_update(string $field, mixed $value, string $where_field, mixed $where_value)
    {

        $sql = "UPDATE $this->table SET $field=:field_param WHERE $where_field = :where_param";
        $insert = $this->pk_conn->prepare($sql);
        $insert->bindparam(':field_param', $value);
        $insert->bindparam(':where_param', $where_value);
        return $insert->execute(); // TRUE se ok, FALSE se errore

    }



    public function pk_delete(string $where, string $value)
    {
        $param = ':' . $where;
        $delete = $this->pk_conn->prepare("DELETE FROM $this->table WHERE $where = $param");
        $delete->bindParam($param, $value);
        $delete->execute();
    }

    public function pk_delete_2where(array $where, array $value)
    {
        $this->where = $where;
        $this->value = $value;

        $delete = $this->pk_conn->prepare("DELETE FROM $this->table WHERE ({$this->where[0]}= :value0 AND {$this->where[1]}= :value1 )");
        $delete->bindParam(':value0', $this->value[0]);
        $delete->bindParam(':value1', $this->value[1]);
        $delete->execute();
    }


    public function pk_create_table(string $name_table)
    {

        $name_id = 'id_' . $name_table;
        $create = $this->pk_conn->prepare("CREATE TABLE $name_table ( $name_id INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ($name_id), `last_edit` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE = InnoDB;");
        if ($create->execute()) {
            echo "Tabella album creata con successo";
        } else {
            die("Errore di creazione");
        }

        $this->pk_insert(array($name_id), array(1));
    }


    public function pk_remove_column(string $name_column)
    {

        $create = $this->pk_conn->prepare("ALTER TABLE $this->table  DROP $name_column  ;");
        $create->execute();
    }

    public function pk_move_column(string $name_column, string $after_column, string $tipe_column)
    {

        $create = $this->pk_conn->prepare("ALTER TABLE $this->table  MODIFY $name_column  $tipe_column AFTER $after_column; ");
        $create->execute();
    }

    public function pk_create_column(
        string $name_table,
        string $name_new_column,
        string $type_new_column,
        string $length_new_column,
        string $predefinito_new_column = '',
        string $codifica_new_column = '',
        string $attr_new_column = '',
        string $null_new_column = 'NOT NULL'
    ) {

        $name_sanitized = SAFE_PK::pk_sanitizie_name_column($name_new_column); /* sanitizare diversamente da html */
        $type_sanitized = SAFE_PK::pk_sanitize_var($type_new_column);
        $length_sanitized = SAFE_PK::pk_sanitize_int($length_new_column);
        $predefinito_sanitized = SAFE_PK::pk_sanitize_var($predefinito_new_column);
        $attr_sanitized = SAFE_PK::pk_sanitize_var($attr_new_column);
        $null_satized = SAFE_PK::pk_sanitize_var($null_new_column);
        $codifica_sanitized = SAFE_PK::pk_sanitize_var($codifica_new_column);

        /* faccio un controllo della codifica e la trasformo nell'informazione che serve al mysql */
        if ($codifica_sanitized === 'utf8mb4_unicode_ci') {
            $codifica_sanitized = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        } elseif ($codifica_sanitized === 'utf8mb4_general_ci') {
            $codifica_sanitized = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';
        } else {
            $codifica_sanitized = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE VARCHAR */
        if ($type_sanitized === 'TEXT (varchar)') {
            $type_sanitized = 'VARCHAR';
            if ($attr_sanitized === 'COMPRESSED=zlib') {
                $attr_sanitized = 'COMPRESSED=zlib';
            } else {
                $attr_sanitized = '';
            }

            if ($length_sanitized === '') {
                $length_sanitized = '(80)';
            } else {
                $length_sanitized = '(' . $length_sanitized . ')';
            }

            if ($predefinito_sanitized == 'CURRENT_TIMESTAMP') {
                $predefinito_sanitized = '';
            } elseif ($predefinito_sanitized == 'NULL') {
                $predefinito_sanitized = 'DEFAULT NULL';
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE VARCHAR */
        if ($type_sanitized === 'TEXT (char dimensioni fisse)') {
            $type_sanitized = 'CHAR';
            if ($attr_sanitized === 'COMPRESSED=zlib') {
                $attr_sanitized = 'COMPRESSED=zlib';
            } else {
                $attr_sanitized = '';
            }

            if ($length_sanitized === '') {
                $length_sanitized = '(40)';
            } else {
                $length_sanitized = '(' . $length_sanitized . ')';
            }

            if ($predefinito_sanitized == 'CURRENT_TIMESTAMP') {
                $predefinito_sanitized = '';
            } elseif ($predefinito_sanitized == 'NULL') {
                $predefinito_sanitized = 'DEFAULT NULL';
            }
        }

        /*  SE SI SCEGLIE TINYTEXT */
        if ($type_sanitized === 'EMAIL (tinytext)') {
            $type_sanitized = 'TINYTEXT';
            $codifica_sanitized = '';
            $length_sanitized = '';
            if ($attr_sanitized === 'COMPRESSED=zlib') {
                $attr_sanitized = 'COMPRESSED=zlib';
            } else {
                $attr_sanitized = '';
            }
            if ($predefinito_sanitized == 'CURRENT_TIMESTAMP') {
                $predefinito_sanitized = '';
            } elseif ($predefinito_sanitized == 'NULL') {
                $predefinito_sanitized = 'DEFAULT NULL';
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE LONG TEXT */
        if ($type_sanitized === 'LONGTEXT') {
            if ($attr_sanitized === 'COMPRESSED=zlib') {
                $attr_sanitized = 'COMPRESSED=zlib';
            } else {
                $attr_sanitized = '';
            }

            $length_sanitized = '';

            if ($predefinito_sanitized == 'CURRENT_TIMESTAMP') {
                $predefinito_sanitized = '';
            } elseif ($predefinito_sanitized == 'NULL') {
                $predefinito_sanitized = 'DEFAULT NULL';
            }
        }
        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE INT */
        if ($type_sanitized === 'NUMBER (int)') {
            $type_sanitized = 'INT';
            $codifica_sanitized = '';
            if ($length_sanitized === '') {
                $length_sanitized = '(11)';
            } else {
                $length_sanitized = '(' . $length_sanitized . ')';
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE INT */
        if ($type_sanitized === 'NUMBER (float)') {
            $type_sanitized = 'FLOAT';
            $codifica_sanitized = '';
            $length_sanitized = '';
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE id BIGINT */
        if ($type_sanitized === 'ID (bigint)') {
            $type_sanitized = 'BIGINT';
            $codifica_sanitized = '';
            if ($length_sanitized === '') {
                $length_sanitized = '(30)';
            } else {
                $length_sanitized = '(' . $length_sanitized . ')';
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE DATE */
        if ($type_sanitized === 'DATE') {
            $length_sanitized = '';
            $codifica_sanitized = '';
            $attr_sanitized = '';
            switch ($predefinito_sanitized) {
                case 'CURRENT_TIMESTAMP':
                    $predefinito_sanitized = 'DEFAULT CURRENT_TIMESTAMP';
                    break;
                case 'NULL':
                    $predefinito_sanitized = '';
                    break;
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE DATE */
        if ($type_sanitized === 'TIMESTAMP') {
            $length_sanitized = '';
            $codifica_sanitized = '';
            $attr_sanitized = '';
            switch ($predefinito_sanitized) {
                case '':
                    $predefinito_sanitized = 'DEFAULT CURRENT_TIMESTAMP';
                    break;
                case 'CURRENT_TIMESTAMP':
                    $predefinito_sanitized = 'DEFAULT CURRENT_TIMESTAMP';
                    break;
                case 'NULL':
                    $predefinito_sanitized = '';
                    break;
            }
        }

        /*   SE SI SCEGLIE PASSWORD */
        if ($type_sanitized === 'PASSWORD') {
            $type_sanitized = 'CHAR';
            $attr_sanitized = '';
            $length_sanitized = '(255)';

            if ($predefinito_sanitized == 'CURRENT_TIMESTAMP') {
                $predefinito_sanitized = '';
            } elseif ($predefinito_sanitized == 'NULL') {
                $predefinito_sanitized = 'DEFAULT NULL';
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE BOOLEAN */
        if ($type_sanitized === 'BOOLEAN') {
            $type_sanitized = 'TINYINT';
            $codifica_sanitized = '';
            $length_sanitized = '(1)';
        }



        $create = $this->pk_conn->prepare("ALTER TABLE $name_table ADD $name_sanitized  
        $type_sanitized $length_sanitized
        $codifica_sanitized
        $attr_sanitized
        $null_satized 
        $predefinito_sanitized  ;");
        try {
            ($create->execute());
            echo "";
        } catch (\PDOException $e) {
            die('Errore la colonna non è stata creata: ' . $e->getMessage());
        }
    }

    /* seleziono tutte le info della tabella etichette del db ecc */
    public function pk_select_information_table(string $field, ?string $return = NULL)
    {

        $this->field = $field;
        $result = $this->pk_conn->prepare("SELECT $this->field FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = :table_name ORDER BY ORDINAL_POSITION");
        $result->bindparam(':db_name', $this->db);
        $result->bindparam(':table_name', $this->table);

        $result->execute();

        if (!empty($result)) {
            if ($return === NULL) {
                return $result;
            } else {
                return self::pk_fetch($result, $return);
            }
        } else {
            $result = array();
            return $result;
        }
    }

    /* seleziono una info della tabella etichette del db ecc */
    public function pk_select_one_information_table(string $column_name, ?string $return = NULL)
    {
        $result = $this->pk_conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE (TABLE_SCHEMA = :db_name AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name) ");
        $result->bindparam(':db_name', $this->db);
        $result->bindparam(':table_name', $this->table);
        $result->bindparam(':column_name', $column_name);

        $result->execute();

        if (!empty($result)) {
            if ($return === NULL) {
                return $result;
            } else {
                return self::pk_fetch($result, $return);
            }
        } else {
            $result = array();
            return $result;
        }
    }

    public function pk_drop_table()
    {

        $delete = $this->pk_conn->prepare("DROP TABLE $this->table ");

        $delete->execute();
    }

    public function pk_count(?string $whereCol = null, ?string $whereVal = null): int
    {
        // Sanifico nome tabella e — se presente — la colonna del WHERE
        $table = self::bt(self::pk_sanitize_campo_db($this->table));
        $sql   = "SELECT COUNT(*) AS c FROM $table";

        if ($whereCol !== null) {
            $col = self::bt(self::pk_sanitize_campo_db($whereCol));
            $sql .= " WHERE $col = :w";
        }

        $stmt = $this->pk_conn->prepare($sql);
        if ($whereCol !== null) {
            $stmt->bindValue(':w', $whereVal);
        }
        $stmt->execute();
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt = null;

        return isset($row['c']) ? (int)$row['c'] : 0;
    }



    public function pk_select_one_random(
        array $fields,
        ?string $return = 'FETCH_ASSOC',
        ?string $whereCol = null,
        ?string $whereVal = null
    ) {
        // Costruisco lista campi: consento '*' solo se unico elemento
        if (count($fields) === 1 && $fields[0] === '*') {
            $cols = '*';
        } else {
            $cols = implode(',', array_map(
                fn($c) => self::bt(self::pk_sanitize_campo_db($c)),
                $fields
            ));
        }

        $table = self::bt(self::pk_sanitize_campo_db($this->table));

        // WHERE opzionale
        $whereSql = '';
        if ($whereCol !== null) {
            $col = self::bt(self::pk_sanitize_campo_db($whereCol));
            $whereSql = " WHERE $col = :w";
        }

        // Semplice e leggibile: ok su tabelle non enormi
        $sql = "SELECT $cols FROM $table$whereSql ORDER BY RAND() LIMIT 1";

        $stmt = $this->pk_conn->prepare($sql);
        if ($whereCol !== null) {
            $stmt->bindValue(':w', $whereVal);
        }
        $stmt->execute();

        if ($return === null) return $stmt; // compat: ritorno statement aperto

        $out = self::pk_fetch($stmt, $return);
        $stmt = null;
        return $out ?: []; // tabella vuota ⇒ []
    }


    public function pk_select_one_random_fast(
        array $fields,
        ?string $return = 'FETCH_ASSOC',
        ?string $whereCol = null,
        ?string $whereVal = null
    ) {
        // 1) Conta righe (eventualmente filtrate)
        $count = $this->pk_count($whereCol, $whereVal);
        if ($count <= 0) {
            return $return === null ? null : [];
        }

        // 2) Offset casuale [0 .. count-1]
        $offset = random_int(0, $count - 1);

        // 3) Lista campi
        if (count($fields) === 1 && $fields[0] === '*') {
            $cols = '*';
        } else {
            $cols = implode(',', array_map(
                fn($c) => self::bt(self::pk_sanitize_campo_db($c)),
                $fields
            ));
        }

        $table = self::bt(self::pk_sanitize_campo_db($this->table));

        // WHERE opzionale
        $whereSql = '';
        if ($whereCol !== null) {
            $col = self::bt(self::pk_sanitize_campo_db($whereCol));
            $whereSql = " WHERE $col = :w";
        }

        // 4) SELECT con LIMIT 1 OFFSET X (senza ORDER BY → super veloce)
        $sql = "SELECT $cols FROM $table$whereSql LIMIT 1 OFFSET " . (int)$offset;

        $stmt = $this->pk_conn->prepare($sql);
        if ($whereCol !== null) {
            $stmt->bindValue(':w', $whereVal);
        }
        $stmt->execute();

        if ($return === null) return $stmt;

        $out = self::pk_fetch($stmt, $return);
        $stmt = null;
        return $out ?: [];
    }

    // === TRANSAZIONI & RETRY SU DEADLOCK/LOCK-WAIT =============================

    /** Avvia una transazione solo se non è già attiva. */
    public function begin(): void
    {
        if (!$this->pk_conn->inTransaction()) {
            $this->pk_conn->beginTransaction();
        }
    }

    /** Commit silenzioso (se c’è una transazione attiva). */
    public function commit(): void
    {
        if ($this->pk_conn->inTransaction()) {
            $this->pk_conn->commit();
        }
    }

    /** Rollback silenzioso (ignora eventuali eccezioni nel rollback). */
    public function rollBackQuiet(): void
    {
        if ($this->pk_conn->inTransaction()) {
            try {
                $this->pk_conn->rollBack();
            } catch (\Throwable $e) { /* no-op */
            }
        }
    }

    /**
     * Riconosce errori di concorrenza tipici di MySQL:
     *  - 1213 = Deadlock found
     *  - 1205 = Lock wait timeout
     *  - SQLSTATE '40001' = serialization failure (alcuni motori)
     */
    private static function isConcurrencyError(\PDOException $e): bool
    {
        $errno    = $e->errorInfo[1] ?? null;  // codice MySQL
        $sqlstate = $e->getCode();             // può essere '40001'
        return ($errno === 1213) || ($errno === 1205) || ($sqlstate === '40001');
    }

    /**
     * Esegue $fn DENTRO una transazione, con retry automatico su deadlock/lock-wait.
     *
     * @param callable $fn      funzione con la tua logica; firma: function(DB_PK $db) { ...; return $qualcosa; }
     * @param int      $retries max tentativi (in totale). 3 è un buon default
     * @param int      $sleepMs attesa base tra i retry (usiamo backoff esponenziale + jitter)
     * @param ?callable $onRetry callback opzionale chiamata prima di ogni retry (riceve $attempt, $e)
     *
     * NOTE:
     * - Se è già attiva una transazione ESTERNA, NON ne apre un’altra (niente nested tx)
     *   e NON può fare retry (perché non può ripristinare lo stato correttamente). In quel caso
     *   esegue $fn “al volo” e lascia commit/rollback all’esterno.
     */
    public function runInTransaction(
        callable $fn,
        int $retries = 3,
        int $sleepMs = 50,
        ?callable $onRetry = null
    ) {
        // Se esiste già una transazione esterna, esegui senza toccare il TX e senza retry.
        if ($this->pk_conn->inTransaction()) {
            // N.B.: qui NON possiamo “rifare” in caso di deadlock perché non controlliamo il perimetro del TX.
            return $fn($this);
        }

        $attempt = 0;

        RETRY:
        $attempt++;

        $this->begin();
        try {
            $result = $fn($this);  // esegui la tua logica utente
            $this->commit();
            return $result;
        } catch (\PDOException $e) {
            $this->rollBackQuiet();

            if (self::isConcurrencyError($e) && $attempt < $retries) {
                // Backoff esponenziale + jitter (random) per evitare Thundering Herd
                if ($onRetry) {
                    try {
                        $onRetry($attempt, $e);
                    } catch (\Throwable $t) {
                    }
                }
                $delay = ($sleepMs * $attempt) + random_int(0, 30); // in millisecondi
                usleep($delay * 1000);
                goto RETRY;
            }

            // altri errori o esauriti i tentativi: rilancia
            throw $e;
        } catch (\Throwable $e) {
            $this->rollBackQuiet();
            throw $e;
        }
    }

    /**
     * Esegue in transazione con un ISOLATION LEVEL specifico (opzionale).
     * Utile quando vuoi SERIALIZABLE/REPEATABLE READ solo per un blocco.
     *
     * @param string $isolation 'READ COMMITTED' | 'REPEATABLE READ' | 'SERIALIZABLE'
     */
    public function runInTransactionWithIsolation(
        callable $fn,
        string $isolation = 'REPEATABLE READ',
        int $retries = 3,
        int $sleepMs = 50,
        ?callable $onRetry = null
    ) {
        // Se c’è già una transazione attiva, non possiamo cambiare isolamento: esegui diretto.
        if ($this->pk_conn->inTransaction()) {
            return $fn($this);
        }

        // Imposta l’isolamento per la prossima transazione
        $iso = strtoupper($isolation);
        $this->pk_conn->exec("SET TRANSACTION ISOLATION LEVEL $iso");

        return $this->runInTransaction($fn, $retries, $sleepMs, $onRetry);
    }

    public function pk_select_innerjoin_2where($field, $comparison_value, $table_1, $table_2, $where1, $value1, $where2, $value2, $return = NULL)
    {


        $result = $this->pk_conn->prepare("SELECT $field FROM $table_1 INNER JOIN $table_2 ON $table_1.$comparison_value=$table_2.$comparison_value WHERE ( $where1 = :value1 AND $where2 = :value2 )");
        $result->bindparam(":value1", $value1);
        $result->bindparam(":value2", $value2);
        $result->execute();


        if (!empty($result)) {
            if ($return === NULL) {

                return $result;
            } else {

                return self::pk_fetch($result, $return);
            }
        } else {
            $result = array();
            return $result;
        }
    }
}
