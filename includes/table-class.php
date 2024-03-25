<?php

namespace Punkode;

require_once PKDIR . '/includes/input-class.php';
class TABLE_PK extends INPUT_PK
{
    private  $info_schema = array();  /* informazioni della tabella information_table */
    private  $select_all = array();     /*  tutti i record e tutti i campi della tabella */
    use MANAG_TABLE_PK;
    use SAFE_PK;

    function __construct($table = '', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

        parent::__construct($table, $db, $host, $user, $password);
    }

    public function template_create_table_pk(string $action = '#')
    {
        $this->form_pk($action, 4);
        $this->text_pk('Name Table', 'table_name_new_table', '', 'pattern="^[a-z A-Z]+$" REQUIRED', array('s', 12));
        $this->text_pk('Name id', 'id_name_new_table', '', 'REQUIRED', array('s', 12));
        $this->submit_pk('Create', 'submit_create_new_table', array('s', 'primary', 12));
        $this->end_form_pk();
    }

    public function template_classic_pk(string $table, string $element = 'all', array $exclude = array(), string $action = '#')
    {

        $this->info_schema = $this->select_information_table_pk($table, '*'); /* prendo le informazioni della tabella information_table */
        $select_all = $this->select_all_pk($table, '*');  /* selezione di tutti i record e tutti i campi della tabella */
        if (isset($select_all[0])) {
            $length_select_all = count($select_all[0]) / 2;
        }

?>

        <div class="card  m-3">
            <a href=<?php __FILE__ ?>>
                <h5 class="card-header text-center "><?php echo 'Tabella "' . $this->info_schema[0]["TABLE_NAME"] . '"';  ?></h5>
            </a>
            <div class="card-header text-center">
                <div class="row">
                    <div class="col-4" style="text-align:start ; padding-left: 1rem">
                        <?php ($element === 'all' || $element === 'edit') ? $this->insert_modal_pk($table, $action, 'Add Record') : null ?>
                    </div>
                    <div class="col-5" style="text-align:start ; padding-left: 1rem">

                    </div>
                    <div class="col-1" style="text-align:end ; padding-right: 1rem">
                        <?php
                        ($element === 'all') ? $this->add_column_modal_pk($action) : null;
                        ?>
                    </div>
                    <div class="col-1" style="text-align:end ; padding-right: 1rem">
                        <?php

                        ($element === 'all') ? $this->remove_column_modal_pk($action) : null;

                        ?>
                    </div>
                    <div class="col-1" style="text-align:end ; padding-right: 1rem">
                        <?php

                        ($element === 'all') ? $this->move_column_modal_pk($action) : null;
                        ?>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered border-primary">
                        <thead>
                            <tr>
                                <?php
                                if ($element === 'all' || $element === 'edit') {
                                    echo '<th>Edit</th>';
                                }

                                $lenght_infoschema = count($this->info_schema);
                                for ($i = 0; $i < $lenght_infoschema; $i++) {
                                    if (!in_array($this->info_schema[$i]["COLUMN_NAME"], $exclude)) {
                                        echo "<th scope='col'>{$this->info_schema[$i]["COLUMN_NAME"]}</th>";
                                    }
                                }
                                ?>


                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $number_row =  count($select_all);
                            for ($r = 0; $r < $number_row; $r++) {
                                echo '<tr>';
                                if ($element === 'all' || $element === 'edit') { /* la scelta per inserire il pulsante edit oppure no */
                                    echo '<td>';
                                    $this->update_modal_pk($table, $action, $select_all[$r][0], 'Edit');
                                    echo '</td>';
                                }

                                for ($i = 0; $i < $length_select_all; $i++) {
                                    switch ($this->info_schema[$i]['COLUMN_TYPE']) {
                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'int' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):
                                            $validate = $this->validate_int_pk($select_all[$r][$i]);
                                            echo " <td>$validate</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'var' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):
                                            $validate = $this->validate_var_pk($select_all[$r][$i]);
                                            echo "<td> $validate </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'tex' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):
                                            $validate = $this->validate_var_pk($select_all[$r][$i]);
                                            echo " <td> $validate</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'cha' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):  /*  per la password */
                                            $validate = $this->validate_pass_pk($select_all[$r][$i]);
                                            echo " <td> $validate </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) === 'tinyt' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude): /*  valore email */
                                            if ($this->validate_email_pk($select_all[$r][$i])) {
                                                $validate = $this->validate_email_pk($select_all[$r][$i]);
                                                echo "<td>$validate</td>";
                                            } else {
                                                echo "<td>E-mail non valida</td>";
                                            }
                                            break;

                                        case $this->info_schema[$i]['COLUMN_TYPE'] == 'tinyint(1)' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):  /*  valore booleano */
                                            ($select_all[$r][$i] === 1) ? $value_check = 'X' : $value_check = 'O';
                                            echo " <td> $value_check </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyi' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):
                                            $validate = $this->validate_int_pk($select_all[$r][$i]);
                                            echo " <td>$validate</td>";
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'dat' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude): /* VALORE DATA */
                                            $validate = $this->validate_date_pk($select_all[$r][$i]);
                                            echo " <td> $validate </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'lon' && !in_array($this->info_schema[$i]['COLUMN_NAME'], $exclude):
                                            $validate = $this->validate_var_pk($select_all[$r][$i]);
                                            echo " <td> $validate</td>";
                                            break;
                                    }
                                }
                            ?>
                                </tr>

                            <?php  }
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
    
 /*    creo una form automaticamente solo indicando la tebella e i campi da visualizzare */
    public function form_custom_pk(string $table, array $fields)
    {
        

        $this->form_pk('#', 3);
        foreach ($fields as $field) {

            $tmp = $this->select_one_information_table_pk($table, $field);
            $type = TOOL_PK::set_column_type_pk($tmp[0]['COLUMN_TYPE']);
            switch ($type) {
                case 'var':
                case 'cha':
                case 'text':
                    $this->text_pk($field, $field);
                    break;
                case 'int':
                    $this->int_pk($field, $field);
                    break;
                case 'lon':
                    $this->longText_pk($field, $field);
                    break;
                case 'dat':
                    $this->date_pk($field, $field);
                    break;
            }
        }
        $this->submit_pk('Salva', 'submit_salva');
        $this->end_form_pk();
    }


    public function update_modal_pk(string $table, string $action, int $id, string $label = 'Edit')
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModal<?php echo $id  ?>"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModal<?php echo $id  ?>" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel"><?php echo "ID $id - Table $table"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">

                        <?php
                        $this->update_row_manag_pk($table, $action, $id);

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

    public function insert_modal_pk(string $table, string $action, string $label = 'Add Record',)
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
                        $this->insert_row_manag_pk($table, $action)
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

    public function add_column_modal_pk(string $action, string $label = 'ADD COLUMN')
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
                        $this->add_column_manag_pk($this->table, $action);
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


    public function remove_column_modal_pk(string $action, string $label = 'DELETE COLUMN')
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
                        $this->remove_column_manag_pk($this->table, $action);
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

    public function move_column_modal_pk(string $action, string $label = 'MOVE COLUMN')
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModalmove"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModalmove" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalmove" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaladd"><?php echo "Sposta colonna"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->move_column_manag_pk($this->table, $action);
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




?>