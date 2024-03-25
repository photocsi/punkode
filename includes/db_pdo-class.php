<?php

namespace Punkode;

require_once 'safety-trait-class.php';
require_once 'manag_table-trait-class.php';
class DB_PK extends CONFIG_PK
{

    private $conn = "";
    public $field = array();
    public $table = "";
    public $where = array();
    public $value = array();

    use SAFE_PK;

    function __construct(string $table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

        parent::__construct($db, $host, $user, $password);

        $_SESSION['table'] = $table;
        $_SESSION['db'] = $this->db;
        $_SESSION['host'] = $this->host;
        $_SESSION['user'] = $this->user;
        $_SESSION['password'] = $this->password;



        try {
            $this->conn = new \PDO("mysql:host=$this->host;dbname=$this->db", $this->user, $this->password);
            // Set the PDO error mode to exception
            $this->conn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            /* ECHO "CONNESSIONE RIUSCITA"; */
        } catch (\PDOException $e) {
            die("ERRORE: Impossibile stabilire una connessione al database");
        }
    }

    function __destruct()
    {
        $this->conn = null;
    }

    /* seleziona una o piu campi con un where */
    public function select_pk($table, $array_field, $where, $value)
    {
        $result = array();
        $this->field = implode(',', $array_field);
        $this->table = $table;
        $this->where = $where;
        $param = ':' . $where;
        $this->value = $value;

        $select = $this->conn->prepare("SELECT $this->field FROM $this->db.$this->table WHERE $this->where= $param ");
        $select->bindparam($param, $this->value);
        $select->execute();


        while ($row = $select->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }



    /* seleziono tutti i campi di una tabella */
    public function select_all_pk($table, $field)
    {
        $result = array();
        $this->field = $field;
        $this->table = $table;

        $select = $this->conn->prepare("SELECT $this->field FROM $this->db.$this->table ");

        $select->execute();


        while ($row = $select->fetch(\PDO::FETCH_BOTH)) {
            $result[] = $row;
        }
        return $result;
    }





    /* seleziona uno o piu campi con 2 where */
    public function select_2where_pk($array_field, $table, $where, $value)
    {
        $result = array();

        $this->field = implode(',', $array_field);
        $this->table = $table;
        $this->where = $where;
        $this->value = $value;

        $select = $this->conn->prepare("SELECT $this->field FROM $this->table WHERE ({$this->where[0]}= :value0 AND {$this->where[1]}= :value1 )");
        $select->bindparam(":value0", $this->value[0]);
        $select->bindparam(":value1", $this->value[1]);
        $select->execute();

        while ($row = $select->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function select_order_pk($table, $array_field, $quantity, $array_where, $array_value, $asc_desc)
    {
        $result = array();

        $this->field = implode(',', $array_field);
        $this->table = $table;
        $this->where = $array_where;
        $this->value = $array_value;

        if (!isset($this->where[1])) {
            $this->where[1] = $this->where[0];
            $this->where[2] = $this->where[0];
        } else if (isset($this->where[1]) && !isset($this->where[2])) {
            $this->where[2] = $this->where[0];
        }

        if (!isset($this->value[1])) {
            $this->value[1] = $this->value[0];
            $this->value[2] = $this->value[0];
        } else if (isset($this->value[1]) && !isset($this->value[2])) {
            $this->value[2] = $this->value[0];
        }

        $select = $this->conn->prepare("SELECT $this->field FROM $this->table WHERE ({$this->where[0]}= :value0 AND {$this->where[1]}= :value1 AND {$this->where[2]}= :value2 ) ORDER BY $this->field $asc_desc LIMIT $quantity");
        $select->bindparam(":value0", $this->value[0]);
        $select->bindparam(":value1", $this->value[1]);
        $select->bindparam(":value2", $this->value[2]);
        $select->execute();

        while ($row = $select->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    public function order_db_pk(string $table, string $field){
        $this->sanitizie_only_char($field);
        
        $select = $this->conn->prepare("SELECT * FROM $this->db.$table ORDER BY $field  ;");
        $select->execute();

        while ($row = $select->fetch(\PDO::FETCH_BOTH)) {
            $result[] = $row;
        }
        return $result;
    }


    public function select_innerjoin_pk($string_field, $array_table, $where, $value)
    {
        $result = array();
        $select_inner = $this->conn->prepare("SELECT $string_field FROM {$array_table[0]} INNER JOIN {$array_table[1]} WHERE $where = $value ");
        $select_inner->execute();
        while ($row = $select_inner->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
    /*  inserisco un nuovo record con i suoi vari campi */
    public function insert_pk(string $table, array $array_fields, array $array_values)
    {
        $array_param = array();
        $count_array_fields= count($array_fields);
        for ($i = 0; $i < $count_array_fields; $i++) {
            $array_param[$i] = ':' . $array_fields[$i];
        }
        $string_fields = implode(",", $array_fields);
        $string_param = implode(",", $array_param);
        $insert = $this->conn->prepare("INSERT INTO $this->db.$table ($string_fields) VALUES ($string_param) ");
        $count_array_param=count($array_param);
        for ($i = 0; $i < $count_array_param; $i++) {
            $insert->bindparam($array_param[$i], $array_values[$i]);
        }
        $insert->execute();
        unset($count_array_fields);
        unset($count_array_param);
    }


    public function update_pk(string $table, string $field, string $value, string $where_field, string $where_value)
    {
        $sql="UPDATE $this->db.$table SET $field=:field_param WHERE $where_field = :where_param";
        $insert = $this->conn->prepare($sql);
        $insert->bindparam(':field_param', $value);
        $insert->bindparam(':where_param', $where_value);
        $insert->execute();
    }



    public function delete_pk(string $table, string $where_field, string $value_where)
    {
        $param = ':' . $where_field;
        $delete = $this->conn->prepare("DELETE FROM $this->db.$table WHERE $where_field = $param");
        $delete->bindParam($param, $value_where);
        $delete->execute();
    }


    public function create_table_pk(string $name_table, string $name_id)
    {

        $create = $this->conn->prepare("CREATE TABLE $name_table ( $name_id INT UNSIGNED NOT NULL AUTO_INCREMENT, PRIMARY KEY ($name_id)) ENGINE = InnoDB;");
        if ($create->execute()) {
            echo "Tabella album creata con successo";
        } else {
            die("Errore di creazione");
        }
    }


    public function remove_column_pk(string $name_table, string $name_column)
    {

        $create = $this->conn->prepare("ALTER TABLE $name_table  DROP $name_column  ;");
        $create->execute();
    }

    public function move_column_pk(string $name_table, string $name_column, string $after_column, string $tipe_column)
    {

        $create = $this->conn->prepare("ALTER TABLE $name_table  MODIFY $name_column  $tipe_column AFTER $after_column; ");
        $create->execute();
    }

    public function create_column_pk(
        string $name_table,
        string $name_new_column,
        string $type_new_column,
        string $length_new_column,
        string $predefinito_new_column = '',
        string $codifica_new_column = '',
        string $attr_new_column = '',
        string $null_new_column = 'NOT NULL'
    ) {

        $name_sanitized = $name_new_column; /* sanitizare diversamente da html */
        $type_sanitized = $this->sanitize_var_pk($type_new_column);
        $length_sanitized = $this->sanitize_int_pk($length_new_column);
        $predefinito_sanitized = $this->sanitize_var_pk($predefinito_new_column);
        $attr_sanitized = $this->sanitize_var_pk($attr_new_column);
        $null_satized = $this->sanitize_var_pk($null_new_column);
        $codifica_sanitized = $this->sanitize_var_pk($codifica_new_column);

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


        $create = $this->conn->prepare("ALTER TABLE $name_table ADD $name_sanitized  
        $type_sanitized $length_sanitized
        $codifica_sanitized
        $attr_sanitized
        $null_satized 
        $predefinito_sanitized  ;");
        if ($create->execute()) {
            echo "";
        } else {
            die("Errore di creazione");
        }
    }

    /* seleziono tutte le info della tabella etichette del db ecc */
    public function select_information_table_pk($table, $field)
    {
        $result = array();
        $this->field = $field;
        $this->table = $table;

        $select = $this->conn->prepare("SELECT $this->field FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = :table_name ORDER BY ORDINAL_POSITION");
        $select->bindparam(':db_name', $this->db);
        $select->bindparam(':table_name', $this->table);

        $select->execute();


        while ($row = $select->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /* seleziono tutte le info della tabella etichette del db ecc */
    public function select_one_information_table_pk(string $table, string $column_name)
    {
        $result = array();
        $this->table = $table;

        $select = $this->conn->prepare("SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE (TABLE_SCHEMA = :db_name AND TABLE_NAME = :table_name AND COLUMN_NAME = :column_name) ");
        $select->bindparam(':db_name', $this->db);
        $select->bindparam(':table_name', $this->table);
        $select->bindparam(':column_name', $column_name);

        $select->execute();


        while ($row = $select->fetch(\PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
}
