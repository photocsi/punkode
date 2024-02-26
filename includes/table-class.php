<?php
class TABLE_PK extends INPUT_PK
{
    private  $info_schema = array();  /* informazioni della tabella information_table */
    private  $select_all = array();     /*  tutti i record e tutti i campi della tabella */

    function __construct($table, $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

        parent::__construct($table, $db, $host, $user, $password);
        $this->info_schema = $this->select_information_table_pk($table, '*'); /* prendo le informazioni della tabella information_table */
        $this->select_all = $this->select_all_pk($table, '*');  /* selezione di tutti i record e tutti i campi della tabella */
        $this->view();
    }


    public function edit_modal_pk($id, string $label = 'Edit',)
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
                        $php_self = htmlspecialchars($_SERVER["PHP_SELF"]);
                        $this->form_pk($php_self);
                        $column_name_replace = str_replace('_', ' ', $this->info_schema[0]['COLUMN_NAME']);
                        echo " <input name='{$this->info_schema[0]['COLUMN_NAME']}'  type='number' value='$id' hidden> ";
                        $array_type_column = array();
                        $array_type_column[] = 'int';
                        for ($i = 1; $i < count($this->info_schema); $i++) {
                            $column_name_replace = str_replace('_', ' ', $this->info_schema[$i]['COLUMN_NAME']);
                            /* tolgo il trattino basso e sostituisco con lo spazio */
                            switch ($this->info_schema[$i]['COLUMN_TYPE']) {
                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'int':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->number_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );
                                    $array_type_column[] = 'int';
                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->text_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );
                                    $array_type_column[] = 'var';
                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->text_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );
                                    $array_type_column[] = 'tex';
                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyt':   /*controllo se la stringa column_type comincia con tinyt e quindi e un email*/
                                    $this->email_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );
                                    $array_type_column[] = 'tinyt';
                                    break;

                                case $this->info_schema[$i]['COLUMN_TYPE'] == 'tinyint(1)':   /*  valore booleano */
                                    $this->bool_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        ''
                                    );
                                    $array_type_column[] = 'tinyint(1)';
                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->number_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );
                                    $array_type_column[] = 'tinyi';
                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'dat':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->date_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );
                                    $array_type_column[] = 'dat';
                                    break;
                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'lon':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->longText_val_pk(
                                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                                        $id,                                  /* id del record */
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '', /* parametri vari */
                                        array('s')
                                    );
                                    $array_type_column[] = 'lon';
                                    break;
                            }
                        }
                        $string_type_column = implode(',', $array_type_column);
                        echo " <input  type='text' name='type_column' value='$string_type_column' hidden> ";

                        ?>

                    </div>
                    <div class="modal-footer">

                        <?php
                        $this->submit_pk('Delete', 'delete_record', '1', array('danger', 's'));

                        $this->submit_pk('Save', 'edit_record', '1', array('primary', 's')); ?>
                    </div>
                </div>
            </div>
        </div>
        </form>


    <?php


    }

    public function insert_modal_pk(string $label = 'Add Record',)
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
                        $php_self = htmlspecialchars($_SERVER["PHP_SELF"]);
                        $this->form_pk($php_self);
                        $column_name_replace = str_replace('_', ' ', $this->info_schema[0]['COLUMN_NAME']);
                        for ($i = 1; $i < count($this->info_schema); $i++) {
                            $column_name_replace = str_replace('_', ' ', $this->info_schema[$i]['COLUMN_NAME']);
                            /* tolgo il trattino basso e sostituisco con lo spazio */
                            switch ($this->info_schema[$i]['COLUMN_TYPE']) {
                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'int':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->number_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->text_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->text_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyt':   /*controllo se la stringa column_type comincia con tinyt e quindi e un email*/
                                    $this->email_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;

                                case $this->info_schema[$i]['COLUMN_TYPE'] == 'tinyint(1)':   /*  valore booleano */
                                    $this->bool_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        ''
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->number_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'dat':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->date_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;

                                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'lon':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                                    $this->longText_pk(
                                        $column_name_replace,                 /* label - nome colonna senza trattino */
                                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                                        '',
                                        array('s')
                                    );

                                    break;
                            }
                        }
                        ?>

                    </div>
                    <div class="modal-footer">

                        <?php
                        $this->submit_pk('Save', 'insert_record', '1', array('primary', 's'));
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
        if (isset($this->select_all[0])) {
            $lengt_select_all = count($this->select_all[0]) / 2;
        }
    ?>

        <div class="card  m-3">
            <h5 class="card-header text-center "><?php echo 'Tabella "' . $this->info_schema[0]["TABLE_NAME"] . '"';  ?></h5>
            <div class="card-header text-center">
                <?php echo $this->insert_modal_pk('Add Record'); ?>
            </div>
            <div class="card-body">
                <div class="table-responsive">
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
                                echo $this->edit_modal_pk($this->select_all[$r][0], 'Edit', 'edit');
                                echo '</td>';
                                for ($i = 0; $i < $lengt_select_all; $i++) {
                                    switch ($this->select_all[$r][$i]) {
                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'int':
                                            $int_validate = filter_var($this->select_all[$r][$i], FILTER_VALIDATE_INT);
                                            echo " <td>$int_validate</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'var':
                                            //code di escape
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'tex':
                                            //code di escape
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) === 'tinyt':
                                            if (filter_var($this->select_all[$r][$i], FILTER_VALIDATE_EMAIL)) {
                                                echo " <td> {$this->select_all[$r][$i]}</td>";
                                            } else {
                                                echo "<td>E-mail non valida</td>";
                                            }
                                            break;

                                        case $this->info_schema[$i]['COLUMN_TYPE'] == 'tinyint(1)':  /*  valore booleano */
                                            ($this->select_all[$r][$i] === 1) ? $value_check = 'X' : $value_check = 'O';
                                            echo " <td> $value_check </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyi':
                                            $int_validate = filter_var($this->select_all[$r][$i], FILTER_VALIDATE_INT);
                                            echo " <td>$int_validate</td>";
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'dat':
                                            //code di escape
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'lon':
                                            //code di escape
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;
                                    }
                                }
                                echo '</tr>';
                            } ?>


                        </tbody>
                    </table>
                </div>
            </div>
        </div>
<?php
    }
}




?>