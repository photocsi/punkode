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
class FORM_PK extends INPUT_PK
{


    public  $info_schema = array();  /* informazioni della tabella information_table */
    private  $select_all = array();     /*  tutti i record e tutti i campi della tabella */
    use MANAG_TABLE_PK;
    use SAFE_PK;
    use TOOL_PK;

    function __construct($table)
    {

        parent::__construct($table);
    }


    /*    creo una form automaticamente solo indicando la tebella e i campi da visualizzare */
    public function form_custom_pk(array $fields, string $action = '#', string $method = 'post', int $class = 3, array $style = array('span', 'm', 12))
    {
        $this->pk_form($action, $method, $class, array($style[0], $style[1], $style[2]));
        /* i primi 2 input servono sempre per i render preimpostati */
        $array_type_column[] = 'var';
        foreach ($fields as $field) {
            if (is_string($field)) {
                $tmp = $this->pk_select_one_information_table($field, 'FETCH_ASSOC');
                $type = TOOL_PK::pk_set_column_type($tmp['COLUMN_TYPE'][0]);
                $field_friendly = str_replace('_', ' ', $field);
                switch ($type) {
                    case 'int':
                    case 'bigi':
                        $this->pk_int($field_friendly,  $field);
                        $array_type_column[] = 'int'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'flo':
                        $this->pk_int($field_friendly,  $field, '', 12, 'step="0.01"');
                        $array_type_column[] = 'flo'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'var':
                    case 'cha':
                    case 'text':
                        $this->pk_text($field_friendly, $field);
                        $array_type_column[] = 'var'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'char(255)':
                        $this->pk_password($field_friendly, $field);
                        $array_type_column[] = 'char(255)'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'lon':
                        $this->pk_longtext($field_friendly, $field);
                        $array_type_column[] = 'lon'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'json':
                        $this->pk_longtext($field_friendly, $field);
                        $array_type_column[] = 'json'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'dat':
                        /* case 'times': */
                        $this->pk_date($field_friendly, $field);
                        $array_type_column[] = 'dat'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;
                }
            } else {

                $label = str_replace('_', ' ', $field[0]);
                array_shift($field);
                $this->pk_option($label, $label, $field);
            }
        }
        $string_type_column = implode(',', $array_type_column);
        echo " <input  type='text' name='type_column' value='$string_type_column' hidden> ";
        $this->pk_submit('Salva', 'submit_custom_pk', array($style[1], 'primary', $style[2]));
        $this->pk_end_form();
    }

    public function form_custom_val_pk(string $where, string $value, array $fields, string $action = '#', string $method = 'post', int $class = 4, array $style = array('basic', 'm', 12))
    {

        $this->pk_form($action, $method, $class, array($style[0], $style[1], $style[2]));
        /* il primo input servono sempre per i render preimpostati */
        echo " <input name=$where  type='TEXT' value=$value hidden> ";
        $array_type_column[] = 'var';
        foreach ($fields as $field) {
            if (is_string($field)) {
                $tmp =$this->pk_select_one_information_table($field,'FETCH_ASSOC');
                $type = TOOL_PK::set_column_type_pk($tmp['COLUMN_TYPE'][0]);
                $field_friendly = str_replace('_', ' ', $field);
                switch ($type) {
                    case 'int':
                    case 'bigi':
                        $this->pk_int_val($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'int'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'flo':
                        $this->pk_int_val($where, $value, $field_friendly, $field, '', 12, 'step="0.01"');
                        $array_type_column[] = 'flo'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'var':
                    case 'cha':
                    case 'text':
                        $this->pk_text_val($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'var'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'char(255)':
                        $this->pk_password_val($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'char(255)'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'lon':
                        $this->pk_longtext_val($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'lon';  /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'json':
                        $this->pk_longtext_val($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'json';  /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'dat':
                        /* case 'times': */
                        $this->pk_date_val($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'dat'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;
                }
            } else {

                $label = str_replace('_', ' ', $field[0]);
                array_shift($field);
                $this->pk_option_val($where, $value, $label, $label, $field);
                $array_type_column[] = 'opt';
            }
        }

        $string_type_column = implode(',', $array_type_column);
        echo " <input  type='text' name='type_column' value='$string_type_column' hidden> ";
        $this->pk_submit('Salva', 'submit_custom_val_pk', array($style[1], 'primary'));
        $this->pk_end_form();
    }

    public function pk_form_asinc(string $where, string $value, array $fields, int $class = 4, array $style = array('basic', 'm', 12))
    {

        $this->pk_form('','', $class, array($style[0], $style[1], $style[2]));
        /* il primo input servono sempre per i render preimpostati */
        echo " <input name=$where  type='TEXT' value=$value hidden> ";
        $array_type_column[] = 'var';
        foreach ($fields as $field) {
            if (is_string($field)) {
                $tmp = $this->pk_select_one_information_table($field,'FETCH_ASSOC');
                $type = self::pk_set_column_type($tmp['COLUMN_TYPE'][0]);
                $field_friendly = str_replace('_', ' ', $field);
                switch ($type) {
                    case 'int':
                    case 'bigi':
                        $this->pk_int_asinc($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'int'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'flo':
                        $this->pk_int_asinc($where, $value, $field_friendly, $field,$style[2], 'step="0.01"');
                        $array_type_column[] = 'flo'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'var':
                    case 'cha':
                    case 'text':
                        $this->pk_text_asinc($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'var'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'char(255)':
                        $this->pk_password_asinc($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'char(255)'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'lon':
                        $this->pk_longtext_asinc($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'lon';  /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'json':
                        $this->pk_longtext_asinc($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'json';  /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;

                    case 'dat':
                        /* case 'times': */
                        $this->pk_date_asinc($where, $value, $field_friendly, $field);
                        $array_type_column[] = 'dat'; /* questo serve come valore per il render personalizzato, non conta in effetti il valore in se ma solo il numero dell'array */
                        break;
                }
            } else {

                $label = str_replace('_', ' ', $field[0]);
                array_shift($field);
                $this->pk_option_val($where, $value, $label, $label, $field);
                $array_type_column[] = 'opt';
            }
        }

        $string_type_column = implode(',', $array_type_column);
        echo " <input  type='text' name='type_column' value='$string_type_column' hidden> ";
       
        $this->pk_end_form();
    }
}
