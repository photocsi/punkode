<?php
class TABLE_CSI extends INPUT_CSI
{
    private  $info_schema = array();  /* prendo le informazioni della tabella information_table */
    private  $select_all = array();     /* selezione di tutti i record e tutti i campi della tabella */

    function __construct($table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

        parent::__construct($table, $db, $host, $user, $password);
        $this->info_schema = $this->select_information_table($table, '*');
        $this->select_all = $this->select_all($table, '*');
        $this->view();
    }


    public function edit_modal($id, string $label = 'Edit',)
    {

?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal<?php echo $id  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModal<?php echo $id  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel"><?php echo "ID $id - Table {$this->info_schema[0]['TABLE_NAME']}"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <?php

                        $this->form();

                        $column_name_replace = str_replace('_', ' ', $this->info_schema[0]['COLUMN_NAME']);
                        echo " <input name='{$this->info_schema[0]['COLUMN_NAME']}'  type='number' value='$id' hidden> ";
                        for ($i = 1; $i < count($this->info_schema); $i++) {
                            $column_name_replace = str_replace('_', ' ', $this->info_schema[$i]['COLUMN_NAME']);
                            /* tolgo il trattino basso e sostituisco con lo spazio */
                            switch ($this->info_schema[$i]['COLUMN_TYPE']) {
                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'int':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->input_number_value(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        array('s')
                                    );
                                    break;

                                    case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                        $this->input_text_value(
                                            $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                            $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                            $id,                                  /* id del record */
                                            $column_name_replace,                 /* label - nome colonna senza trattino */
                                            $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                            array('s')
                                        );
                                        break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->input_text_value(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        array('s')
                                    );
                                    break;
                            }
                        }

                        ?>

                    </div>
                    <div class="modal-footer">

                        <?php
                        $this->button_submit('Delete', 'delete_record',array('danger','s'));

                        $this->button_submit('Save', 'edit_record', array('primary','s')); ?>
                    </div>
                </div>
            </div>
        </div>
        </form>


    <?php


    }

    public function insert_modal(string $label = 'Add Record',)
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#insertModal"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="insertModal" data-bs-keyboard="false" tabindex="-1" aria-labelledby="insertModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="insertModalLabel"><?php echo " Table {$this->info_schema[0]['TABLE_NAME']}"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <?php

                        $this->form();
                        $column_name_replace = str_replace('_', ' ', $this->info_schema[0]['COLUMN_NAME']);
                        for ($i = 1; $i < count($this->info_schema); $i++) {
                            $column_name_replace = str_replace('_', ' ', $this->info_schema[$i]['COLUMN_NAME']);
                            /* tolgo il trattino basso e sostituisco con lo spazio */
                            switch ($this->info_schema[$i]['COLUMN_TYPE']) {
                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'int':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->input_number(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        array('s')
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->input_text(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        array('s')
                                    );

                                    break;

                                    case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                        $this->input_text(
                                            $column_name_replace,                 /* label - nome colonna senza trattino */
                                            $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                            array('s')
                                        );
    
                                        break;
                            }
                        }

                        ?>




                    </div>
                    <div class="modal-footer">

                        <?php
                       $this->button_submit('Save', 'insert_record', array('primary','s'));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        </form>


    <?php


    }

    public function view()
    {

        $lengt_select_all = count($this->select_all[0]) / 2;

    ?>

        <div class="card  m-3">
            <h5 class="card-header text-center "><?php echo 'Tabella "' . $this->info_schema[0]["TABLE_NAME"] . '"';  ?></h5>
            <div class="card-header text-center">
                <?php echo $this->insert_modal('Add Record'); ?>
            </div>
            <div class="card-body">
                <table class="table table-bordered border-primary">
                    <thead>
                        <tr>
                            <th>Edit</th>
                            <?php
                            for ($i = 0; $i < count($this->info_schema); $i++) {
                                echo "<th scope='col'>{$this->info_schema[$i]["COLUMN_NAME"]}</th>";
                            } ?>

                        </tr>
                    </thead>
                    <tbody>

                        <?php for ($r = 0; $r < count($this->select_all); $r++) {
                            echo '<tr><td>';
                            echo $this->edit_modal($this->select_all[$r][0], 'Edit', 'edit');
                            echo '</td>';
                            for ($i = 0; $i < $lengt_select_all; $i++) {
                                echo " <td> {$this->select_all[$r][$i]}</td>";
                            }
                            echo '</tr>';
                        } ?>


                    </tbody>
                </table>
            </div>
        </div>
<?php
    }
}




?>