<?php

namespace Punkode;

require_once PKDIR . '/includes/input-class.php';
class PUNKODE_PK extends INPUT_PK
{




    public function update_row_render_pk(string $table, string $submit = 'submit_edit_record_pk', array $filled = array())
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            var_dump($_POST);die();
            ($submit === '') ? $submit = $_POST['submit_edit_record_pk'] : $submit = $submit;  /* se il parametro submit viene inserito submit cambia valore con quello nuovo inserito altrimenti rimane quello di default*/
            if (isset($submit) && !empty($submit)) {
                $lenght_filled = count($filled);
                $tmp = 1;
                /*    Controllo che tutti i valori inseriti in filled e quindi quelli che vogliamo verificare che siano settati siano effettivamente stati compilati */
                for ($i = 0; $i < $lenght_filled; $i++) {
                    if (!isset($_POST[$filled[$i]]) && empty($_POST[$filled[$i]])) { /* se il valore non e settato o è vuoto tmp diventa 0 */
                        $tmp = 0;
                    }
                }

                if ($tmp === 1) {

                    $array_type_column = explode(',', $_POST['type_column']); /* trasformo in array la stringa con la lista del tipo colonne */
                    $array_key = array_keys($_POST); /* lista delle chiavi del post che corrisponde al nome dei field della tabella */
                    foreach ($_POST as  $value) {
                        $array_value[] = $value;
                    }


                    if (isset($table) && !empty($table)) { /* se table è stato inserito come parametro nella funzione lo utilizza */
                    } else {    /* altrimenti vuol dire che table non è stato specificato come parametro e prende quello di default cioe '' vuoto, quindi prendiamo il primo post che abbiamo dovuto inserire nel form di provenienza */
                        $table = $array_value[0];
                        array_shift($array_key);
                        array_shift($array_value);
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
                        header("Location: #");
                    }
                }
            }
        }
    }
}
