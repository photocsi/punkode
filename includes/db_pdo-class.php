<?php


require_once FASTDIR . '/setup.php';
require_once 'tool-class.php';
class DB_CSI extends SETUP_CSI
{

    private $conn = "";
    public $field = array();
    public $table = "";
    public $where = array();
    public $value = array();
    

    function __construct(string $table='optional',$db='optional',$host='optional',$user='optional',$password='optional')
    {
      
        parent::__construct($db,$host,$user,$password);
        
        $_SESSION['table']=$table;
        $_SESSION['db']=$this->db;
        $_SESSION['host']=$this->host;
        $_SESSION['user']=$this->user;
        $_SESSION['password']=$this->password;

      

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
    public function select( $table,$array_field,$where ,$value )
    {
        $result = array();
        $this->field = implode(',', $array_field);
        $this->table = $table;
        $this->where = $where;
        $param=':'.$where ;
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
     public function select_information_table( $table,$field)
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
      public function select_all( $table,$field)
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
    public function select_2where($array_field, $table, $where, $value)
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

    public function select_order($table,$array_field, $quantity, $array_where, $array_value,$asc_desc)
    {
        $result = array();

        $this->field = implode(',', $array_field);
        $this->table = $table;
        $this->where = $array_where;
        $this->value = $array_value;

        if(!isset($this->where[1])){
           $this->where[1]=$this->where[0];
           $this->where[2]=$this->where[0];
        }else if(isset($this->where[1]) && !isset($this->where[2])){
            $this->where[2]=$this->where[0];
        }

        if(!isset($this->value[1])){
            $this->value[1]=$this->value[0];
            $this->value[2]=$this->value[0];
         }else if(isset($this->value[1]) && !isset($this->value[2])){
             $this->value[2]=$this->value[0];
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



    public function select_innerjoin($string_field, $array_table, $where, $value)
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
    public function insert($table, $array_fields, $array_values)
    {
        $array_param=array();
        for ($i=0; $i <count($array_fields) ; $i++) { 
            $array_param[$i]=':'.$array_fields[$i];
        }
        $string_fields=implode(",",$array_fields);
        $string_param=implode(",",$array_param);
        $insert = $this->conn->prepare("INSERT INTO $this->db.$table ($string_fields) VALUES ($string_param) ");
        for ($i = 0; $i < count($array_param); $i++) {
            $insert->bindparam($array_param[$i], $array_values[$i]);
        }
        $insert->execute();  
    }

    public function update($table,$field, $value, $where_field,$where_value)
    {
        $where_param = ':' . $where_field;
        $field_param = ':' . $field;
        $insert = $this->conn->prepare("UPDATE $this->db.$table SET $field=$field_param WHERE $where_field = $where_param");
        $insert->bindparam($field_param, $value);
        $insert->bindparam($where_param, $where_value);
        $insert->execute();
    }



    public function delete($table, $where, $value_where)
    {
        $param = ':' . $where;
        $delete = $this->conn->prepare("DELETE FROM $this->db.$table WHERE $where = $param");
        $delete->bindParam($param, $value_where);
        $delete->execute();
    }

}


