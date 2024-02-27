<?php

class MANAG_TABLE_PK extends INPUT_PK
{


  function __construct(string $table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
  {

    parent::__construct($table, $db, $host, $user, $password);
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
   
      if (isset($_POST['table_name']) && !empty($_POST['table_name']) && !empty($_POST['id_name'])) {
        $this->create_table_pk($_POST['table_name'], $_POST['id_name']);
      }else if (isset($_POST['name_new_column']) && !empty($_POST['name_new_column']) && !empty($_POST['type_new_column'])) {
          $name_new_column=$_POST['name_new_column'];
          $type_new_column=$_POST['type_new_column'];
          $length_new_column=$_POST['length_new_column'];
          $predefinito_new_column=$_POST['predefinito_new_column'];
          $attr_new_column=$_POST['attr_new_column'];
          $null_new_column=$_POST['null_new_column'];
          $codifica_new_column=$_POST['codifica_new_column'];
     $this->create_column_table_pk($table,$name_new_column,$type_new_column,$length_new_column,$predefinito_new_column, $codifica_new_column,$attr_new_column,$null_new_column);
  
      }else if(isset($_POST['name_delete_column']) && !empty($_POST['name_delete_column']) && isset($_POST['submit_delete_column'])){
        $name_column=$_POST['name_delete_column'];
        $this->delete_column_table_pk($table,$name_column);
      }
    }
  }


  public function start_table_pk(string $action = '#')
  {
    $this->form_pk($action);
    $this->text_pk('Nome Tabella', 'table_name');
    $this->text_pk('Nome ID', 'id_name');
    $this->submit_pk('invia', 'submit_start_table');
  }


  public function delete_column_pk(string $table,string $action = '#'){
    $array_information= array();
   $information_table=$this->select_information_table_pk($table,'*');
   for ($i=0; $i <count($information_table) ; $i++) { 
   $array_information[] = $information_table[$i]['COLUMN_NAME'];
 
   }
   array_shift($array_information);

    $this->form_pk($action);
    $this->option_pk('Column', 'name_delete_column',$array_information,'',array('s',1));
    $this->submit_pk('invia', 'submit_delete_column');
    $this->end_form_pk();
  }

  public function add_column_pk(string $table,string $action = '#')
  {
   
    $this->form_pk($action);
    $this->hidden_pk('name_table', $table);
    $this->text_pk('Nome', 'name_new_column','REQUIRED',array('s',1));
    $this->option_pk('Tipo', 'type_new_column',array('VARCHAR','INT','DATE','LONGTEXT'),'REQUIRED',array('s',1));
    $this->int_pk('Lunghezza', 'length_new_column','',array('s',1));
    $this->option_pk('Predefinito', 'predefinito_new_column', array('','NULL', 'CURRENT_TIMESTAMP'), '',array('s',1));
    $this->option_pk('Codifica', 'codifica_new_column', array('','utf8mb4_unicode_ci', 'utf8mb4_general_ci'), '',array('s',1));
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
      array('s',1)
    );
    $this->option_pk('NULL','null_new_column',array('NULL','NOT NULL'),'',array('s',1));
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
    $this->submit_pk('invia', 'submit_add_new_column');
    $this->end_form_pk();
  }
}
