<?php

require_once 'trait-class.php';
class DB_PK extends SETUP_PK
{

    private $conn = "";
    public $field = array();
    public $table = "";
    public $where = array();
    public $value = array();

    use SANITIZE_PK;

    function __construct(string $table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

        parent::__construct($db, $host, $user, $password);

        $_SESSION['table'] = $table;
        $_SESSION['db'] = $this->db;
        $_SESSION['host'] = $this->host;
        $_SESSION['user'] = $this->user;
        $_SESSION['password'] = $this->password;



        try {
            $this->conn = new PDO("mysql:host=$this->host;dbname=$this->db", $this->user, $this->password);
            // Set the PDO error mode to exception
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            /* ECHO "CONNESSIONE RIUSCITA"; */
        } catch (PDOException $e) {
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


        while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }

    /* seleziono tutte le info della tabella etichette del db ecc */
    public function select_information_table_pk($table, $field)
    {
        $result = array();
        $this->field = $field;
        $this->table = $table;

        $select = $this->conn->prepare("SELECT $this->field FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db_name AND TABLE_NAME = :table_name ");
        $select->bindparam(':db_name', $this->db);
        $select->bindparam(':table_name', $this->table);

        $select->execute();


        while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
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


        while ($row = $select->fetch(PDO::FETCH_BOTH)) {
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

        while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
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

        while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }



    public function select_innerjoin_pk($string_field, $array_table, $where, $value)
    {
        $result = array();
        $select_inner = $this->conn->prepare("SELECT $string_field FROM {$array_table[0]} INNER JOIN {$array_table[1]} WHERE $where = $value ");
        $select_inner->execute();
        while ($row = $select_inner->fetch(PDO::FETCH_ASSOC)) {
            $result[] = $row;
        }
        return $result;
    }
    /*  inserisco un nuovo record con i suoi vari campi */
    public function insert_pk(string $table, array $array_fields, array $array_values)
    {
        $array_param = array();
        for ($i = 0; $i < count($array_fields); $i++) {
            $array_param[$i] = ':' . $array_fields[$i];
        }
        $string_fields = implode(",", $array_fields);
        $string_param = implode(",", $array_param);
        $insert = $this->conn->prepare("INSERT INTO $this->db.$table ($string_fields) VALUES ($string_param) ");
        for ($i = 0; $i < count($array_param); $i++) {
            $input_val = $this->test_input_pk($array_values[$i]);
            $insert->bindparam($array_param[$i], $input_val);
        }
        $insert->execute();
    }

    public function update($table, $field, $value, $where_field, $where_value)
    {
        $where_param = ':' . $where_field;
        $field_param = ':' . $field;
        $insert = $this->conn->prepare("UPDATE $this->db.$table SET $field=$field_param WHERE $where_field = $where_param");
        $input_val = $this->test_input_pk($value);
        $insert->bindparam($field_param, $input_val);
        $insert->bindparam($where_param, $where_value);
        $insert->execute();
    }



    public function delete_pk($table, $where, $value_where)
    {
        $param = ':' . $where;
        $delete = $this->conn->prepare("DELETE FROM $this->db.$table WHERE $where = $param");
        $delete->bindParam($param, $value_where);
        $delete->execute();
    }

    public function test_input_pk($input_post)
    {
        $input = trim($input_post);
        $input = stripslashes($input_post);
        $input = htmlspecialchars($input_post);
        return $input;
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


    public function delete_column_table_pk(string $name_table, string $name_column){

        $create = $this->conn->prepare("ALTER TABLE $name_table  DROP $name_column  ;");
        if ($create->execute()) {
            echo "Colonna eliminata con successo";
        } else {
            die("Errore di creazione");
        }

    }

    public function create_column_table_pk(
        string $name_table,
        string $name_new_field,
        string $type_new_filed,
        string $length,
        string $predefinito = '',
        string $codifica = '',
        string $attr = '',
        string $null = 'NOT NULL'
    ) {

        $name_sanitized = $name_new_field; /* sanitizare diversamente da html */
        $type_sanitized = $type_new_filed;
        $length_sanitized = $this->sanitize_int_pk($length);
        $predefinito_sanitized = $predefinito;
        $attr_sanitized = $attr;
        $null_satized = $null;
        $codifica_sanitized = $codifica;

        /* faccio un controllo della codifica e la trasformo nell'informazione che serve al mysql */
        if ($codifica === 'utf8mb4_unicode_ci') {
            $codifica_sanitized = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        } elseif ($codifica === 'utf8mb4_general_ci') {
            $codifica_sanitized = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci';
        } else {
            $codifica_sanitized = 'CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci';
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE VARCHAR */
        if ($type_sanitized === 'VARCHAR') {
            if ($attr_sanitized === 'COMPRESSED=zlib') {
                $attr_sanitized = 'COMPRESSED=zlib';
            } else {
                $attr_sanitized = '';
            }

            if ($length_sanitized === '') {
                $length_sanitized = 250;
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

            if ($length_sanitized === '') {
                $length_sanitized = 250;
            }

            if ($predefinito_sanitized == 'CURRENT_TIMESTAMP') {
                $predefinito_sanitized = '';
            } elseif ($predefinito_sanitized == 'NULL') {
                $predefinito_sanitized = 'DEFAULT NULL';
            }
        }
/*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE INT */
        if ($type_sanitized === 'INT') {
            $codifica_sanitized = '';
            if ($length_sanitized === '') {
                $length_sanitized = 11;
            }
        }

        /*  CONTROLLO DI TUTTI I CAMPI SE SI SCEGLIE DATE */
        if ($type_sanitized === 'DATE') {
            $length_sanitized = '';
           
        }

        $create = $this->conn->prepare("ALTER TABLE $name_table ADD $name_sanitized  
        $type_sanitized($length_sanitized)
        $codifica_sanitized
        $attr_sanitized
        $null_satized 
        $predefinito_sanitized  ;");
        if ($create->execute()) {
            echo "Colonna aggiunta con successo";
        } else {
            die("Errore di creazione");
        }
    }
}
