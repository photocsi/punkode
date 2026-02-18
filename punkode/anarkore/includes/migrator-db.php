<?php

namespace Punkode;

class MIGRATOR_PK extends DB_PK
{

    public function __construct(string $table, int $conn = 1)
    {
        // Inizializza SETUP_PK + DB_PK
        parent::__construct($table, $conn);

        // Qui NON fai altro per ora.
        // $this->pk_conn è già un PDO valido.
        // $this->table = $table è già impostata da DB_PK.
    }


    public function pk_column_exists(string $columnName): bool
    {
        // 1) SQL che chiede a INFORMATION_SCHEMA se esiste quella colonna
        $sql = "
            SELECT COUNT(*) AS campo_trovato
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = :db
              AND TABLE_NAME   = :table
              AND COLUMN_NAME  = :column
        ";

        // 2) Preparo la query usando la connessione PDO ereditata da DB_PK
        $stmt = $this->pk_conn->prepare($sql);

        // 3) Faccio il bind dei parametri:
        //    - :db    → il nome del database (da SETUP_PK / DB_PK)
        //    - :table → il nome della tabella corrente
        //    - :column→ il nome della colonna che vogliamo controllare
        $stmt->bindValue(':db', $this->db);
        $stmt->bindValue(':table', $this->table);
        $stmt->bindValue(':column', $columnName);

        // 4) Eseguo la query
        $stmt->execute();

        // 5) Prendo il risultato (COUNT(*))
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt = null; // chiudo lo statement, pulito pulito

        // 6) Se cnt > 0 la colonna esiste
        return isset($row['campo_trovato']) && (int)$row['campo_trovato'] > 0;
    }

    /**
     * Controlla se una tabella esiste nel database corrente.
     *
     * Ritorna:
     *   true  → la tabella esiste
     *   false → non esiste
     */
    public function pk_table_exists(string $tableName): bool
    {
        $sql = "
        SELECT COUNT(*) AS tabella_trovata
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_SCHEMA = :db
          AND TABLE_NAME   = :table
    ";

        $stmt = $this->pk_conn->prepare($sql);
        $stmt->bindValue(':db', $this->db);
        $stmt->bindValue(':table', $tableName);
        $stmt->execute();

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        $stmt = null;

        return isset($row['tabella_trovata']) && (int)$row['tabella_trovata'] > 0;
    }

    /**
     * Crea una colonna nella tabella corrente SE non esiste già.
     *
     * $columnName      → nome della colonna da creare (es. 'id_azienda')
     * $columnSql       → definizione SQL della colonna SENZA il nome, es.:
     *                    'INT NULL DEFAULT NULL AFTER id_operatore'
     *
     * Ritorna:
     *   true  → la colonna esiste (perché già c'era o perché l'abbiamo creata)
     *   false → la tabella non esiste oppure ALTER TABLE è fallito
     */
    public function pk_ensure_column(string $columnName, string $columnSql): bool
    {
        // 1) Se la tabella non esiste proprio, non facciamo nulla qui
        if (! $this->pk_table_exists($this->table)) {
            // volendo qui potresti in futuro chiamare una ensureTable(...)
            return false;
        }

        // 2) Se la colonna esiste già, siamo a posto
        if ($this->pk_column_exists($columnName)) {
            return true;
        }

        // 3) Sanifico i nomi di tabella e colonna (per sicurezza sugli identificatori)
        $tableSafe  = self::pk_sanitizie_name_column($this->table);
        $columnSafe = self::pk_sanitizie_name_column($columnName);

        // 4) Costruisco l'ALTER TABLE.
        //    NOTA: $columnSql deve contenere SOLO tipo/default/AFTER ecc., niente nomi.
        $sql = sprintf(
            'ALTER TABLE `%s` ADD `%s` %s',
            $tableSafe,
            $columnSafe,
            $columnSql
        );

        try {
            $this->pk_conn->exec($sql);
            return true;
        } catch (\PDOException $e) {
            // In produzione potresti loggare l'errore invece di fare die()
            if (getenv('APP_ENV') === 'dev') {
                error_log('[MIGRATOR_PK] ALTER FAILED: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Crea una tabella SE non esiste già.
     *
     * $tableName → nome della tabella da creare
     * $tableSql  → definizione SQL interna a CREATE TABLE (...) senza il nome.
     *
     * Esempio:
     *   pk_ensure_table(
     *       'aziende',
     *       '`id_azienda` INT UNSIGNED NOT NULL AUTO_INCREMENT,
     *        `nome` VARCHAR(255) NOT NULL,
     *        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
     *        PRIMARY KEY (`id_azienda`)',
     *       'InnoDB'
     *   );
     *
     * Ritorna:
     *   true  → la tabella esiste già o è stata creata
     *   false → errore
     */
    public function pk_ensure_table(
        string $tableName,
        string $tableSql,
        string $engine = 'InnoDB'
    ): bool {

        // 1) Se esiste già, non facciamo nulla
        if ($this->pk_table_exists($tableName)) {
            return true;
        }

        // 2) Sanifico il nome
        $tableSafe = self::pk_sanitizie_name_column($tableName);

        // 3) Costruisco il CREATE TABLE
        $sql = sprintf(
            "CREATE TABLE `%s` (
            %s
        ) ENGINE=%s DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
            $tableSafe,
            $tableSql,
            $engine
        );

        try {
            $this->pk_conn->exec($sql);
            return true;
        } catch (\PDOException $e) {
            if (getenv('APP_ENV') === 'dev') {
                error_log('[MIGRATOR_PK] CREATE TABLE FAILED: ' . $e->getMessage());
            }
            return false;
        }
    }
    /**
     * Elimina una colonna SE esiste.
     *
     * Ritorna:
     *   true  → colonna eliminata o già non esistente
     *   false → tabella non esiste oppure errore SQL
     */
    public function pk_drop_column(string $columnName): bool
    {
        // 1) Se la tabella non esiste → ritorno subito, non faccio danni
        if (! $this->pk_table_exists($this->table)) {
            return false;
        }

        // 2) Se la colonna NON esiste → siamo già a posto
        if (! $this->pk_column_exists($columnName)) {
            return true;
        }

        // 3) Sanifico identificatori
        $tableSafe  = SAFE_PK::pk_sanitizie_name_column($this->table);
        $columnSafe = SAFE_PK::pk_sanitizie_name_column($columnName);

        // 4) ALTER TABLE ... DROP COLUMN ...
        $sql = sprintf(
            "ALTER TABLE `%s` DROP COLUMN `%s`",
            $tableSafe,
            $columnSafe
        );

        try {
            $this->pk_conn->exec($sql);
            return true;
        } catch (\PDOException $e) {
            if (getenv('APP_ENV') === 'dev') {
                error_log('[MIGRATOR_PK] DROP COLUMN FAILED: ' . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * =============================================================================
     * EN: Execute a raw SQL statement (best for maintenance tasks like cleanup).
     * IT: Esegue una query SQL "raw" (utile per manutenzione/pulizia).
     * =============================================================================
     *
     * @param string $sql Raw SQL to execute
     * @return int Number of affected rows (as returned by PDO::exec)
     */
    public function pk_exec_sql(string $sql): int
    {
        try {
            return (int) $this->pk_conn->exec($sql);
        } catch (\PDOException $e) {
            if (getenv('APP_ENV') === 'dev') {
                error_log('[MIGRATOR_PK] EXEC SQL FAILED: ' . $e->getMessage() . ' | SQL=' . $sql);
            }
            return 0;
        }
    }
}
