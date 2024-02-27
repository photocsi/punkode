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
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal<?php echo $id  ?>"><?php echo $label; ?> </button>

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
                                    $this->int_val_pk(
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
                                    $this->int_val_pk(
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
                        $this->submit_pk('Delete', 'delete_record', '1', array('s','danger'));

                        $this->submit_pk('Save', 'edit_record', '1', array('s','primary')); ?>
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
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#insertModal"><?php echo $label; ?> </button>

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
                                    $this->int_pk(
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
                                    $this->int_pk(
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
                        $this->submit_pk('Save', 'insert_record', '1', array('s','primary'));
                        ?>
                    </div>
                </div>
            </div>
        </div>
        </form>


    <?php


    }

    public function add_column_modal_pk(string $label = 'ADD COLUMN',)
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModaladd"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModaladd" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModaladd" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaladd"><?php echo "Aggiungi una nuova colonna"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->form_pk();
                        $this->hidden_pk('name_table', $this->table);
                        $this->text_pk('Nome', 'name_new_column', 'REQUIRED', array('s', 1));
                        $this->option_pk('Tipo', 'type_new_column', array('VARCHAR', 'INT', 'DATE', 'LONGTEXT'), 'REQUIRED', array('s', 1));
                        $this->int_pk('Lunghezza', 'length_new_column', '', array('s', 1));
                        $this->option_pk('Predefinito', 'predefinito_new_column', array('', 'NULL', 'CURRENT_TIMESTAMP'), '', array('s', 1));
                        $this->option_pk('Codifica', 'codifica_new_column', array('', 'utf8mb4_unicode_ci', 'utf8mb4_general_ci'), '', array('s', 1));
                        $this->option_pk(
                            'Attributi',
                            'attr_new_column',
                            array(
                                '',
                                'BINARY',
                                'UNSIGNED',
                                'UNSIGNED ZEROFILL',
                                'on update CURRENT_TIMESTAMP',
                                'COMPRESSED=zlib'
                            ),
                            '',
                            array('s', 1)
                        );
                        $this->option_pk('NULL', 'null_new_column', array('NULL', 'NOT NULL'), '', array('s', 1));
                        /*  $this->option_pk('Insert After','after_new_column',$array_information,'',array('s',1)); */
                        /* $this->option_pk('Indice','index_new_fiels',
    array(
      'PRIMARY',
      'UNIQUE',
      'INDEX',
      'FULLTEXT',
      'SPATIAL'
    ),
    '',
    array('s',1)
  ); 
  $this->bool_pk('AI','ai_new_field');
  $this->longText_pk('Commenti','comment_new_field','',array('s',1)); */


                        ?>
                    </div>
                    <div class="modal-footer">

                        <?php
                        $this->submit_pk('Aggiungi Campo', 'submit_add_new_column');

                        ?>
                    </div>
                </div>
            </div>
        </div>
        </form>
    <?php
    }


    public function delete_column_modal_pk(string $label = 'DELETE COLUMN',)
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModaldel"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModaldel" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModaldel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaldel"><?php echo "Elimina la colonna con tutti i suoi dati"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $array_information = array();
                        $information_table = $this->select_information_table_pk($this->table, '*');
                        for ($i = 0; $i < count($information_table); $i++) {
                            $array_information[] = $information_table[$i]['COLUMN_NAME'];
                        }
                        array_shift($array_information);

                        $this->form_pk();
                        $this->option_pk('Column', 'name_delete_column', $array_information, '', array('s', 1));

                        ?>
                    </div>
                    <div class="modal-footer">

                        <?php
                        $this->submit_pk('invia', 'submit_delete_column');

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
                <div class="row">
                    <div class="col-6" style="text-align:start ; padding-left: 1rem">
                    <?php echo $this->insert_modal_pk('Add Record'); ?>
                    </div>
                    <div class="col-6" style="text-align:end ; padding-right: 1rem">
                    <?php echo $this->add_column_modal_pk(); ?>
                <?php echo $this->delete_column_modal_pk(); ?>
                    </div>
                </div>
             
               
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