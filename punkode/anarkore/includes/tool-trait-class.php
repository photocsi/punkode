<?php

namespace Punkode;

/*le funzioni di input tipo $this->option_pk() non sono collegate direttaemnte alla classe, ma inserite in classi che estendono input allora funzionano lo stesso*/

trait TOOL_PK
{

    static function nome_esistente($nome, $array_nomi)
    {
        $num = 0;
        $control = 0;
        if (isset($array_nomi) && !empty($array_nomi)) {
            $length = count($array_nomi);
            while ($control == 0) {
                for ($i = 0; $i < $length; $i++) {
                    if ($nome == $array_nomi[$i]) {
                        $num++;
                    } else {
                        $control = 1;
                    }
                }
            }
        }


        if ($num == 0) {
            return $nome;
        } else {
            return $nome . $num;
        }
    }

    /* serve per settare il type column in un valore utilizzabile in punkode ad esempio varchar diventa var */
    static function pk_set_column_type($value_column_type)
    {
        switch ($value_column_type) {

            case str_starts_with($value_column_type, 'varchar') === true:  /*  la funzione str_starts_with controlla se una stringa comincia con una sottostringa */
                return 'var';
                break;

            case str_starts_with($value_column_type, 'int') === true:  /* per tutte la tripla uguaglianza è necessaria perche la funzione di php funzioni */
                return 'int';
                break;

            case str_starts_with($value_column_type, 'bigint') === true:  /* per tutte la tripla uguaglianza è necessaria perche la funzione di php funzioni */
                return 'bigi';
                break;

            case str_starts_with($value_column_type, 'text') === true:
                return 'text';
                break;

            case str_starts_with($value_column_type, 'date') === true:
                return 'dat';
                break;

            case str_starts_with($value_column_type, 'tinyint') === true:
                return 'tinyi';
                break;

            case str_starts_with($value_column_type, 'tinytext') === true:
                return 'tinyt';
                break;

            case $value_column_type === 'char(255)': /* in questo caso controllo la stringa intera prima del char che altrimenti la darebbe per vera */
                return 'char(255)';
                break;

            case str_starts_with($value_column_type, 'char') === true:
                return 'cha';
                break;

            case str_starts_with($value_column_type, 'longtext') === true:
                return 'lon';
                break;

            case str_starts_with($value_column_type, 'json') === true:
                return 'json';
                break;

            case str_starts_with($value_column_type, 'datetime') === true:
                return 'datt';
                break;

            case str_starts_with($value_column_type, 'timestamp') === true:
                return 'times';
                break;

            case str_starts_with($value_column_type, 'float') === true:
                return 'flo';
                break;

            case str_starts_with($value_column_type, 'double') === true:
                return 'dou';
                break;

            case str_starts_with($value_column_type, 'time') === true:
                return 'tim';
                break;

            case str_starts_with($value_column_type, 'smallint') === true:
                return 'sma';
                break;

            case str_starts_with($value_column_type, 'mediumint') === true:
                return 'medi';
                break;

            case str_starts_with($value_column_type, 'decimal') === true:
                return 'dec';
                break;

            case str_starts_with($value_column_type, 'real') === true:
                return 'rea';
                break;

            case str_starts_with($value_column_type, 'bit') === true:
                return 'bit';
                break;

            case str_starts_with($value_column_type, 'boolean') === true:
                return 'boo';
                break;

            case str_starts_with($value_column_type, 'serial') === true:
                return 'ser';
                break;

            case str_starts_with($value_column_type, 'mediumtext') === true:
                return 'medt';
                break;

            case str_starts_with($value_column_type, 'binary') === true:
                return 'bin';
                break;
            case str_starts_with($value_column_type, 'varbinary') === true:
                return 'varb';
                break;
            case str_starts_with($value_column_type, 'tinyblob') === true:
                return 'tinyb';
                break;
            case str_starts_with($value_column_type, 'blob') === true:
                return 'blo';
                break;
            case str_starts_with($value_column_type, 'mediumblob') === true:
                return 'medb';
                break;
            case str_starts_with($value_column_type, 'longblob') === true:
                return 'lonb';
                break;
            case str_starts_with($value_column_type, 'enum') === true:
                return 'enu';
                break;
            case str_starts_with($value_column_type, 'set') === true:
                return 'set';
                break;
            case str_starts_with($value_column_type, 'geometry') === true:
                return 'geo';
                break;
            case str_starts_with($value_column_type, 'point') === true:
                return 'poi';
                break;
            case str_starts_with($value_column_type, 'linestring') === true:
                return 'lin';
                break;
            case str_starts_with($value_column_type, 'polygon') === true:
                return 'pol';
                break;

            case str_starts_with($value_column_type, 'multipoint') === true:
                return 'mul';
                break;
            case str_starts_with($value_column_type, 'multilinestring') === true:
                return 'muls';
                break;
            case str_starts_with($value_column_type, 'multipolygon') === true:
                return 'mulp';
                break;
            case str_starts_with($value_column_type, 'geometricollection') === true:
                return 'geoc';
                break;

            case str_starts_with($value_column_type, 'year') === true:
                return 'yea';
                break;

            default:
                return '';
                break;
        }
    }

    static function pk_ord_array(array $array)
    {
        foreach ($array as $key => $value) {
            foreach ($value as $key => $value1) {

                $result[$key] = array();
            }
        }
        foreach ($array as $key => $value) {
            foreach ($value as $key => $value1) {

                array_push($result[$key], $value1);
            }
        }
        if (!empty($result)) {
            return $result;
        } else {
            return NULL;
        }
    }

    /**
     * Consuma un PDOStatement e restituisce i dati nel formato atteso dal tuo framework.
     * - 'FETCH_ASSOC'  => raccoglie righe associative e poi le trasforma con pk_ord_array()
     *                     (che immagino converta righe→colonne: es. ['id'=>[...], 'nome'=>[...]])
     *
     * Nota: qui chiudiamo SEMPRE lo statement (closeCursor + null) così
     *       fuori non devi preoccupartene.
     */
    public static function pk_fetch($result_query, string $fetch = 'FETCH_ASSOC')
    {
        // Se qualcuno passa già un array (magari per errore), lo rimandiamo a pk_ord_array
        if (is_array($result_query)) {
            return self::pk_ord_array($result_query);
        }

        // Difesa: assicuriamoci che sia un PDOStatement
        if (!($result_query instanceof \PDOStatement)) {
            // Comportamento “safe”: ritorna array vuoto
            return self::pk_ord_array([]);
        }

        $rows = [];

        switch ($fetch) {
            case 'FETCH_ASSOC':
                // Variante 1 (streaming, memoria più bassa):
                while ($row = $result_query->fetch(\PDO::FETCH_ASSOC)) {
                    $rows[] = $row;
                }
                // Variante 2 (alternativa): $rows = $result_query->fetchAll(\PDO::FETCH_ASSOC);
                break;

            // In futuro potresti aggiungere altri formati:
            // case 'FETCH_OBJ':
            //     while ($row = $result_query->fetch(\PDO::FETCH_OBJ)) { $rows[] = (array)$row; }
            //     break;

            default:
                // fallback: nessun formato noto → ritorniamo vuoto
                $rows = [];
                break;
        }

        // Importantissimo: chiudiamo esplicitamente il cursore e rilasciamo lo statement.
        // Così NON devi fare $stmt = null; dove chiami pk_fetch().
        try {
            $result_query->closeCursor();
        } catch (\Throwable $e) { /* ignore */
        }
        $result_query = null;

        // Manteniamo la tua logica: converti righe → struttura a colonne
        return self::pk_ord_array($rows);
    }
}
