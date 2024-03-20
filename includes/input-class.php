<?php
namespace Punkode;
/**Descrizione della classe 
 * 
 * 
*/
require_once PKDIR.'/includes/db_pdo-class.php';
class INPUT_PK extends DB_PK
{

  private $style = "";
  private $div_main = "row mb-3";
  private $label_class = "col-sm-5 col-form-label col-form-label-sm";
  private $div_input = "col-md-4";
  private $input_class = "form-control form-control-sm";

  use SAFE_PK;

  function __construct(string $table = 'optional', $db = 'optional', $host = 'optional', $user = 'optional', $password = 'optional')
  {

    parent::__construct($table, $db, $host, $user, $password);
  }


  public function form_pk($action = "#", string $class = '', $method = 'post', $id = '')
  {
   
  /*   scelgo lo stile di impaginazione bootstrap per il mio form */
    switch ($class) {
      case '1':
        $class = 'row g-3';
        break;
      case '2':
        $class = 'row mb-3';
        break;
      case '3':
        $class = 'row row-cols-lg-auto g-3 align-items-center';
        break;
      case '4':
        $class = 'row gx-3 gy-2 align-items-center';
        break;
      case '5':
        $class = 'row row-cols-lg-auto g-3 align-items-center';
        break;

      default:
        $class = 'row';
        break;
    }
?>

    <form action="<?php echo $action ?>" method="<?php echo $method ?>" id="<?php echo $id ?>" class="<?php echo $class ?>">
    <?php
  }

  public function end_form_pk()
  {

    ?>
    </form>
  <?php
  }

  public function submit_pk( string $label, string $name, array $options = array('m', 'primary', 12), string $value='1')
  {

    if (isset($options[0])) {
      switch ($options[0]) {
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
    }

    (!isset($options[1])) ? $options[1]='primary' : null;
    (!isset($options[2])) ? $options[2]='12' : null;

  ?>
    <div class="col-<?php echo $options[2] ?>">
      <button type="submit" name="<?php echo $name ?>" class="btn btn-<?php echo $options[1] . ' ' . $size ?>" value="<?php echo $value ?>">
        <?php echo $label ?></button>
    </div>
  <?php
  }


  public function button_func_pk($label, $name = 'submit', $type = 'submit', $id_button = '', $func_js = '')  /*  inserire testo per spiegare la funzione */
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

  public function hidden_pk($name, $value)
  {

  ?>


    <div class="input-group mb-3 <?php echo (isset($size)) ? $size : null ?>">
      <input name="<?php echo $name ?>" value="<?php echo $value ?>" type="text" id="<?php echo $name ?>" hidden>
    </div>

  <?php


  }

  public function int_pk(string $label, string $name = '', string $param = '', array $options = array('m', 12))
  {
    if ($name === '') {
      $name = $label;
    }
    if (isset($options[0])) {
      switch ($options[0]) {
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
    }

    (!isset($options[1])) ? $options[1]='12' : null;
    

  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input name="<?php echo $name ?>" value="" class="form-control <?php echo (isset($size)) ?  $size : null ?>" type="number" pattern="^[0-9]+$"  <?php echo $param ?>>
      </div>
    </div>

  <?php

  }

  public function int_val_pk(string $table, string $where, string $value, string $label, string $name = '', string $param = '', array $options = array('m', 12))
  {
    $value_input = array();
    $value_input = $this->select_pk($table, array($name), $where, $value);

    if ($name === '') {
      $name = $label;
    }

    if (isset($options[0])) {
      switch ($options[0]) {
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
    }

   
    (!isset($options[1])) ? $options[1]='12' : null;

    $value_validate= $this->validate_int_pk($value_input[0][$name]);

  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php echo (isset($size)) ?  $size : null ?>"><?php echo $label ?></span>
        <input name="<?php echo $name ?>" value="<?php echo $value_validate ?>" class="form-control " type="number" pattern="^[0-9]+$" <?php echo $param ?>>
      </div>
    </div>

  <?php

  }



  public function text_pk($label, $name, string $value='', $param = '', $options = array('m', 12))
  {
    if (isset($options[0])) {
      switch ($options[0]) {
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
    }
    (!isset($options[1])) ? $options[1]='12' : null;
    

  ?>

    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo (isset($size)) ? $size : null ?>">
        <span class="input-group-text <?php echo (isset($size)) ?  $size : null ?>"><?php echo $label ?></span>
        <input  type="text" name="<?php echo $name ?>" value="<?php echo (isset($value)) ?  $value : null ?>" 
        class="form-control <?php echo (isset($size)) ?  $size : null ?> "  <?php echo $param ?> >
      </div>
    </div>
  <?php

  }

  public function text_val_pk($table, $where, $value, $label, $name, $param = '', $options = array('m', 12))
  {
    $value_input = array();
    $value_input = $this->select_pk($table, array($name), $where, $value);

    if (isset($options[0])) {
      switch ($options[0]) {
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
    }
    (!isset($options[1])) ? $options[1]='12' : null;
   
    $value_validate= $this->validate_var_pk($value_input[0][$name]);

  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php (isset($size)) ?  $size : null ?>"><?php echo $label ?></span>
        <input type="text" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" class="form-control"   <?php echo $param ?>>
      </div>
    </div>

  <?php

  }

  public function email_pk($label, $name, $param, $options = array('m', 12))
  {

    if (isset($options[0])) {
      switch ($options[0]) {   /* definisco la dimensione dell'input */
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
    }
    (!isset($options[1])) ? $options[1]='12' : null;
  

  ?>

    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input type="email" name="<?php echo $name ?>" value="" class="form-control <?php echo $size ?>"  <?php echo $param ?>>
      </div>
    </div>

  <?php

  }

  public function email_val_pk($table, $where, $value, $label, $name, $param, $options = array('m', 12))
  {
    $value_input = array();
    $value_input = $this->select_pk($table, array($name), $where, $value);

    switch ($options[0]) {
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
    (!isset($options[1])) ? $options[1]='12' : null;
  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input type="email" name="<?php echo $name ?>" value="<?php echo $value_input[0][$name] ?>" class="form-control"  <?php echo $param ?>>
      </div>
    </div>

  <?php

  }

  public function date_pk($label, $name, $param = '', $options = array('m', 12))
  {

    switch ($options[0]) {   /* definisco la dimensione dell'input */
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
    (!isset($options[1])) ? $options[1]='12' : null;

  ?>

    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input type="date" name="<?php echo $name ?>" value="" class="form-control <?php echo $size ?>"  <?php echo $param ?>>
      </div>
    </div>

  <?php

  }

  public function date_val_pk($table, $where, $value, $label, $name, $param = '', $options = array('m', 12))
  {
    $value_input = array();

    $value_input = $this->select_pk($table, array($name), $where, $value);
    switch ($options[0]) {
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

    (!isset($options[1])) ? $options[1]='12' : null;

    $value_validate= $this->validate_date_pk($value_input[0][$name]); /* prima di mostrarlo controllo il valore estratto dal db */
  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input type="date" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" class="form-control"  <?php echo $param ?>>
      </div>
    </div>

  <?php

  }

  public function longText_pk(string $label, string $name, string $param = '', array $options = array('m', 12))
  {



    switch ($options[0]) {   /* definisco la dimensione dell'input */
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
    (!isset($options[1])) ? $options[1]='12' : null;

  ?>

    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>

        <textarea name="<?php echo $name ?>" value="" class="form-control <?php echo $size ?>" <?php echo $param ?>></textarea>
      </div>
    </div>
  <?php

  }

  public function longText_val_pk($table, $where, $value, $label, $name, $param = '', $options = array('m', 12))
  {
    $value_input = array();

    $value_input = $this->select_pk($table, array($name), $where, $value);
    switch ($options[0]) {
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
    (!isset($options[1])) ? $options[1]='12' : null;

  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <textarea name="<?php echo $name ?>" class="form-control"  <?php echo $param ?>><?php echo $value_input[0][$name] ?></textarea>
      </div>
    </div>

  <?php

  }


  public function bool_pk(string $label, string $name, string $checked = '', string $param = '', $options = array('m', 12))
  {

    (!isset($options[1])) ? $options[1]='12' : null;

  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 ">
        <div class="form-check">
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="2" hidden checked>
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="1" id="flexCheckDefault" <?php echo $checked ?> <?php echo $param ?>>
          <label class="form-check-label" for="flexCheckDefault">
            <?php echo $label ?>
          </label>
        </div>
      </div>
    </div>



  <?php

  }

  public function bool_val_pk($table, $where, $value, $label, $name, $param = '', $options = array('m', 12))
  {
    $value_input = array();
    (!isset($options[1])) ? $options[1]='12' : null;

    $value_input = $this->select_pk($table, array($name), $where, $value);
    ($value_input[0][$name] == 1) ? $checked = 'checked' : $checked = '';


  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 ">
        <div class="form-check">
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="2" hidden checked>
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="1" <?php echo $checked ?> <?php echo $param ?>>
          <label class="form-check-label" for="flexCheck <?php echo $name ?>">
            <?php echo $label ?>
          </label>
        </div>
      </div>
    </div>


  <?php

  }

  public function option_pk(string $label, string $name, array $selection, string $param = '', $options = array('m', 12))
  {

    $default_options = array('m', 12, 'Open this select menu');
    if (!isset($options[0])) {
      $options[0] = $default_options[0];
    } else if (!isset($options[1])) {
      $options[1] = $default_options[1];
    } else if (!isset($options[2])) {
      $options[2] = $default_options[2];
    }

    switch ($options[0]) {   /* definisco la dimensione dell'input */
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
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <select <?php echo $param ?> name="<?php echo $name ?>" class="form-select" aria-label="Default select example">
          <option selected><?php echo $selection[0] ?></option>
          <?php for ($i = 1; $i < count($selection); $i++) {
            echo " <option value='$selection[$i]'>$selection[$i]</option>";
          }
          ?>

        </select>
      </div>
    </div>
<?php
  }

  public function password_pk($label, $name, $param = '', $options = array('m', 12))
  {
    if (isset($options[0])) {
      switch ($options[0]) {
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
    }
    (!isset($options[1])) ? $options[1]='12' : null;

  ?>

    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php echo (isset($size)) ? $size : null ?>">
        <span class="input-group-text <?php echo (isset($size)) ?  $size : null ?>"><?php echo $label ?></span>
        <input type="password" <?php echo $param ?> name="<?php echo $name ?>" value="" class="form-control <?php echo (isset($size)) ?  $size : null ?> "  >
      </div>
    </div>
  <?php

  }

  public function password_val_pk($table, $where, $value, $label, $name, $param = '', $options = array('m', 12))
  {
    $value_input = array();
    $value_input = $this->select_pk($table, array($name), $where, $value);

    if (isset($options[0])) {
      switch ($options[0]) {
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
    }

    (!isset($options[1])) ? $options[1]='12' : null;
    $value_validate= $this->validate_pass_pk($value_input[0][$name]); /* prima di mostrarlo controllo il valore estratto dal db */

  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php (isset($size)) ?  $size : null ?>"><?php echo $label ?></span>
        <input type="password" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" class="form-control"  <?php echo $param ?>>
      </div>
    </div>

  <?php

  }


}

?>