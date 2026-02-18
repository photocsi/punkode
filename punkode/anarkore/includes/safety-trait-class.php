<?php

namespace Punkode;

trait SAFE_PK
{

    public static function pk_sanitize_name_for_path(string $value, int $maxlen = 64): string
    {
        return \Punkode\SAFETY_PK::pk_sanitize_dir_file_name($value, $maxlen);
    }


    /**
     * Sanifica in modo “whitelist” un IDENTIFICATORE SQL (nome tabella/colonna/indice).
     * - Consente solo A–Z a–z 0–9 e underscore.
     * - Se inizia con cifra, prefix “t_” (i nomi tabella/colonna non dovrebbero iniziare con numero).
     * - Comprimi underscore ripetuti.
     * - Tronca a maxlen (default 64) per evitare problemi con nomi troppo lunghi.
     *
     * NOTA: questa funzione NON va usata per valori (dati). Per i valori si usano i bind (:param).
     */

    public static function pk_sanitize_campo_db(string $id, int $maxlen = 64): string
    {
        // Rimpiazza tutto ciò che non è [A-Za-z0-9_] con underscore
        $id = preg_replace('/[^A-Za-z0-9_]/', '_', $id);

        // Se stringa vuota o inizia con cifra, prefissa "t_"
        if ($id === '' || ctype_digit($id[0])) {
            $id = 't_' . $id;
        }

        // Collassa underscore multipli in uno solo
        $id = preg_replace('/_+/', '_', $id);

        // Tronca alla lunghezza massima (di solito 64 è ok per MySQL)
        if (strlen($id) > $maxlen) {
            $id = substr($id, 0, $maxlen);
        }

        return $id;
    }

    public static function pk_sanitize_int($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_SANITIZE_NUMBER_INT);

        return $value_sanitized; // con substr limito la visualizzazione del dato a 11 caratteri
    }

    public static function pk_validate_int($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_VALIDATE_INT);


        return $value_sanitized;
    }

    public static function pk_sanitize_var($value)
    {
        $value_sanitized = htmlentities(trim($value), ENT_QUOTES);

        return $value_sanitized;
    }

    public static function pk_validate_var($value)
    {
        $value_decode = html_entity_decode((string) $value);
        $value_sanitized = htmlspecialchars($value_decode);

        return $value_sanitized;
    }

    public static function pk_sanitize_cha($value)
    {
        $value_sanitized = htmlentities(trim($value), ENT_QUOTES);

        return $value_sanitized;
    }

    public static function pk_validate_cha($value)
    {
        $value_decode = html_entity_decode((string) $value);
        $value_sanitized = htmlspecialchars($value_decode);

        return $value_sanitized;
    }
    public static function pk_sanitize_date($value)
    {
        $value_sanitized = htmlentities($value, ENT_QUOTES);

        return substr($value_sanitized, 0, 12);
    }

    public static function pk_validate_date($value)
    {
        $value_control = htmlentities($value);
        if ($value_control != '0000-00-00' && $value_control != NULL) {
            $date = date_create($value_control);
            $value_sanitized = date_format($date, 'd-M-y H:i:s');
        } else {
            $value_sanitized = '';
        }

        return substr($value_sanitized, 0, 18);
    }

    public static function pk_validate_date_short($value)
    {
        $value_control = htmlentities($value);
        if ($value_control != '0000-00-00' && $value_control != NULL) {
            $date = date_create($value_control);
            $value_sanitized = date_format($date, 'd-M-Y');
        } else {
            $value_sanitized = '';
        }

        return substr($value_sanitized, 0, 18);
    }

    public static function pk_sanitize_email($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_SANITIZE_EMAIL);

        return substr($value_sanitized, 0, 40);
    }

    public static function pk_validate_email($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_VALIDATE_EMAIL);

        return substr($value_sanitized, 0, 40);
    }

    public static function pk_sanitize_pass($value)
    {
        $value_sanitized = password_hash(trim($value), PASSWORD_ARGON2I);

        return substr($value_sanitized, 0, 255);
    }
    public static function pk_validate_pass($value)
    {
        $value_validate = $value;

        return $value_validate;
    }

    public static function pk_sanitize_float($value)
    {
        $tmp_value_sanitized = str_replace(',', '.', $value);

        $value_sanitized = filter_var(trim($tmp_value_sanitized), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

        return substr($value_sanitized, 0, 18);
    }

    public static function pk_validate_float($value)
    {

        (!empty($value)) ? $tmp_value_sanitized = str_replace(',', '.', $value) : $tmp_value_sanitized = 0;
        $value_sanitized = filter_var(trim($tmp_value_sanitized), FILTER_VALIDATE_FLOAT);

        return substr($value_sanitized, 0, 18);
    }

    public static function pk_validate_bool($value)
    {
        $value_sanitized = filter_var(trim($value), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return $value_sanitized;
    }

    public static function pk_sanitizie_only_char($value)
    {
        trim($value);
        $regex = '/^[a-z _ A-Z]+$/';
        if (preg_match($regex, $value)) {
            $value_sanitized = str_replace(' ', '_', $value);
        } else {
            $value_sanitized = '';
        }
        return $value_sanitized;
    }

    public static function pk_sanitizie_name_column($value)
    { //creare la sanitizzazione per escludere le parole chiave di mysql

        $test = '';
        $array_string_key_mysql =
            [
                'int',
                'case',
                'bigint',
                'smallint',
                'tinyint',
                'tinytext',
                'float',
                'double',
                'varchar',
                'char',
                'text',
                'longtext',
                'boolean',
                'date',
                'timestamp',
                'datetime',
                'time',
                'year',
                'blob',
                'serial',
                'json',
                'READ',
                'REAL',
                'REFERENCES',
                'REGEXP',
                'RENAME',
                'REPEAT',
                'REPLACE',
                'REQUIRE',
                'RESTRICT',
                'RETURN',
                'REVOKE',
                'RIGHT',
                'FETCH',
                'FIELDS',
                'ADD',
                'ALL',
                'ALTER',
                'ANALYZE',
                'AND',
                'AS',
                'ASC',
                'ASENSITIVE',
                'AUTO_INCREMENT',
                'BDB',
                'BEFORE',
                'BERKELEYDB',
                'BETWEEN',
                'BIGINT',
                'BINARY',
                'BLOB',
                'BOTH',
                'BY',
                'CALL',
                'CASCADE',
                'CHANGE',
                'CHARACTER',
                'CHECK',
                'COLLATE',
                'COLUMN',
                'COLUMNS',
                'CONDITION',
                'CONNECTION',
                'CONSTRAINT',
                'CONTINUE',
                'CREATE',
                'CROSS',
                'CURRENT_DATE',
                'CURRENT_TIME',
                'CURRENT_TIMESTAMP',
                'CURSOR',
                'DATABASE',
                'DATABASES',
                'DAY_HOUR',
                'DAY_MICROSECOND',
                'DAY_MINUTE',
                'DAY_SECOND',
                'DEC',
                'DECIMAL',
                'DECLARE',
                'DEFAULT',
                'DELAYED',
                'DELETE',
                'DESC',
                'DESCRIBE',
                'DETERMINISTIC',
                'DISTINCT',
                'DISTINCTROW',
                'DIV',
                'DROP',
                'ELSE',
                'ELSEIF',
                'ENCLOSED',
                'ESCAPED',
                'EXISTS',
                'EXIT',
                'EXPLAIN',
                'FALSE',
                'FOR',
                'FORCE',
                'FOREIGN',
                'FOUND',
                'FRAC_SECOND',
                'FROM',
                'FULLTEXT',
                'GRANT',
                'GROUP',
                'HAVING',
                'HIGH_PRIORITY',
                'HOUR_MICROSECOND',
                'HOUR_MINUTE',
                'HOUR_SECOND',
                'IF',
                'IGNORE',
                'IN',
                'INDEX',
                'INFILE',
                'INNER',
                'INNODB',
                'INOUT',
                'INSENSITIVE',
                'INSERT',
                'INTEGER',
                'INTERVAL',
                'INTO',
                'IO_THREAD',
                'IS',
                'ITERATE',
                'JOIN',
                'KEY',
                'KEYS',
                'KILL',
                'LEADING',
                'LEAVE',
                'LEFT',
                'LIKE',
                'LIMIT',
                'LINES',
                'LOAD',
                'LOCALTIME',
                'LOCALTIMESTAMP',
                'LOCK',
                'LONG',
                'LONGBLOB',
                'LONGTEXT',
                'LOOP',
                'LOW_PRIORITY',
                'MASTER_SERVER_ID',
                'MATCH',
                'MEDIUMBLOB',
                'MEDIUMINT',
                'MEDIUMTEXT',
                'MIDDLEINT',
                'MINUTE_MICROSECOND',
                'MINUTE_SECOND',
                'MOD',
                'NATURAL',
                'NOT',
                'NO_WRITE_TO_BINLOG',
                'NULL',


            ];
        foreach ($array_string_key_mysql as $key_mysql) {
            if (strcasecmp($value, $key_mysql) == 0) {
                $test = '_1';
            };
        }
        $value_sanitized = htmlEntities(str_replace(" ", "_", trim($value . $test)));

        return $value_sanitized;
    }
}
