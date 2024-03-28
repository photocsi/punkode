<?php

namespace Punkode;

require_once PKDIR . '/includes/db_pdo-class.php';
require_once PKDIR . '/includes/tool-trait-class.php';
class RENDER_PK extends INPUT_PK
{
    public  $info_schema = array();
    use SAFE_PK;
    use TOOL_PK;

    function __construct(string $table = '', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {


        parent::__construct($table, $db, $host, $user, $password);


        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            switch ($_POST) {
                case isset($_POST['submit_edit_record_pk']) && $_POST['submit_edit_record_pk'] === '1' && isset($_POST['name_update_table_pk']) && !empty($_POST['name_update_table_pk']):
                    $this->update_row_render_pk($table);
                    break;

                case isset($_POST['submit_insert_record_pk']) && $_POST['submit_insert_record_pk'] === '1' && isset($_POST['name_insert_table_pk']) && !empty($_POST['name_insert_table_pk']):
                    $this->insert_row_render_pk($table);
                    break;

                case   isset($_POST['submit_delete_record_pk']) && !empty($_POST['submit_delete_record_pk']) && isset($_POST['name_delete_table_pk']) && !empty($_POST['name_delete_table_pk']):
                    $this->delete_row_render_pk($table);
                    break;
                    /* controllare gli isset da qui */
                case  isset($_POST['name_new_column']) && !empty($_POST['name_new_column']) && !empty($_POST['type_new_column']) && isset($_POST['name_table_pk']) && !empty($_POST['name_table_pk']):
                    $this->add_column_render_pk($table);
                    break;

                case    isset($_POST['name_delete_column']) && !empty($_POST['name_delete_column']) && isset($_POST['submit_delete_column']) && isset($_POST['name_table_remove']) && !empty($_POST['name_table_remove']):
                    $this->remove_column_render_pk($table);
                    break;

                case   isset($_POST['submit_move_column_pk']) && isset($_POST['name_table_move_pk']) && !empty($_POST['name_table_move_pk']):
                    $this->move_column_render_pk($table);
                    break;

                case     isset($_POST['table_name_new_table']) && !empty($_POST['table_name_new_table']) && !empty($_POST['id_name_new_table']):
                    $this->create_table_render_pk();
                    break;
            }
        }
    }

    public function render_update_pk(string $table, string $name_submit, string $where_field = '', string $where_value = '')
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST[$name_submit]) && !empty($_POST[$name_submit])) {
                $array_type_column = array();
                $array_key = array();
                $array_value = array();
                foreach ($_POST as $key => $value) {
                    $type_column_tmp = $this->select_one_information_table_pk($table, $key);

                    if ($type_column_tmp != NULL) {
                        $array_type_column[] = TOOL_PK::set_column_type_pk($type_column_tmp[0]['COLUMN_TYPE']);/*  prendo tutti i type_column dei valori post passati escluso il submit */

                        $array_key[] = $key; /* Metto in un array tutte le le chiavi del post che vengono passati escluso il submit */
                        $array_value[] = $value;
                    }
                }
                array_unshift($array_type_column, '0'); /* aggiungo un valore a caso all'inizio dell'array */
                array_unshift($array_value, $where_value);   /*  perche il for comincia da 1 per esigenze del render preimpostato */
                array_unshift($array_key, $where_field);

                $this->update_row_render_pk($table, $array_type_column, $array_key, $array_value);
            }
        }
    }

    public function update_row_render_pk($table, array $array_type = array(), array $array_key = array(), array $array_value = array())
    {
        /*  se $_POST[type_colum] è settato vuol dire che arriva dai render preimpostati */
        if (isset($_POST['type_column'])) {
            $array_type_column = explode(',', $_POST['type_column']); /* trasformo in array la stringa con la lista del tipo colonne */
            $array_key = array_keys($_POST); /* lista delle chiavi del post che corrisponde al nome dei field della tabella */
            foreach ($_POST as  $value) {
                $array_value[] = $value;
            }
            if (isset($table) && !empty($table)) { /* se table è stato inserito come parametro nella funzione lo utilizza */
                /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto,
                   quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza, quindi l'else e per i render preimpostati */
            } else {
                $table = $array_value[0]; /* Questo valore corrisponde a name_update_table_pk che sarebbe il nome della tabella */
                array_shift($array_key); /* tolgo il primo elemento dell'array cioè il nome tabella  */
                array_shift($array_value); /* tolgo il primo elemento dell'array cioè il nome tabella */
            }
        } else { /*  altrimenti significa che arriva dal render personalizzato */
            $array_key = $array_key;
            $array_type_column = $array_type;
        }

        $lenght_type_column = count($array_type_column);
        for ($i = 1; $i <  $lenght_type_column; $i++) { //prendo la lunghezza del type column e tolgo il primo valore che sarebbe l'id
            switch ($array_type_column[$i]) {
                case 'int':
                    $value_sanitiz = $this->sanitize_int_pk($array_value[$i]);
                    break;
                case 'var':
                    $value_sanitiz = $this->sanitize_var_pk($array_value[$i]);
                    break;

                case 'char(255)':
                    $value_sanitiz = $this->sanitize_pass_pk($array_value[$i]);
                    break;

                case 'cha':
                    $value_sanitiz = $this->sanitize_cha_pk($array_value[$i]);
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

        header("Location: #");
    }


    public function render_insert_pk(string $table, string $name_submit)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST[$name_submit]) && !empty($_POST[$name_submit])) {
                $array_type_column = array();
                $array_key = array();
                $array_value = array();
                foreach ($_POST as $key => $value) {
                    $type_column_tmp = $this->select_one_information_table_pk($table, $key);

                    if ($type_column_tmp != NULL) {
                        $array_type_column[] = TOOL_PK::set_column_type_pk($type_column_tmp[0]['COLUMN_TYPE']);/*  prendo tutti i type_column dei valori post passati escluso il submit e li setto con le prime lettere del type */

                        $array_key[] = $key; /* Metto in un array tutte le chiavi del post che vengono passati escluso il submit */
                        $array_value[] = $value; /* Metto in un array tutte i valori del post che vengono passati escluso il submit */
                    }
                }


                $this->insert_row_render_pk($table, $array_type_column, $array_key, $array_value);
            }
        }
    }

    public function insert_row_render_pk($table, array $array_type_column = array(), array $array_key = array(), array $array_value = array())
    {
        if (isset($_POST['type_column'])) {
            $array_type_column = explode(',', $_POST['type_column']);
            $array_key = array_keys($_POST);
            foreach ($_POST as  $value) {
                $array_value[] = $value;
            }

            array_pop($array_key);/*  elimino l'ultima chiave del valore post il submit */
            array_pop($array_value);  /* elimino l'ultimo valore del post il submit */
            array_pop($array_key);/*  elimino l'ultima chiave del valore post lista di tipo column */
            array_pop($array_value);  /* elimino l'ultimo valore del post lista di tipo column */

            if (isset($table) && !empty($table)) {
                array_shift($array_key); /* elimino il primo elemento dell'array che sarebbe table */
                array_shift($array_value);
            } else {
                $table = $array_value[0]; /* prima setto table prendendolo dal campo nascosto e poi lo elomino dall'array */
                array_shift($array_key); /* elimino il primo elemento dell'array che sarebbe table */
                array_shift($array_value);
            }
        } else {
            $array_type_column = $array_type_column; /* trasformo in array la stringa con la lista del tipo colonne */
        }


        $array_value_sanitizie = array();
        $lenght_type_column = count($array_type_column);
        for ($i = 0; $i <  $lenght_type_column; $i++) { //prendo la lunghezza del type column e tolgo il primo valore che sarebbe l'id
            switch ($array_type_column[$i]) {
                case 'int':
                    $value_sanitiz = $this->sanitize_int_pk($array_value[$i]);
                    break;
                case 'var':
                    $value_sanitiz = $this->sanitize_var_pk($array_value[$i]);
                    break;

                case 'char(255)':
                    $value_sanitiz = $this->sanitize_pass_pk($array_value[$i]);
                    break;

                case 'cha':
                    $value_sanitiz = $this->sanitize_cha_pk($array_value[$i]);
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
            $array_value_sanitizie[] = $value_sanitiz;
        }

        $this->insert_pk($table, $array_key, $array_value_sanitizie);


        header("Location: #");
    }

    public function render_delete_pk(string $table, string $name_submit)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST[$name_submit]) && !empty($_POST[$name_submit])) {
                $this->delete_row_render_pk($table);
            }
        }
    }


    public function delete_row_render_pk(string $table)
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

        $this->delete_pk($table, $where, $value);
        header("Location: #");
    }


    public function add_column_render_pk($table)
    {

        if (isset($table) && !empty($table)) {
        } else {
            $table = $_POST['name_table_pk'];
        }
        $name_new_column = $this->sanitizie_only_char($_POST['name_new_column']);
        $type_new_column = $_POST['type_new_column'];
        $length_new_column = $_POST['length_new_column'];
        $predefinito_new_column = $_POST['predefinito_new_column'];
        $attr_new_column = $_POST['attr_new_column'];
        $null_new_column = $_POST['null_new_column'];
        $codifica_new_column = $_POST['codifica_new_column'];
        $this->create_column_pk($table, $name_new_column, $type_new_column, $length_new_column, $predefinito_new_column, $codifica_new_column, $attr_new_column, $null_new_column);
        header("Location: #");
    }

    public function remove_column_render_pk($table)
    {
        $name_column = $_POST['name_delete_column'];
        if (isset($table) && !empty($table)) { /* se table è stato inserito come parametro nella funzione lo utilizza */
        } else {    /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto, quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza */
            $table = $_POST['name_table_remove'];
        }
        $this->remove_column_pk($table, $name_column);
        header("Location: #");
    }


    public function move_column_render_pk($table)
    {
        $name_column = $_POST['name_move_column_pk'];
        $after_column = $_POST['name_after_column_pk'];

        if (isset($table) && !empty($table)) { /* se table è stato inserito come parametro nella funzione lo utilizza */
        } else {    /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto, quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza */
            $table = $_POST['name_table_move_pk'];
        }
        $this->info_schema = $this->select_one_information_table_pk($table, $name_column); /* "prendo tutte le information table del campo selezionato da spostare" */
        $type = $this->info_schema[0]['COLUMN_TYPE'];
        $this->move_column_pk($table, $name_column, $after_column, $type);

        header("Location: #");
    }




    public function create_table_render_pk()
    {
        $this->create_table_pk($_POST['table_name_new_table'], $_POST['id_name_new_table']);
    }
}
