<?php

namespace Punkode;
require_once PKDIR.'/includes/input-class.php';
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
                        $this->update_row_tool_pk($table, $action, $id);

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
                        $this->insert_row_tool_pk($table, $action)
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
                        $this->add_column_tool_pk($this->table, $action);
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


    public function remove_column_modal_pk(string $action,string $label = 'DELETE COLUMN')
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
                        $this->remove_column_tool_pk($this->table, $action);
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

    public function move_column_modal_pk(string $action , string $label = 'MOVE COLUMN')
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
                        $this->move_column_tool_pk($this->table, $action);
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