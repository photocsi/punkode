<?php

namespace Punkode;

/* DESCRIZIONE CLASSE */
/* La classe table serve per creare in automatico la tabella front-end da una tabella del db
serve per creare dei form personalizzati in maniera veloce
e contiene il form preimpostato per la creazione di una nuova tabella nel db
contiene inoltre i modali da cui partono l'update , l'insert e il delet dei record della tabella
e i modali per aggiungere , eliminare o spostare una colonna della tabella nel db
------------------------------------------------------------------------------------------------
Le funzioni template contengono le funzioni per far partire i modali,
 dai modali partono le funzioni della classe manage_table che creano il form (utilizzando la classe input) per le varie modifiche
 dalle funzioni della classe manage_table tramite il form e la richiesta http si passa alla classe render
 la classe render prende tutti i dati post è li invia alla classe db che esegue tutte le query... 
 in mezzo si utilizza la classe safety per la sanificazione e validazione dei dati  */

require_once PKDIR . '/includes/input-class.php';
class TABLE_PK extends INPUT_PK
{
    public  $info_schema = array();  /* informazioni della tabella information_table */
    private  $select_all = array();     /*  tutti i record e tutti i campi della tabella */
    use MANAG_TABLE_PK;
    use SAFE_PK;
    use HOOK_PK;

    function __construct($table)
    {
        $this->table = $table;
        parent::__construct($this->table);
    }

    public function form_create_table(string $action = '#')
    {
        $this->pk_form($action);
        $this->pk_text('Name Table', 'table_name_new_table', 12, 'pattern="^[a-z A-Z]+$" REQUIRED');
        $this->pk_submit('Create', 'submit_create_new_table', array('s', 'primary', 12));
        $this->pk_end_form();
    }

    /* ****************** TABLE ASINC******************************************
    ************************************************************************ */
    public function pk_table(string $style = 'placeholder', string $element = 'all', array $exclude = array(), string $action = '#')
    {
        $this->info_schema = $this->pk_select_information_table('*','FETCH_ASSOC'); /* prendo le informazioni della tabella information_table */
        $select_all = $this->pk_select_all('*','FETCH_ASSOC');  /* selezione di tutti i record e tutti i campi della tabella */
        $key_column_name=$this->info_schema['COLUMN_NAME']; //prendo il primo valore della chiave column_name che sarebbe l'id, valore obbligatorio per ogni riga
        $this->pk_table_html($element, $action, $style);
       
        /*  $this->hook($this->name,$this->callback); */
?>
        <thead>
            <tr>
                <?php
                if ($element === 'all' || $element === 'edit') {
                    echo '<th>DELETE</th>';
                }

                $lenght_infoschema = count($this->info_schema["COLUMN_NAME"]);

                for ($i = 1; $i < $lenght_infoschema; $i++) {
                    if (!in_array($this->info_schema["COLUMN_NAME"][$i], $exclude)) {
                        echo "<th scope='col'>{$this->info_schema["COLUMN_NAME"][$i]}</th>";
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php 
            //utilizzo in pratica select_all['tutti i column name][0] prendo il primo column name che sarebbe il campo id della tabella (obbligatorio)
             if (isset($select_all[$key_column_name[0]]) && isset($this->info_schema['COLUMN_NAME'])) {
           
                $row_table = count($select_all[$key_column_name[0]]);
                $column_table = count($this->info_schema['COLUMN_NAME']);
      
            for ($r = 0; $r < $row_table; $r++) {
                echo '<tr>';
                if ($element === 'all' || $element === 'edit') { /* la scelta per inserire il pulsante edit oppure no */
            ?><td>
                        <div class="d-grid gap-2 d-md-flex justify-content-md-start">

                        <?php $this->pk_delete_modal($action, $select_all[$key_column_name[0]][$r], 'Delete',$style);
                        echo '</div></td>';
                    }
                    for ($i = 1; $i < $column_table; $i++) {

                        $column_name_replace = trim(str_replace('_', ' ', $this->info_schema['COLUMN_NAME'][$i]));

                        switch ($this->info_schema['COLUMN_TYPE'][$i]) {
                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 4) === 'bigi' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                echo '<td>';
                                $this->pk_int_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );

                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'int' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):

                                echo '<td>';
                                $this->pk_int_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'var' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                echo '<td>';
                                $this->pk_text_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 9) === 'char(255)' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  per la password */
                                echo '<td>';
                                $this->pk_password_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'cha' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  per char */
                                $validate = $this->pk_validate_var($select_all[$key_column_name[0]][$r]);
                                echo '<td>';
                                $this->pk_text_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'tex' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):

                                echo '<td>';
                                $this->pk_text_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) === 'tinyt' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude): /*  valore email */
                                echo '<td>';
                                $this->pk_email_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case $this->info_schema['COLUMN_TYPE'][$i] == 'tinyint(1)' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  valore booleano */
                                ($select_all[$key_column_name[$i]][$r] === 1) ? $value_check = 'X' : $value_check = 'O';
                                echo '<td>';
                                $this->pk_bool_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'tinyi' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):

                                echo '<td>';
                                $this->pk_text_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'flo' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  valore float */
                                echo '<td>';
                                $this->pk_flo_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'times' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude): /* VALORE DATA */
                                $validate = $this->pk_validate_date($select_all[$key_column_name[$i]][$r]); //se è un time stamp mostro tutte le cifre per mostrare anche ore min e secondi
                                echo '<td>' . $validate . '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'dat' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude): /* VALORE DATA */
                                echo '<td>';
                                $this->pk_date_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'lon' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                echo '<td>';
                                $this->pk_text_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 4) == 'json' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                echo '<td>';
                                $this->pk_text_asinc(
                                    $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                                    $select_all[$key_column_name[0]][$r],                                  /* id del record */
                                    $column_name_replace,                 /* label - nome colonna senza trattino */
                                    $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                                );
                                echo '</td>';
                                break;

                            default:
                                'Column Type non trovato';
                        }
                    }
                        ?>
                        </tr>

                    <?php
                }
            }
                unset($length_select_all);
                unset($number_row);
                    ?>

        </tbody>
        </table>
        </div>
        </div>
        </div>

    <?php

    }

    public function pk_table_classic(string $style = 'label', string $element = 'all', array $exclude = array(), string $action = '#')
    {

        $this->info_schema = $this->pk_select_information_table('*','FETCH_ASSOC'); /* prendo le informazioni della tabella information_table */
        $select_all = $this->pk_select_all('*','FETCH_ASSOC');  /* selezione di tutti i record e tutti i campi della tabella */
        $key_column_name=$this->info_schema['COLUMN_NAME']; //prendo il primo valore della chiave column_name che sarebbe l'id, valore obbligatorio per ogni riga
        $this->pk_table_html($element, $action, $style);

    ?>
        <thead>
            <tr>
                <?php
                if ($element === 'all' || $element === 'edit') {
                    echo '<th>Edit</th>';
                }

                $lenght_infoschema = count($this->info_schema['COLUMN_NAME']);
                for ($i = 0; $i < $lenght_infoschema; $i++) {
                    if (!in_array($this->info_schema["COLUMN_NAME"][$i], $exclude)) {
                        echo "<th scope='col'>{$this->info_schema["COLUMN_NAME"][$i]}</th>";
                    }
                }
                ?>
            </tr>
        </thead>
        <tbody>
            <?php
            if (isset($select_all[$key_column_name[0]]) && isset($this->info_schema['COLUMN_NAME'])) {
                $length_select_all = count($select_all[$key_column_name[0]]);
                $row_select_all = count($this->info_schema['COLUMN_NAME']);
                for ($r = 0; $r < $length_select_all; $r++) {
                    echo '<tr>';
                    if ($element === 'all' || $element === 'edit') { /* la scelta per inserire il pulsante edit oppure no */
                ?><td>
                            <div class="d-grid gap-2 d-md-flex justify-content-md-start">
                            <?php $this->pk_update_modal($action, $select_all[$key_column_name[0]][$r], 'Edit', $style);
                            $this->pk_delete_modal($action, $select_all[$key_column_name[0]][$r], 'Delete',$style);
                            echo '</div></td>';
                        }
    
                        for ($i = 0; $i < $row_select_all; $i++) {
                            switch ($this->info_schema['COLUMN_TYPE'][$i]) {
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 4) === 'bigi' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $this->pk_validate_int($select_all[$key_column_name[$i]][$r]);
                                    echo " <td>$validate</td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'int' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $this->pk_validate_int($select_all[$key_column_name[$i]][$r]);
                                    echo " <td>$validate</td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'var' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $this->pk_validate_var($select_all[$key_column_name[$i]][$r]);
                                    echo "<td> $validate </td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 9) === 'char(255)' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  per la password */
                                    $validate = $this->pk_validate_pass($select_all[$key_column_name[$i]][$r]);
                                    echo " <td> ***** </td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'cha' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  per char */
                                    $validate = $this->pk_validate_var($select_all[$key_column_name[$i]][$r]);
                                    echo " <td> $validate </td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) === 'tex' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $this->pk_validate_var($select_all[$key_column_name[$i]][$r]);
                                    echo " <td> $validate</td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) === 'tinyt' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude): /*  valore email */
                                    if ($this->pk_validate_email($select_all[$key_column_name[$i]][$r])) {
                                        $validate = $this->pk_validate_email($select_all[$key_column_name[$i]][$r]);
                                        echo "<td>$validate</td>";
                                    } else {
                                        echo "<td>E-mail non valida</td>";
                                    }
                                    break;
    
                                case $this->info_schema['COLUMN_TYPE'][$i] == 'tinyint(1)' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  valore booleano */
                                    ($select_all[$key_column_name[$i]][$r] === 1) ? $value_check = 'X' : $value_check = 'O';
                                    echo " <td> $value_check </td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'tinyi' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $this->pk_validate_int($select_all[$key_column_name[$i]][$r]);
                                    echo " <td>$validate</td>";
    
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'flo' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):  /*  valore booleano */
                                    $validate = $this->pk_validate_float($select_all[$key_column_name[$i]][$r]);
                                    echo " <td>$validate</td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'times' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude): /* VALORE DATA */
                                    $validate = $this->pk_validate_date($select_all[$key_column_name[$i]][$r]); //se è un time stamp mostro tutte le cifre per mostrare anche ore min e secondi
                                    echo " <td> $validate </td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'dat' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude): /* VALORE DATA */
                                    $validate = substr($this->pk_validate_date($select_all[$key_column_name[$i]][$r]), 0, 9); //se è una data mostro solo le prime 9 cifre
                                    echo " <td> $validate </td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'lon' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $this->pk_validate_var($select_all[$key_column_name[$i]][$r]);
                                    echo " <td> $validate</td>";
                                    break;
    
                                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 4) == 'json' && !in_array($this->info_schema['COLUMN_NAME'][$i], $exclude):
                                    $validate = $select_all[$key_column_name[$i]][$r];
                                    echo " <td> $validate</td>";
                                    break;
    
                                default:
                                    'Column Type non trovato';
                            }
                        }
                            ?>
                            </tr>
    
                        <?php  }
            }
    
           
                unset($length_select_all);
                unset($number_row);
                    ?>

        </tbody>
        </table>
        </div>
        </div>
        </div>

    <?php

    }


    public function pk_update_modal(string $action, int $id, string $label , string $style)
    {
    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal<?php echo $id  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModal<?php echo $id  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel"><?php echo "ID $id - Table $this->table"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->pk_update_row_manag($id, $action, $style);
                        ?>
                    </div>
                    <div class="modal-footer">
                    </div>
                </div>
            </div>
        </div>
        </form>
    <?php

    }

    public function insert_asinc()
    {
        $this->pk_button('Add Record', PKURLMAIN, 'submit', 'punkode_add,' . $this->table . ',' . PKURLMAIN, '');
    }

    public function pk_insert_modal(string $action, string $label = 'Add Record', string $style = '')
    {
    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#insertModal<?php echo $this->table  ?>"><?php echo $label; ?> </button>
        <!-- Modal -->
        <div class="modal fade" id="insertModal<?php echo $this->table  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="insertModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="insertModalLabel"><?php echo 'Table ' . $this->table; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <?php
                        $this->pk_insert_row_manag($action, $style)
                        ?>

                    </div>
                    <div class="modal-footer">


                    </div>
                </div>
            </div>
        </div>
        </form>


    <?php


    }

    public function pk_delete_modal(string $action, int $id, string $label , string $style )
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModalDelete<?php echo $id  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModalDelete<?php echo $id  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel"><?php echo "ID $id - Table $this->table"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <h5>Conferma cancellazione riga</h5>
                        <?php
                        $this->pk_delete_row_manag($id, $action, $style);
                        ?>
                    </div>
                    <div class="modal-footer">


                    </div>
                </div>
            </div>
        </div>
        </form>


    <?php


    }

    public function pk_add_column_modal(string $action, string $label = 'ADD COLUMN', string $style = '')
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModaladd<?php echo $this->table  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModaladd<?php echo $this->table  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModaladd" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaladd"><?php echo "Aggiungi una nuova colonna"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->pk_add_column_manag($action, $style);
                        ?>
                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>
        
    <?php
    }


    public function pk_remove_column_modal(string $action, string $label = 'DELETe COLUMN', string $style = '')
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModaldel<?php echo $this->table  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModaldel<?php echo $this->table  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModaldel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaldel"><?php echo "Elimina la colonna con tutti i suoi dati"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->pk_remove_column_manag($action, $style);
                        ?>
                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>
        </form>
    <?php
    }

    public function pk_move_column_modal(string $action, string $label = 'MOVE COLUMN', string $style = '')
    {
    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModalmove<?php echo $this->table  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModalmove<?php echo $this->table  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalmove" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaladd"><?php echo "Sposta colonna"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->pk_move_column_manag($action, $style);
                        ?>
                    </div>
                    <div class="modal-footer">

                    </div>
                </div>
            </div>
        </div>
        </form>
<?php
    }
}
