<?php

namespace Punkode;

class RESPONSE_PK extends DB_PK
{
    public  $info_schema = array();
    use SAFE_PK;

    function __construct(string $table = '', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

       
        parent::__construct($table, $db, $host, $user, $password);

        $this->update_row_response_pk($table);
        $this->insert_row_response_pk($table);
        $this->delete_record_response_pk();
        $this->remove_column_response_pk($table);
        $this->add_column_response_pk($table);
        $this->move_column_response_pk($table);
       $this->create_table_response_pk();
    }


    public function update_row_response_pk($table)
    {
        $this->info_schema = $this->select_information_table_pk($table, '*'); /* "prendo tutte le info della tabella" */
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['edit_record_pk']) && isset($_POST['name_update_table_pk']) && !empty($_POST['name_update_table_pk'])) {

                $array_type_column = explode(',', $_POST['type_column']); /* trasformo in array la stringa con la lista del tipo colonne */
                $array_key = array_keys($_POST); /* lista delle chiavi del post che corrisponde al nome dei field della tabella */
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }

                
                if(isset($table) && !empty($table)){ /* se table è stato inserito come parametro nella funzione lo utilizza */

                }else{    /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto, quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza */
                    $table=$array_value[0];
                    array_shift ($array_key);
                    array_shift ($array_value);
                }
                

                for ($i = 1; $i < count($array_type_column); $i++) { //prendo la lunghezza del type column e tolgo il primo valore che sarebbe l'id
                    switch ($array_type_column[$i]) {
                        case 'int':
                           $value_sanitiz= $this->sanitize_int_pk($array_value[$i]);
                            break;
                        case 'var':
                            $value_sanitiz = $this->sanitize_var_pk($array_value[$i]);
                            break;
                        case 'cha':
                            $value_sanitiz = $this->sanitize_pass_pk($array_value[$i]);
                            break;
                        case 'tex':
                            $value_sanitiz = $this->sanitize_var_pk($array_value[$i]);
                            break;
                        case 'tinyt': /* tiniyt e usato per l'email */
                            $value_sanitiz = $this->sanitize_email_pk($array_value[$i]);
                            break;
                        case 'tinyint(1)':
                            $value_sanitiz = $array_value[$i];
                            break;
                        case 'dat':
                            $value_sanitiz = $this->sanitize_date_pk($array_value[$i]);
                            break;
                        case 'lon':
                            $value_sanitiz = $this->sanitize_var_pk($array_value[$i]);
                            break;
                    }
                    
                    if ($array_key[$i] != 'type_column') { /* type column e un campo nascosto nel form di arrivo per indentificare la tipologia della colonna */
                        $this->update_pk($table, $array_key[$i], $value_sanitiz, $array_key[0], $array_value[0]);
                    }
                }
            }
        }
    }

    public function insert_row_response_pk($table)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['submit_insert_record_pk']) && isset($_POST['name_insert_table_pk']) && !empty($_POST['name_insert_table_pk'])) {
                $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
                array_pop($array_key);/*  elimino l'ultima chiave del valore post il submit */
                array_pop($array_value);  /* elimino l'ultimo valore del post il submit */

                if(isset($table) && !empty($table)){

                }else{
                    $table=$array_value[0];
                    array_shift ($array_key);
                    array_shift ($array_value);
                }
            
                $this->insert_pk($table, $array_key, $array_value);
             
            
                header("Location: #");
            }
        }
    }

    public function delete_record_response_pk()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['delete_record_pk'])) {
                $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
                $this->delete_pk($array_value[0], $array_key[1], $array_value[1]);
            }
        }
    }

    public function add_column_response_pk($table)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if  (isset($_POST['name_new_column']) && !empty($_POST['name_new_column']) && !empty($_POST['type_new_column']) && isset($_POST['name_table_pk']) && !empty($_POST['name_table_pk'])) {

                if(isset($table) && !empty($table)){

                }else{
                    $table=$_POST['name_table_pk'];
                    
                }
                $name_new_column = $_POST['name_new_column'];
                $type_new_column = $_POST['type_new_column'];
                $length_new_column = $_POST['length_new_column'];
                $predefinito_new_column = $_POST['predefinito_new_column'];
                $attr_new_column = $_POST['attr_new_column'];
                $null_new_column = $_POST['null_new_column'];
                $codifica_new_column = $_POST['codifica_new_column'];
                $this->create_column_pk($table, $name_new_column, $type_new_column, $length_new_column, $predefinito_new_column, $codifica_new_column, $attr_new_column, $null_new_column);
                header("Location: #");
            }
        }
    }

    public function remove_column_response_pk($table)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['name_delete_column']) && !empty($_POST['name_delete_column']) && isset($_POST['submit_delete_column']) && isset($_POST['name_table_remove']) && !empty($_POST['name_table_remove'])) {
                $name_column = $_POST['name_delete_column'];
                if(isset($table) && !empty($table)){ /* se table è stato inserito come parametro nella funzione lo utilizza */

                }else{    /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto, quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza */
                    $table=$_POST['name_table_remove'];
                    
                }
                $this->remove_column_pk($table, $name_column);
                header("Location: #");
            }
        }
    }

    public function move_column_response_pk($table)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['submit_move_column_pk']) && isset($_POST['name_table_move_pk']) && !empty($_POST['name_table_move_pk']) ) {
                $name_column = $_POST['name_move_column_pk'];
                $after_column=$_POST['name_after_column_pk'];

                if(isset($table) && !empty($table)){ /* se table è stato inserito come parametro nella funzione lo utilizza */

                }else{    /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto, quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza */
                    $table=$_POST['name_table_move_pk'];
                    
                }
                $this->info_schema = $this->select_one_information_table_pk($table, $name_column); /* "prendo tutte le information table del campo selezionato da spostare" */
                $type=$this->info_schema[0]['COLUMN_TYPE'];
                $this->move_column_pk($table, $name_column,$after_column,$type);
                header("Location: #");
            }
        }
    }

    

    public function create_table_response_pk()
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['table_name_new_table']) && !empty($_POST['table_name_new_table']) && !empty($_POST['id_name_new_table'])) {
                $this->create_table_pk($_POST['table_name_new_table'], $_POST['id_name_new_table']);
            }
        }
    }
}
