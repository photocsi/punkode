<?php

namespace Punkode;

/*le funzioni di input tipo $this->option() non sono collegate direttamente alla classe, ma inserite in classi che estendono input e funzionano lo stesso*/

/**DESCRIZIONE CLASSE
 * E' una classe trait che viene utilizzata da table-class
 * contiene le funzioni che creano il form dei vari pulsanti della tabella, update, insert, delet riga e add, delete, move colonna nel db
 * le funzioni creano i form che inviano i dati alla classe render (che a sua volta prende i dati post e li invia alla classe db)
 * manage_table class utilizza la classe input per creare i vari campi
 * serve solo per i form preimpostati
 */

trait MANAG_TABLE_PK
{


    public function pk_update_row_manag(int $id, string $action = '#', string $style = '')
    {  /* crea tutti gli input presi dalla tabella del db */
        $this->pk_form($action, 'post', '', array($style, '', ''));
        $column_name_replace = str_replace('_', ' ', $this->info_schema['COLUMN_NAME'][0]);
        echo " <input name='pk_name_update_table'  type='TEXT' value=$this->table hidden> ";
        echo " <input name='{$this->info_schema['COLUMN_NAME'][0]}'  type='text' value=$id hidden> ";

        $array_type_column = array();
        $array_type_column[] = 'int';
        $count_info_schema = count($this->info_schema['COLUMN_NAME']);
        for ($i = 1; $i <  $count_info_schema; $i++) {
            $column_name_replace = trim(str_replace('_', ' ', $this->info_schema['COLUMN_NAME'][$i]));
            /* tolgo il trattino basso e sostituisco con lo spazio */
            switch ($this->info_schema['COLUMN_TYPE'][$i]) {
                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 4) == 'bigi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */
                    );
                    $array_type_column[] = 'bigi';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'int':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'int';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_text_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i] /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'var';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 9) == 'char(255)':   /*controllo se la stringa column_type comincia con char(255) e quindi e una password*/
                    $this->pk_password_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i] /* name - nome colonna con trattini */


                    );
                    $array_type_column[] = 'char(255)';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'cha':   /*controllo se la stringa column_type comincia con cha e quindi e char*/
                    $this->pk_text_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'cha';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_text_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'tex';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'tinyt':   /*controllo se la stringa column_type comincia con tinyt e quindi e un email*/
                    $this->pk_email_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'tinyt';
                    break;

                case $this->info_schema['COLUMN_TYPE'][$i] == 'tinyint(1)':   /*  valore booleano */
                    $this->pk_bool_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i] /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'tinyint(1)';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'tinyi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'tinyi';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'flo':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */
                        12,
                        'step="0.01"'

                    );
                    $array_type_column[] = 'flo';
                    break;

                    /*  case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'times':   //controllo se la stringa column_type comincia con int e quindi e un numero
                    $this->date_val(
                        $this->info_schema[0]['TABLE_NAME'],  //nome tabella 
                        $this->info_schema['COLUMN_NAME'][0], // nome della prima colonna che contiene l'id 
                        $id,                                  // id del record 
                        $column_name_replace,                 // label - nome colonna senza trattino 
                        $this->info_schema['COLUMN_NAME'][$i], // name - nome colonna con trattini 
                        '',
                        array('s')
                    );
                    $array_type_column[] = 'times';
                    break; */

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'dat':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_date_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'dat';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'lon':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_longText_val(
                        $this->info_schema['COLUMN_NAME'][0], /* nome della prima colonna che contiene l'id */
                        $id,                                  /* id del record */
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'lon';
                    break;
            }
        }
        $string_type_column = implode(',', $array_type_column);
        echo " <input  type='text' name='type_column' value='$string_type_column' hidden> ";

        $this->pk_submit('Save', 'pk_submit_edit_record', array('s', 'primary'));
        $this->pk_end_form();
    }


    public function pk_insert_row_manag($action = '#', string $style = '')
    {
        $this->pk_form($action, 'post', '', array($style, '', ''));
        echo " <input name='pk_name_insert_table'  type='TEXT' value=$this->table hidden> ";
        $column_name_replace = str_replace('_', ' ', $this->info_schema['COLUMN_NAME'][0]);
        $array_type_column = array();
        $length=count($this->info_schema['COLUMN_NAME']);
        for ($i = 1; $i < $length; $i++) {
            $column_name_replace = trim(str_replace('_', ' ', $this->info_schema['COLUMN_NAME'][$i]));
            /* tolgo il trattino basso e sostituisco con lo spazio */
            switch ($this->info_schema['COLUMN_TYPE'][$i]) {
                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 4) == 'bigi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'bigi';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'int':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'int';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'var':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_text(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'var';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 9) == 'char(255)':   /*controllo se la stringa column_type comincia con char(255) e quindi e una password*/
                    $this->pk_password(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'char(255)';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'cha':   /*controllo se la stringa column_type comincia con cha e quindi e char a lunghezza fissa*/
                    $this->pk_text(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'cha';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'tex':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_text(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'tex';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'tinyt':   /*controllo se la stringa column_type comincia con tinyt e quindi e un email*/
                    $this->pk_email(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'tinyt';
                    break;

                case $this->info_schema['COLUMN_TYPE'][$i] == 'tinyint(1)':   /*  valore booleano */
                    $this->pk_bool(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */
                        ''
                    );
                    $array_type_column[] = 'tinyint(1)';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'tinyi':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */



                    );
                    $array_type_column[] = 'tinyi';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'flo':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_int(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */
                        '' .
                            12,
                        'step="0.01"'
                    );
                    $array_type_column[] = 'flo';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 5) == 'times':   //controllo se la stringa column_type comincia con int e quindi e un numero
                    $this->pk_date(
                        $column_name_replace,                 // label - nome colonna senza trattino 
                        $this->info_schema['COLUMN_NAME'][$i], // name - nome colonna con trattini 

                    );
                    $array_type_column[] = 'times';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'dat':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_date(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'dat';
                    break;

                case substr($this->info_schema['COLUMN_TYPE'][$i], 0, 3) == 'lon':   /*controllo se la stringa column_type comincia con int e quindi e un numero*/
                    $this->pk_longText(
                        $column_name_replace,                 /* label - nome colonna senza trattino */
                        $this->info_schema['COLUMN_NAME'][$i], /* name - nome colonna con trattini */

                    );
                    $array_type_column[] = 'lon';
                    break;
            }
        }
        $string_type_column = implode(',', $array_type_column);
?> <input type='text' name='type_column' value='<?php echo isset($string_type_column) ? $string_type_column : null; ?>' hidden>
<?php
        $this->pk_submit('Save', 'pk_submit_insert_record', array('s', 'primary'));
        $this->pk_end_form();
    }


    public function pk_delete_row_manag(int $id, string $action = '#', string $style = '')
    {  /* crea tutti gli input presi dalla tabella del db */
        $this->pk_form($action, 'post', '', array($style, '', ''));
        echo " <input name='pk_name_delete_table'  type='TEXT' value=$this->table hidden> ";
        echo " <input name='{$this->info_schema['COLUMN_NAME'][0]}'  type='number' value=$id hidden> ";


        $this->pk_submit('Delete', 'pk_submit_delete_record', array('s', 'danger'));
        $this->pk_end_form();
    }


    public function pk_add_column_manag(string $action = '#', string $style = '')
    {

        $this->pk_form($action, 'post', '', array($style, '', ''));
        $this->pk_hidden('pk_name_add_table', $this->table);
        $this->pk_text(
            'Nome',
            'pk_name_new_column',
            12,
            'pattern="^[a-z _ A-Z 0-9]+$" REQUIRED PLACEHOLDER="Solo lettere e numeri, vietate le parole chiave di mysql"'

        );

        $this->pk_option(
            'Tipo',
            'type_new_column',
            array('', 'ID (bigint)', 'TEXT (varchar)', 'TEXT (char dimensioni fisse)', 'LONGTEXT', 'NUMBER (int)', 'NUMBER (float)', 'EMAIL (tinytext)', 'DATE', 'TIMESTAMP', 'PASSWORD', 'BOOLEAN'),
            12,
            'REQUIRED',

        );

        echo 'Expert';
        $this->pk_int(
            'Lunghezza',
            'length_new_column',

        );

        $this->pk_option(
            'Predefinito',
            'predefinito_new_column',
            array('', 'NULL', 'CURRENT_TIMESTAMP'),

        );

        $this->pk_option(
            'Codifica',
            'codifica_new_column',
            array('', 'utf8mb4_unicode_ci', 'utf8mb4_general_ci'),

        );

        $this->pk_option(
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

        );

        $this->pk_option(
            'NULL',
            'null_new_column',
            array('', 'NULL', 'NOT NULL'),

        );

        $this->pk_submit('invia', 'pk_submit_add_new_column', array('s', 'primary', 12));
        $this->pk_end_form();
    }


    public function pk_remove_column_manag(string $action = '#', string $style = '')
    {
        $array_information = array();
        $information_table = $this->pk_select_information_table('*','FETCH_ASSOC');
        $length=count($information_table['COLUMN_NAME']);
        for ($i = 0; $i < $length; $i++) {
            $array_information[] = $information_table['COLUMN_NAME'][$i];
        }

        $this->pk_form($action, 'post', '', array($style, '', ''));
        echo " <input name='pk_name_remove_table'  type='TEXT' value=$this->table hidden> ";
        $this->pk_option('Column', 'pk_name_delete_column', $array_information);
        $this->pk_submit('Delet Column', 'pk_submit_delete_column', array('s', 'danger', 12));
        $this->pk_end_form();
    }

    public function pk_move_column_manag(string $action = '', string $style = '')
    {
        $array_information = array();
        $information_table = $this->pk_select_information_table('*','FETCH_ASSOC');
        $length=count($information_table['COLUMN_NAME']);
        for ($i = 0; $i < $length; $i++) {
            $array_information[] = $information_table['COLUMN_NAME'][$i];
        }

        $this->pk_form($action, 'post', '', array($style, '', ''));
        echo " <input name='pk_name_move_table'  type='TEXT' value=$this->table hidden> ";
        $this->pk_option('Column', 'pk_name_move_column', $array_information);
        $this->pk_option('After Column', 'pk_name_after_column', $array_information);
        $this->pk_submit('Move Column', 'pk_submit_move_column', array('s', 'danger', 12));
        $this->pk_end_form();
    }
}
