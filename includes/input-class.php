<?php


require_once FASTDIR . '/setup.php';
require_once FASTDIR . '/includes/db_pdo-class.php';



/**Descrizione della classe */
class INPUT_CSI extends DB_CSI
{

  private $style = "";
  private $div_main = "row mb-3";
  private $label_class = "col-sm-5 col-form-label col-form-label-sm";
  private $div_input = "col-md-4";
  private $input_class = "form-control form-control-sm";


  function __construct(string $table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
  {


    parent::__construct($table, $db, $host, $user, $password);
  }

  public function form($action = "#", $method = 'post', $id = 'form')
  {

    $action = $action;
    $method = $method;
    $id = $id;
?>
    <form action="<?php echo $action ?>" method="<?php echo $method ?>" id="<?php echo $id ?>" class="row g-3">
    <?php
  }

  public function end_form()
  {

    ?>
    </form>
  <?php
  }

  public function button_submit($label, $name_submit, $array_options_style = array('color' => 'primary', 'size' => 'm'))
  {
    $submit = $name_submit;
    switch ($array_options_style[1]) {
      case 's':
        $size = 'btn-sm';
        break;
      case 'm':
        $size = '';
        break;
      case 'l':
        $size = 'btn-lg';
        break;
    }
  ?>

    <button type="submit" name="<?php echo $submit ?>" class="btn btn-<?php echo $array_options_style[0] . ' ' . $array_options_style[1] ?>"><?php echo $label ?></button>

  <?php
  }


  public function button_func($label, $name = 'submit', $type = 'submit', $id_button = '', $func_js = '')  /*  inserire testo per spiegare la funzione */
  {

    $label = $label;
    $name = $name;
    $type = $type;
    (isset($func_js[0])) ? $fun = $func_js[0] : $fun = 'onclick';
    (isset($func_js[1])) ? $fun1 = $func_js[1] : $fun1 = 'default';
    (isset($func_js[2])) ? $param = $func_js[2] : $param = 'default';


  ?>

    <button type="<?php echo $type ?>" name="<?php echo $name ?>" class="btn btn-primary" id="<?php echo $id_button ?>" <?php echo $func_js ?>><?php echo $label ?></button>

  <?php
  }


  public function input_number($label, $name, $array_options_style = array('size' => 'm'), $func_js = '')
  {

    $function_js = $func_js;

    switch ($array_options_style[0]) {
      case 's':
        $size = 'input-group-sm';
        break;
      case 'm':
        $size = '';
        break;
      case 'l':
        $size = 'input-group-lg';
        break;
    }

  ?>
    <div class="input-group mb-3 <?php echo $size ?>">
      <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
      <input name="<?php echo $name ?>" value="" class="form-control <?php echo $size ?>" type="number" id="<?php echo $name ?>" <?php echo $func_js ?>>
    </div>

  <?php

  }

  public function input_number_value($table, $where, $value, $label, $name, $array_options_style = array('size' => 'm'), $func_js = '')
  {
    $value_input = array();

    $value_input = $this->select($table, array($name), $where, $value);
    switch ($array_options_style[0]) {
      case 's':
        $size = 'input-group-sm';
        break;
      case 'm':
        $size = '';
        break;
      case 'l':
        $size = 'input-group-lg';
        break;
    }

  ?>

    <div class="input-group mb-3 <?php echo $size ?>">
      <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
      <input name="<?php echo $name ?>" value="<?php echo $value_input[0][$name] ?>" class="form-control " type="number" id="<?php echo $name . $value ?>" <?php echo $func_js ?>>
      <!-- <button class="btn btn-outline-danger" type="button" id=""  onclick="update(<?php /* echo '\''.$table.'\','.'\''.$name.'\','.'\''.$where.'\','.'\''.$value.'\'' */ ?>)">Modifica</button> -->
    </div>

  <?php

  }



  public function input_text($label, $name, $array_options_style = array('size' => 'm'), $function_js = '')
  {

    $function_js = $function_js;

    switch ($array_options_style[0]) {
      case 's':
        $size = 'input-group-sm';
        break;
      case 'm':
        $size = '';
        break;
      case 'l':
        $size = 'input-group-lg';
        break;
    }


  ?>


    <div class="input-group mb-3 <?php echo $size ?>">
      <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
      <input name="<?php echo $name ?>" value="" class="form-control <?php echo $size ?>" type="text" id="<?php echo $name ?>" <?php echo $function_js ?>>
    </div>

  <?php

  }

  public function input_text_value($table, $where, $value, $label, $name, $array_options_style = array('lenght_input' => 8, 'size' => 'm'), $func_js = '')
  {
    $value_input = array();

    $value_input = $this->select($table, array($name), $where, $value);
    switch ($array_options_style[0]) {
      case 's':
        $size = 'input-group-sm';
        break;
      case 'm':
        $size = '';
        break;
      case 'l':
        $size = 'input-group-lg';
        break;
    }


  ?>
    <div class="input-group mb-3 <?php echo $size ?>">
      <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
      <input name="<?php echo $name ?>" value="<?php echo $value_input[0][$name] ?>" class="form-control" type="text" id="<?php echo $name . $value ?>" <?php echo $func_js ?>>

    </div>

<?php

  }
}
