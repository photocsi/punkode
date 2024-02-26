
<?php


class TOOL_PK extends DB_PK
{
    public  $info_schema = array();

    function __construct(string $table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {
  
  
      parent::__construct($table, $db, $host, $user, $password);
      $this->update_record_pk($table);
      $this->insert_record_pk($table);
      $this->delete_record_pk($table);
    }


    public function update_record_pk($table)
    {
        $this->info_schema = $this->select_information_table_pk($table, '*');
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['edit_record'])) {
                $array_type_column = explode(',', $_POST['type_column']);
                $array_key = array_keys($_POST); /* lista delle chiavi del post che corrisponde al nome dei field della tabella */
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }

                for ($i = 1; $i < count($array_type_column); $i++) { //prendo la lunghezza del type column e tolgo il primo valore che sarebbe l'id
                    switch ($array_type_column[$i]) {
                        case (($array_type_column[$i]) == 'int'):
                            $value_sanitiz = filter_var($array_value[$i], FILTER_SANITIZE_NUMBER_INT);
                            break;
                        case ($array_type_column[$i]) == 'var':
                            $value_sanitiz = htmlspecialchars($array_value[$i]);
                            break;
                        case ($array_type_column[$i]) == 'tex':
                            $value_sanitiz = htmlspecialchars($array_value[$i]);
                            break;
                        case ($array_type_column[$i]) == 'tinyt': /* tiniyt e usato per l'email */
                            $value_sanitiz = filter_var($array_value[$i], FILTER_SANITIZE_EMAIL);
                            break;
                        case ($array_type_column[$i]) == 'tinyint(1)':
                                $value_sanitiz = htmlspecialchars($array_value[$i]);
                            break;
                        case ($array_type_column[$i]) == 'dat':
                            $value_sanitiz = htmlspecialchars($array_value[$i]);
                            break;
                        case ($array_type_column[$i]) == 'lon':
                            $value_sanitiz = htmlspecialchars($array_value[$i]);
                            break;
                    }
                    if ($array_key[$i] != 'type_column') {
                        $this->update($table, $array_key[$i], $value_sanitiz, $array_key[0], $array_value[0]);
                    }
                }
            }
        }
    }

    public function insert_record_pk($table)
    {    
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['insert_record'])) {
                $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
                array_pop($array_key);/*  elimino l'ultima chiave del valore post il submit */
                array_pop($array_value);  /* elimino l'ultimo valore del post il submit */
                $this->insert_pk($table, $array_key, $array_value);
                header("Location: #");
            }
        }
    }

    public function delete_record_pk($table)
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['delete_record'])) {
                $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
                $this->delete_pk($table, $array_key[0], $array_value[0]);
            }
        }
    }


}
