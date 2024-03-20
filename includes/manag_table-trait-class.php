<?php

namespace Punkode;

/*le funzioni di input tipo $this->option_pk() non sono collegate direttaemnte alla classe, ma inserite in classi che estendono input allora funzionano lo stesso*/

trait MANAG_TABLE_PK
{
    public function template_classic_pk(string $table, string $action='#', string $element = 'all', array $exclude = array())
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
                        <?php ($element === 'all' || $element === 'edit') ? $this->insert_modal_pk($table,$action , 'Add Record') : null ?>
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


    public function update_row_tool_pk(string $table, string $action='#', int $id)
    {  /* crea tutti gli input presi dalla tabella del db */
        $this->form_pk($action, 4);
        $column_name_replace = str_replace('_', ' ', $this->info_schema[0]['COLUMN_NAME']);
        echo " <input name='name_update_table_pk'  type='TEXT' value=$table hidden> ";
        echo " <input name='{$this->info_schema[0]['COLUMN_NAME']}'  type='number' value=$id hidden> ";

        $array_type_column = array();
        $array_type_column[] = 'int';
        $count_info_schema = count($this->info_schema);
        for ($i = 1; $i <  $count_info_schema; $i++) {
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

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'cha':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->password_val_pk(
                        $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                        $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'cha';
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

        $this->submit_pk('Delete', 'submit_delete_record_pk', array('s', 'danger'));

        $this->submit_pk('Save', 'submit_edit_record_pk', array('s', 'primary'));
        $this->end_form_pk();
    }


    public function insert_row_tool_pk($table,$action='#')
    {


        $this->form_pk($action, 4);
        echo " <input name='name_insert_table_pk'  type='text' value=$table hidden> "; /* campo nascosto che serve anche come sicurezza se non e compilato l'insert non va */
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
                    $array_type_column[] = 'int';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->text_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        '',
                        array('s', 12)
                    );
                    $array_type_column[] = 'var';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'cha':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->password_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'cha';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->text_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'tex';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyt':   /*controllo se la stringa column_type comincia con tinyt e quindi e un email*/
                    $this->email_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'tinyt';
                    break;

                case $this->info_schema[$i]['COLUMN_TYPE'] == 'tinyint(1)':   /*  valore booleano */
                    $this->bool_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        ''
                    );
                    $array_type_column[] = 'tinyint(1)';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 5) == 'tinyi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->int_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'tinyi';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'dat':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->date_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'dat';
                    break;

                case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'lon':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->longText_pk(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'lon';
                    break;
            }
        }
        $string_type_column = implode(',', $array_type_column);
        echo " <input  type='text' name='type_column' value='$string_type_column' hidden> ";
        $this->submit_pk('Save', 'submit_insert_record_pk', array('s', 'primary'));
        $this->end_form_pk();
    }

    public function add_column_tool_pk(string $table, string $action = '#')
    {

        $this->form_pk($action, 4);
        $this->hidden_pk('name_table_pk', $table);
        $this->text_pk(
            'Nome',
            'name_new_column',
            '',
            'pattern="^[a-z _ A-Z]+$" REQUIRED',
            array('s', 12)
        );

        $this->option_pk(
            'Tipo',
            'type_new_column',
            array('TEXT (varchar)', 'LONGTEXT', 'NUMBER (int)', 'EMAIL (tinytext)', 'DATE', 'PASSWORD', 'BOOLEAN'),
            'REQUIRED',
            array('s', 12)
        );

        $this->int_pk('Lunghezza', 'length_new_column', '', array('s', 12));

        $this->option_pk(
            'Predefinito',
            'predefinito_new_column',
            array('', 'NULL', 'CURRENT_TIMESTAMP'),
            '',
            array('s', 12)
        );

        $this->option_pk(
            'Codifica',
            'codifica_new_column',
            array('', 'utf8mb4_unicode_ci', 'utf8mb4_general_ci'),
            '',
            array('s', 12)
        );

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
            array('s', 12)
        );

        $this->option_pk('NULL', 'null_new_column', array('NULL', 'NOT NULL'), '', array('s', 12));

        $this->submit_pk('invia', 'submit_add_new_column', array('s', 'primary', 12));
        $this->end_form_pk();
    }


    public function remove_column_tool_pk(string $table, string $action = '#')
    {
        $array_information = array();
        $information_table = $this->select_information_table_pk($table, '*');
        for ($i = 0; $i < count($information_table); $i++) {
            $array_information[] = $information_table[$i]['COLUMN_NAME'];
        }
        array_shift($array_information);

        $this->form_pk($action, 4);
        echo " <input name='name_table_remove'  type='TEXT' value=$table hidden> ";
        $this->option_pk('Column', 'name_delete_column', $array_information, '', array('s', 12));
        $this->submit_pk('Delet Column', 'submit_delete_column', array('s', 'danger', 12));
        $this->end_form_pk();
    }

    public function move_column_tool_pk(string $table, string $action = '')
    {
        $array_information = array();
        $information_table = $this->select_information_table_pk($table, '*');
        for ($i = 0; $i < count($information_table); $i++) {
            $array_information[] = $information_table[$i]['COLUMN_NAME'];
        }
        array_shift($array_information);

        $this->form_pk($action, 4);
        echo " <input name='name_table_move_pk'  type='TEXT' value=$table hidden> ";
        $this->option_pk('Column', 'name_move_column_pk', $array_information, '', array('s', 12));
        $this->option_pk('After Column', 'name_after_column_pk', $array_information, '', array('s', 12));
        $this->submit_pk('Move Column', 'submit_move_column_pk', array('s', 'danger', 12));
        $this->end_form_pk();
    }

   /*  public function orderby_tool_pk(string $table)
    {
        $array_information = array();
        $information_table = $this->select_information_table_pk($table, '*');
        for ($i = 0; $i < count($information_table); $i++) {
            $array_information[] = $information_table[$i]['COLUMN_NAME'];
        }
        array_shift($array_information);

        $this->form_pk('#', 5);
        $this->option_pk('Ordina per', 'name_orderby_pk', $array_information, '', array('s', 12));
        $this->submit_pk('vai', 'submit_orderby_pk', array('s', 'danger', 12));
        $this->end_form_pk();
    }
 */
   
}
