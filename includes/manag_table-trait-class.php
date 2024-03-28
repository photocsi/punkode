<?php

namespace Punkode;

/*le funzioni di input tipo $this->option_pk() non sono collegate direttaemnte alla classe, ma inserite in classi che estendono input allora funzionano lo stesso*/

trait MANAG_TABLE_PK
{
   

    public function update_row_manag_pk(string $table, string $action='#', int $id)
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
                        $table,  /* nome tabella */
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

                    case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 9) == 'char(255)':   /*controllo se la stringa column_type comincia con char(255) e quindi e una password*/
                        $this->password_val_pk(
                            $this->info_schema[0]['TABLE_NAME'],  /* nome tabella */
                            $this->info_schema[0]['COLUMN_NAME'], /* nome della prima colonna che contiene l'id */
                            $id,                                  /* id del record */
                            $column_name_replace,                 /* label - nome colonna senza trattino */
                            $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                            '',
                            array('s')
                        );
                        $array_type_column[] = 'char(255)';
                        break;

                    case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'cha':   /*controllo se la stringa column_type comincia con cha e quindi e char*/
                        $this->text_val_pk(
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

        $this->submit_pk('Save', 'submit_edit_record_pk', array('s', 'primary'));
        $this->end_form_pk();
    }


    public function insert_row_manag_pk($table,$action='#')
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
                        array('s', 12)
                    );
                    $array_type_column[] = 'var';
                    break;

                    case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 9) == 'char(255)':   /*controllo se la stringa column_type comincia con char(255) e quindi e una password*/
                        $this->password_pk(
                            $column_name_replace,                 /* label - nome colonna senza trattino */
                            $this->info_schema[$i]['COLUMN_NAME'], /* name - nome colonna con trattini */
                            '',
                            array('s')
                        );
                        $array_type_column[] = 'char(255)';
                        break;

                    case substr($this->info_schema[$i]['COLUMN_TYPE'], 0, 3) == 'cha':   /*controllo se la stringa column_type comincia con cha e quindi e char a lunghezza fissa*/
                        $this->text_pk(
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

    public function delete_row_manag_pk(string $table, string $action='#', int $id)
    {  /* crea tutti gli input presi dalla tabella del db */
        $this->form_pk($action, 4);
        echo " <input name='name_delete_table_pk'  type='TEXT' value=$table hidden> ";
        echo " <input name='{$this->info_schema[0]['COLUMN_NAME']}'  type='number' value=$id hidden> ";

      
        $this->submit_pk('Delete', 'submit_delete_record_pk', array('s', 'danger'));
        $this->end_form_pk();
    }


    public function add_column_manag_pk(string $table, string $action = '#')
    {

        $this->form_pk($action, 4);
        $this->hidden_pk('name_table_pk', $table);
        $this->text_pk(
            'Nome',
            'name_new_column',
            'pattern="^[a-z _ A-Z]+$" REQUIRED',
            array('s', 12)
        );

        $this->option_pk(
            'Tipo',
            'type_new_column',
            array('TEXT (varchar)','TEXT (char dimensioni fisse)', 'LONGTEXT', 'NUMBER (int)', 'EMAIL (tinytext)', 'DATE', 'PASSWORD', 'BOOLEAN'),
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


    public function remove_column_manag_pk(string $table, string $action = '#')
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

    public function move_column_manag_pk(string $table, string $action = '')
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

   /*  public function orderby_manag_pk(string $table)
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
