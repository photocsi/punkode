<?php

namespace Punkode;

require_once 'tool-trait-class.php';
class TABLE_PK extends INPUT_PK
{
    private  $info_schema = array();  /* informazioni della tabella information_table */
    private  $select_all = array();     /*  tutti i record e tutti i campi della tabella */
    use MANAG_TABLE_PK;
    use SAFE_PK;

    function __construct($table='', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
    {

        parent::__construct($table, $db, $host, $user, $password);
       
        
    }

    public function view_create_table_pk(string $action = '#'){
        $this->form_pk($action,4);
        $this->text_pk('Name Table', 'table_name_new_table','', 'REQUIRED', array('s', 12));
        $this->text_pk('Name id', 'id_name_new_table','', 'REQUIRED', array('s', 12));
        $this->submit_pk('Create', 'submit_create_new_table', array('s', 'primary', 12));
        $this->end_form_pk();

    }

    public function view_table_pk($table)
    {

        $this->info_schema = $this->select_information_table_pk($table, '*'); /* prendo le informazioni della tabella information_table */
        $this->select_all = $this->select_all_pk($table, '*');  /* selezione di tutti i record e tutti i campi della tabella */
        if (isset($this->select_all[0])) {
            $lengt_select_all = count($this->select_all[0]) / 2;
        }
    ?>

        <div class="card  m-3">
            <h5 class="card-header text-center "><?php  echo 'Tabella "' . $this->info_schema[0]["TABLE_NAME"] . '"';  ?></h5>
            <div class="card-header text-center">
                <div class="row">
                    <div class="col-6" style="text-align:start ; padding-left: 1rem">
                        <?php echo $this->insert_modal_pk($table,'Add Record'); ?>
                    </div>
                    <div class="col-6" style="text-align:end ; padding-right: 1rem">
                        <?php echo $this->add_column_modal_pk(); ?>
                        <?php echo $this->remove_column_modal_pk(); ?>
                        <?php echo $this->move_column_modal_pk(); ?>
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
                                echo $this->update_modal_pk($table,$this->select_all[$r][0], 'Edit');
                                echo '</td>';
                                for ($i = 0; $i < $lengt_select_all; $i++) {
                                    switch ($this->select_all[$r][$i]) {
                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'int':
                                            $validate = $this->validate_int_pk($this->select_all[$r][$i]);
                                            echo " <td>$validate</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'var':
                                            $validate = $this->validate_var_pk($this->select_all[$r][$i]);
                                            echo " <td> $validate</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'tex':
                                            $validate = $this->validate_var_pk($this->select_all[$r][$i]);
                                            echo " <td> $validate</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) === 'cha':  /*  per la password */
                                            $validate = $this->validate_pass_pk($this->select_all[$r][$i]);
                                            echo " <td> $validate </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) === 'tinyt':
                                            $validate = $this->validate_email_pk($this->select_all[$r][$i]);
                                                echo "<td>E-mail non valida</td>";
                                            
                                            break;

                                        case $this->info_schema[$i]['COLUMN_TYPE'] == 'tinyint(1)':  /*  valore booleano */
                                            ($this->select_all[$r][$i] === 1) ? $value_check = 'X' : $value_check = 'O';
                                            echo " <td> $value_check </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyi':
                                            $validate = $this->validate_int_pk($this->select_all[$r][$i]);
                                            echo " <td>$validate</td>";
                                            echo " <td> {$this->select_all[$r][$i]}</td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'dat':
                                            $validate = $this->validate_date_pk($this->select_all[$r][$i]);
                                            echo " <td> $validate </td>";
                                            break;

                                        case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'lon':
                                            $validate = $this->validate_var_pk($this->select_all[$r][$i]);
                                            echo " <td> $validate</td>";
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

    public function update_modal_pk($table,$id, string $label = 'Edit')
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
                       $this->update_row_tool_pk($table,$id);

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

    public function insert_modal_pk(string $table, string $label = 'Add Record',)
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
                       $this->insert_row_tool_pk($table)
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
                        $this->add_column_tool_pk($this->table);
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


    public function remove_column_modal_pk(string $label = 'DELETE COLUMN',)
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
                        $this->remove_column_tool_pk($this->table);
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

    public function move_column_modal_pk(string $label = 'MOVE COLUMN',)
    {

    ?>
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#exampleModalmove"><?php echo $label; ?> </button>

        <!-- Modal -->
        <div class="modal fade" id="exampleModalmove" data-bs-keyboard="false" tabindex="-1" aria-labelledby="exampleModalmove" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModaladd"><?php echo "Aggiungi una nuova colonna"; ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <?php
                        $this->move_column_tool_pk($this->table);
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