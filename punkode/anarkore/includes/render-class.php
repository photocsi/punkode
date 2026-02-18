<?php

namespace Punkode;

use Exception;

require_once PKDIR . '/includes/db_pdo-class.php';
require_once PKDIR . '/includes/tool-trait-class.php';
require_once PKDIR . '/includes/upload-trait-class.php';
class RENDER_PK extends INPUT_PK
{
    public  $info_schema = array();
    use SAFE_PK;
    use TOOL_PK;
    use UPLOAD_PK;

    function __construct(string $table)
    {

        parent::__construct($table);


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            switch ($_POST) {
                case isset($_POST['pk_submit_edit_record']) && $_POST['pk_submit_edit_record'] === '1'&& isset($_POST['pk_name_update_table']) && $this->table == $_POST['pk_name_update_table']:
                    $this->pk_update_row_render();
                    break;

                case isset($_POST['pk_submit_insert_record']) && $_POST['pk_submit_insert_record'] === '1' && isset($_POST['pk_name_insert_table']) && $this->table == $_POST['pk_name_insert_table']:
                    $this->pk_insert_row_render();
                    break;

                case isset($_POST['pk_submit_delete_record']) && !empty($_POST['pk_submit_delete_record']) && isset($_POST['pk_name_delete_table']) && $this->table == $_POST['pk_name_delete_table']:
                    $this->pk_delete_row_render();
                    break;
                    /* controllare gli isset da qui */
                case  isset($_POST['pk_name_new_column']) && !empty($_POST['pk_name_new_column']) && isset($_POST['pk_name_add_table']) && $this->table == $_POST['pk_name_add_table']:
                    $this->pk_add_column_render();
                    break;

                case  isset($_POST['pk_name_delete_column']) && !empty($_POST['pk_name_delete_column']) && isset($_POST['pk_name_remove_table']) && $this->table == $_POST['pk_name_remove_table']:
              
                    $this->pk_remove_column_render();
                    break;

                case isset($_POST['pk_submit_move_column']) && !empty($_POST['pk_submit_move_column']) && isset($_POST['pk_name_move_table']) && $this->table == $_POST['pk_name_move_table']:
                    $this->pk_move_column_render();
                    break;

                case   isset($_POST['pk_table_name_new_table']) && !empty($_POST['pk_table_name_new_table']):
                    $this->pk_render_new_table();
                    break;

                     default:
              
                    break;
            }
        }
    }


    public function pk_render_up(string $name_submit, string $where_field , string $where_value )
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST[$name_submit]) && !empty($_POST[$name_submit])) {
                $array_type_column = array();
                $array_key = array();
                $array_value = array();

                $type_column_tmp = $this->pk_select_one_information_table($where_field,'FETCH_ASSOC'); // prendo le information table del campo where
                if ($type_column_tmp != NULL) {
                    $array_type_column[] = TOOL_PK::set_column_type($type_column_tmp[0]['COLUMN_TYPE']); //prendo il type column del campo where
                }
                foreach ($_POST as $key => $value) {
                    $type_column_tmp = $this->pk_select_one_information_table($key,'FETCH_ASSOC'); //per ogni campo prendo tutte le informatio table

                    if ($type_column_tmp != NULL) {
                        $array_type_column[] = TOOL_PK::set_column_type($type_column_tmp[0]['COLUMN_TYPE']);/*  prendo tutti i type_column dei valori post passati escluso il submit */

                        $array_key[] = $key; /* Metto in un array tutte le le chiavi del post che vengono passati escluso il submit */
                        $array_value[] = $value;
                    }
                }

                array_unshift($array_value, $where_value);   /*  perche il for comincia da 1 per esigenze del render preimpostato */
                array_unshift($array_key, $where_field);

               $result_file= $this->pk_update_row_render($array_type_column, $array_key, $array_value);
            }
        }
        return $result_file;
    }

    public function pk_update_row_render(array $array_type = array(), array $array_key = array(), array $array_value = array())
    {
        if (isset($_POST['type_column'])) {   // PREIMPOSTATO SE $_POST[type_colum] è settato vuol dire che arriva dai render preimpostati 
            $array_type_column = explode(',', $_POST['type_column']); // trasformo in array la stringa con la lista del tipo colonne 
            unset($_POST['pk_name_update_table']);
            $array_key = array_keys($_POST); // lista delle chiavi del post che corrisponde al nome dei field della tabella 
            
            foreach ($_POST as  $value) {
                $array_value[] = $value;
            }
        } else { // PERONALIZATO altrimenti significa che arriva dal render personalizzato 
            $array_key = $array_key;
            $array_type_column = $array_type;
        }
        $lenght_type_column = count($array_type_column);
        for ($i = 1; $i <  $lenght_type_column; $i++) { //prendo la lunghezza del type column partendo da 
            match ($array_type_column[$i]) {
                'int', 'bigi' => $value_sanitiz = $this->pk_sanitize_int($array_value[$i]),
                'flo' =>  $value_sanitiz = $this->pk_sanitize_float($array_value[$i]),
                'var', 'tex', 'lon' =>    $value_sanitiz = $this->pk_sanitize_var($array_value[$i]),
                'char(255)' =>  $value_sanitiz = $this->pk_sanitize_pass($array_value[$i]),
                'cha' =>  $value_sanitiz = $this->pk_sanitize_cha($array_value[$i]),
                'tinyt' => $value_sanitiz = $this->pk_sanitize_email($array_value[$i]),
                'tinyint(1)', 'tinyi' =>   $value_sanitiz = $this->pk_sanitize_int($array_value[$i]),  //da controllare
                'dat', 'times' => $value_sanitiz = $this->pk_sanitize_date($array_value[$i]),
                'json' => $value_sanitiz = $array_value[$i],
            };
            if ($array_key[$i] != 'type_column') { /* type column e un campo nascosto nel form di arrivo per indentificare la tipologia della colonna */
                $this->pk_update($array_key[$i], $value_sanitiz, $array_key[0], $array_value[0]); // per ogni valore faccio l'update classe DB
            }
        }
/*  SE SONO PRESENTI DEI FILE */

if(isset($_FILES) && !empty($_FILES)){ //se esiste e non è vuoto


return $_FILES;
}


          header("Location: #");
    }


    public function pk_render_in()
    {
        
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
                $array_type_column = array();
                $array_key = array();
                $array_value = array();
                foreach ($_POST as $key => $value) {
                    $type_column_tmp = $this->pk_select_one_information_table($key,'FETCH_ASSOC');

                    if ($type_column_tmp != NULL) {
                        $array_type_column[] = TOOL_PK::pk_set_column_type($type_column_tmp['COLUMN_TYPE'][0]);/*  prendo tutti i type_column dei valori post passati escluso il submit e li setto con le prime lettere del type */

                        $array_key[] = $key; /* Metto in un array tutte le chiavi del post che vengono passati escluso il submit */
                        $array_value[] = $value; /* Metto in un array tutte i valori del post che vengono passati escluso il submit */
                    }
                }

                $this->pk_insert_row_render($array_type_column, $array_key, $array_value); //inserisco i dati nel db
            
        }
    }

    public function pk_insert_row_render(array $array_type_column = array(), array $array_key = array(), array $array_value = array())
    {
        if (isset($_POST['type_column'])) { //questo per preimpostato
            $array_type_column = explode(',', $_POST['type_column']);
            unset($_POST['pk_name_insert_table']);
            $array_key = array_keys($_POST);
            foreach ($_POST as  $value) {
                $array_value[] = $value;
            }
            array_pop($array_key);/*  elimino l'ultima chiave del valore post il submit */
            array_pop($array_value);  /* elimino l'ultimo valore del post il submit */
            array_pop($array_key);/*  elimino l'ultima chiave del valore post lista di tipo column */
            array_pop($array_value);  /* elimino l'ultimo valore del post lista di tipo column */

            
        } else {
            $array_type_column = explode(',', $_POST['type_column']); /* trasformo in array la stringa con la lista del tipo colonne */
        }


        $array_value_sanitizie = array();
        $lenght_type_column = count($array_type_column);
        for ($i = 0; $i <  $lenght_type_column; $i++) { //prendo la lunghezza del type column e tolgo il primo valore che sarebbe l'id

            match ($array_type_column[$i]) {
                'int', 'bigi' => $value_sanitiz = $this->pk_sanitize_int($array_value[$i]),
                'flo' =>  $value_sanitiz = $this->pk_sanitize_float($array_value[$i]),
                'var', 'tex', 'lon' =>    $value_sanitiz = $this->pk_sanitize_var($array_value[$i]),
                'char(255)' =>  $value_sanitiz = $this->pk_sanitize_pass($array_value[$i]),
                'cha' =>  $value_sanitiz = $this->pk_sanitize_cha($array_value[$i]),
                'tinyt' => $value_sanitiz = $this->pk_sanitize_email($array_value[$i]),
                'tinyint(1)', 'tinyi' =>   $value_sanitiz = $this->pk_sanitize_email($array_value[$i]),
                'dat', 'times' => $value_sanitiz = $this->pk_sanitize_date($array_value[$i]),
                'json' => $value_sanitiz = $array_value[$i],
            };

            $array_value_sanitizie[] = $value_sanitiz;
        }
        $this->pk_insert($array_key, $array_value_sanitizie);


        /* header("Location: #"); */
    }

    public function pk_render_delete(string $name_submit)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST[$name_submit]) && !empty($_POST[$name_submit])) {
                $this->pk_delete_row_render($this->table);
            }
        }
    }


    public function pk_delete_row_render()
    {

        $array_key = array_keys($_POST);
        foreach ($_POST as  $value) {
            $array_value[] = $value;
        }
        if (empty($table)) {
            $table = $array_value[0];
            $where = $array_key[1];
            $value = $array_value[1];
        } else {
            $table = $table;
            $where = $array_value[0];
            $value = $array_value[1];
        }

        $this->pk_delete($where, $value);
        header("Location: #");
    }


    public function pk_add_column_render()
    {

        $name_new_column = $this->pk_sanitizie_name_column($_POST['pk_name_new_column']);
        $type_new_column = $_POST['type_new_column'];
        $length_new_column = $_POST['length_new_column'];
        $predefinito_new_column = $_POST['predefinito_new_column'];
        $attr_new_column = $_POST['attr_new_column'];
        $null_new_column = $_POST['null_new_column'];
        $codifica_new_column = $_POST['codifica_new_column'];
        $this->pk_create_column($this->table, $name_new_column, $type_new_column, $length_new_column, $predefinito_new_column, $codifica_new_column, $attr_new_column, $null_new_column);
        header("Location: #");
    }

    public function pk_remove_column_render()
    {
        $name_column = $_POST['pk_name_delete_column'];

        $table = $_POST['name_table_remove'];

        $this->pk_remove_column($name_column);
        header("Location: #");
    }


    public function pk_move_column_render()
    {
        $name_column = $_POST['pk_name_move_column'];
        $after_column = $_POST['pk_name_after_column'];

        $this->info_schema = $this->pk_select_one_information_table($name_column,'FETCH_ASSOC'); /* "prendo tutte le information table del campo selezionato da spostare" */
        $type = $this->info_schema['COLUMN_TYPE'][0];
        $this->pk_move_column($name_column, $after_column, $type);

        header("Location: #");
    }

    public function pk_import_files($array_files)
    {
        /* foreach($array_files as $file)
    copy($file['tmp_name'], "$path" . $file['name']); */
    }


    public function pk_render_new_table()
    {
        $name_table = $this->pk_sanitizie_name_column($_POST['table_name_new_table']);
        $this->pk_create_table($name_table);
    }
}
