<?php

namespace Punkode;

/**Descrizione della classe 
 * 
 * 
 */
require_once PKDIR . '/includes/db_pdo-class.php';
require_once PKDIR . '/includes/tool-trait-class.php';
require_once PKDIR . '/html/input_html_class.php';
require_once PKDIR . '/includes/upload-trait-class.php';
require_once PKDIR . '/includes/dir-trait-class.php';
class INPUT_PK extends DB_PK
{

  use SAFE_PK;
  use INPUT_HTML_PK;
  use TOOL_PK;
  use UPLOAD_PK;
  use DIR_PK;

  public string $size = 's';
  public array $style = array('span', 'm', 4);

  public function __construct(string $table, ?\PDO $pdo = null)
  {
    if ($pdo) {
      $this->pk_conn = $pdo;
      $this->table = $table;
      return;
    }
    parent::__construct($table);
  }


  public function pk_form($action = "#", string $method = 'post', $class = 4, array $style = array('label', 'm', 4), $param = '', string $enctype = 'multipart/form-data') // application/x-www-form-urlencoded
  {
    $this->style[0] = $style[0];
    $this->size = $style[1];


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

    <form action="<?php echo $action ?>" method="<?php echo $method ?>" enctype="<?php echo $enctype ?>" <?php echo $param ?> class="<?php echo $class ?>">
    <?php
  }

  public function pk_end_form()
  {

    ?>
    </form>
  <?php
  }

  private function pk_size_submit($value)
  {
    switch ($value) {
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
    return $size;
  }

  private function pk_size_input($value)
  {
    $size = match ($value) {
      's' => 'sm',
      'm' => '',
      'l' => 'lg',
      default => '',
    };
    return $size;
  }


  public function pk_submit(string $label, string $name, array $options = array('m', 'primary', 12), string $value = '1')
  {

    (!isset($this->size)) ? $size = $this->pk_size_submit($this->size) : $size = ''; //richiamo il metodo per inserire la giusta classe bootstrap
    (!isset($options[1])) ? $options[1] = 'primary' : null;
    (!isset($options[2])) ? $options[2] = '12' : null;

  ?>
    <div class="col-<?php echo $options[2] ?>">
      <button type="submit" name="<?php echo $name ?>" class="btn btn-<?php echo $options[1] . ' ' . $size ?>" value="<?php echo $value ?>">
        <?php echo $label ?></button>
    </div>
  <?php
  }


  public function pk_button($label, $name = 'submit', $type = 'submit', $id_button = '', $func_js = '')  /*  inserire testo per spiegare la funzione */
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


  public function pk_hidden($name, $value)
  {
  ?>
    <div class="input-group mb-3 <?php echo (isset($size)) ? $size : null ?>">
      <input name="<?php echo $name ?>" value="<?php echo $value ?>" type="text" hidden>
    </div>

    <?php
  }


  public function pk_int(string $label,  string $name = '', int $length = 12, string $param = '')
  {
    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    $template = 'pk_' . $this->style[0];
    self::$template('number', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_int_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    if (is_int($value_input[$name][0])) {
      $value_validate = $this->pk_validate_int($value_input[$name][0]);
    } else {
      $value_validate = $this->pk_validate_float($value_input[$name][0]);
    }


    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    $template = 'pk_' . $this->style[0];
    self::$template('number', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_int_asinc(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');
    ($name === '') ? $name = $label : $name = $name;
    (isset($this->size)) ? $size = $this->pk_size_input($this->size) : null; //richiamo il metodo per inserire la giusta classe bootstrap

    if (is_int($value_input[$name][0])) {
      $value_validate = $this->pk_validate_int($value_input[$name][0]);
    } else {
      $value_validate = $this->pk_validate_float($value_input[$name][0]);
    }

    $template = $this->style[0];
    switch ($template) {
      case 'label':
        self::pk_label_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'placeholder':
        self::pk_placeholder_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;
      case 'float':
        self::pk_float_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'span':
        self::pk_span_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      default:
    ?> <input type="number" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'number,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    <?php break;
    }
    ?>




    <?php

  }

  public function pk_flo_asinc(string $where, string $value, string $label, string $name = '', int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    ($name === '') ? $name = $label : $name = $name;
    (!isset($this->size)) ? $size = $this->pk_size_input($this->size) : $size = ''; //richiamo il metodo per inserire la giusta classe bootstrap

    $value_validate = $this->pk_validate_float($value_input[$name][0]);


    $param = $param . 'step="0.01"';
    $template = $this->style[0];
    switch ($template) {
      case 'label':
        self::pk_label_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'placeholder':
        self::pk_placeholder_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'float':
        self::pk_float_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'span':
        self::pk_span_asinc($this->table, $where, $value, 'number', $length, $size, $label, $name, $value_validate, $param);
        break;

      default:
    ?>
        <input type="number" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'float,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    <?php break;
    }
    ?>


    <?php

  }



  public function pk_text(string $label, string $name, int $length = 12, string $param = '')
  {

    $template = $this->style[0];
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    $template = 'pk_' . $this->style[0];
    self::$template('text', $length, $size, $label, $name, $value_validate, $param);
  }


  public function pk_text_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    if (is_int($value_input[$name][0])) {
      $value_validate = $this->pk_validate_int($value_input[$name][0]);
    } else {
      $value_validate = $this->pk_validate_var($value_input[$name][0]);
    }

    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    $template = 'pk_' . $this->style[0];
    self::$template('text', $length, $size, $label, $name, $value_validate, $param);
  }


  public function pk_text_asinc(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    (isset($this->size)) ? $size = $this->pk_size_input($this->size) : null; //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = $this->pk_validate_var($value_input[$name][0]);

    $template = $this->style[0];
    switch ($template) {
      case 'label':
        self::pk_label_asinc($this->table, $where, $value, 'text', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'placeholde':
        self::pk_placeholder_asinc($this->table, $where, $value, 'text', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'float':
        self::pk_float_asinc($this->table, $where, $value, 'text', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'span':
        self::pk_span_asinc($this->table, $where, $value, 'text', $length, $size, $label, $name, $value_validate, $param);
        break;

      default:
    ?>
        <input type="text" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'text,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    <?php break;
    }
    ?>
  <?php

  }

  public function pk_email(string $label, string $name = '', int $length = 12, string $param = '')
  {

    ($name === '') ? $name = $label : $name = $name;
    $template = $this->style[0];
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    $template = 'pk_' . $this->style[0];
    self::$template('email', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_email_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    if (is_int($value_input[$name][0])) {
      $value_validate = $this->pk_validate_int($value_input[$name][0]);
    } else {
      $value_validate = $this->pk_validate_float($value_input[$name][0]);
    }


    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    $template = 'pk_' . $this->style[0];
    self::$template('email', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_email_asinc($where, $value, $label, string $name = '', int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    (!isset($this->size)) ? $size = $this->pk_size_input($this->size) : null; //richiamo il metodo per inserire la giusta classe bootstrap

    $value_validate = $this->pk_validate_email($value_input[$name][0]);
  ?>
    <input type="email" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'email,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>


    <?php

  }

  public function pk_date(string $label, string $name = '', int $length = 12, string $param = '')
  {

    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    $template = 'pk_' . $this->style[0];
    self::$template('date', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_date_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');


    if ($this->pk_validate_date($value_input[$name][0])) {
      $value_validate = $this->pk_sanitize_date($value_input[$name][0]);
    } else {
      $value_validate = NULL;
    }

    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    $template = 'pk_' . $this->style[0];
    self::$template('date', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_date_asinc(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    (isset($this->size)) ? $size = $this->pk_size_input($this->size) : null; //richiamo il metodo per inserire la giusta classe bootstra

    if ($this->pk_validate_date($value_input[$name][0])) {
      $value_validate = $this->pk_sanitize_date($value_input[$name][0]);
    } else {
      $value_validate = NULL;
    } /* prima di mostrarlo controllo il valore estratto dal db */


    $template = $this->style[0];
    switch ($template) {
      case 'float':
        self::pk_float_asinc($this->table, $where, $value, 'date', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'span':
        self::pk_span_asinc($this->table, $where, $value, 'date', $length, $size, $label, $name, $value_validate, $param);
        break;

      default:
    ?>
        <input type="date" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'date,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    <?php break;
    }
    ?>


    <?php

  }


  public function pk_longtext(string $label, string $name = '', int $length = 12, string $param = '')
  {

    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    $template = 'pk_' . $this->style[0];
    self::$template('textarea', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_longtext_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    if (is_int($value_input[$name][0])) {
      $value_validate = $this->pk_validate_int($value_input[$name][0]);
    } else {
      $value_validate = $this->pk_validate_float($value_input[$name][0]);
    }

    ($name === '') ? $name = $label : $name = $name;
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    $template = 'pk_' . $this->style[0];
    self::$template('textarea', $length, $size, $label, $name, $value_validate, $param);
  }

  public function pk_longtext_asinc(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');
    $value_validate = $this->pk_validate_var($value_input[$name][0]);
    (isset($this->size)) ? $size = $this->pk_size_input($this->size) : null; //richiamo il metodo per inserire la giusta classe bootstrap


    $template = $this->style[0];
    switch ($template) {
      case 'label':
        self::pk_label_asinc($this->table, $where, $value, 'textarea', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'placeholder':
        self::pk_placeholder_asinc($this->table, $where, $value, 'textarea', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'float':
        self::pk_float_asinc($this->table, $where, $value, 'textarea', $length, $size, $label, $name, $value_validate, $param);
        break;

      case 'span':
        self::pk_span_asinc($this->table, $where, $value, 'textarea', $length, $size, $label, $name, $value_validate, $param);
        break;

      default:
    ?>
        <input type="textarea" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'textarea,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    <?php break;
    }
    ?>


  <?php


  }


  public function pk_bool(string $label, string $name, string $checked = '', int $length = 12, string $param = '')
  {

    $template = $this->style[0];
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap


  ?>
    <div class="col-<?php echo $length ?>">
      <div class="input-group mb-3 ">
        <div class="form-check">
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="" hidden checked>
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="checked" id="flexCheckDefault<?php echo $name ?>" <?php echo $checked ?> <?php echo $param ?>>
          <label class="form-check-label" for="flexCheckDefault<?php echo $name ?>">
            <?php echo $label ?>
          </label>
        </div>
      </div>
    </div>



  <?php

  }

  public function pk_bool_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();

    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');


  ?>
    <div class="col-<?php echo $length ?>">
      <div class="input-group mb-3 ">
        <div class="form-check">
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="" hidden checked>
          <input class="form-check-input" name=" <?php echo $name ?>" type="checkbox" value="<?php echo $value_input[0][$name] ?>" id="flexCheck<?php echo $name ?>" <?php echo $param ?>>
          <label class="form-check-label" for="flexCheck<?php echo $name ?>">
            <?php echo $label ?>
          </label>
        </div>
      </div>
    </div>


  <?php

  }


  public function pk_bool_asinc($where, $value, $label, $name, int $length = 12, string $param = '')
  {
    $value_input = array();

    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    ($value_input[$name][0] === 1) ? $checked = 'checked' : $checked = '';
    ($value_input[$name][0] === 1) ? $value_dest = '0' : $value_dest = '1';





  ?>
    <div class="col-<?php echo $length ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <div class="form-check <?php echo $size ?>">
          <input class="form-check-input" name="<?php echo $name ?>" type="checkbox" value="<?php echo $value_dest ?>" id="<?php echo 'check,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?> <?php echo $checked ?> <?php echo $param ?>>
          <label class="form-check-label">
            <?php echo $label ?>
          </label>
        </div>
      </div>
    </div>


  <?php

  }

  public function pk_option(string $label, string $name, array $selection, int $length = 12, string $param = '')
  {



    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    $template = 'pk_option_' . $this->style[0];
    self::$template($length, $size, $label, $name, $value_validate, $param);

    $count = count($selection);
    for ($i = 0; $i < $count; $i++) {
      echo " <option value='$selection[$i]'>$selection[$i]</option>";
    }
  ?>

    </select>
    </div>
    </div>

  <?php
  }

  public function pk_option_val(string $where, string $value, string $label, string $name, array $selection, int $length = 12, string $param = '')
  {
    $name = str_replace(' ', '_', $name);
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');
    $value_validate = $this->pk_validate_var($value_input[$name][0]);


    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $template = 'pk_option_' . $this->style[0];
    self::$template($length, $size, $label, $name, $value_validate, $param);

    $count = count($selection);
    for ($i = 0; $i < $count; $i++) {
      echo " <option value='$selection[$i]'>$selection[$i]</option>";
    }
  ?>
    </select>
    </div>
    </div>


  <?php
  }

  public function session_on_of($where_field, $where_value, $name)
  {
    if ($where_field === 'SESSION' && $where_value === 'SESSION') {
      if (isset($_SESSION[$name])) {

        $result[$name][0] = $_SESSION[$name];
      } else {
        $result[$name][0] = '';
      }
    } else {
      $result = $this->pk_select(array($name), $where_field, $where_value, 'FETCH_ASSOC');
    }

    return $result;
  }


  public function pk_option_asinc(string $where_field, string $where_value, string $label, string $name, array $selection, int $length = 12, string $param = '')
  {
    $name = str_replace(' ', '_', $name);

    $value_input = $this->session_on_of($where_field, $where_value, $name);

    $value_input[$name][0] = self::pk_sanitize_var(str_replace('_', ' ', $value_input[$name][0]));

    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $count_selection = count($selection);
  ?>
    <div class="col-<?php echo $length ?>">
      <div class="input-group mb-3 <?php echo $size ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <select <?php echo $param ?> name="<?php echo $name ?>" class="form-select" aria-label="Default select example" id="<?php echo 'option,' . $this->table . ',' . $where_field . ',' . $where_value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
          <option selected><?php echo $value_input[$name][0] ?></option>
          <?php for ($i = 0; $i < $count_selection; $i++) {
            echo " <option value='$selection[$i]'>$selection[$i]</option>";
          }
          ?>

        </select>
      </div>
    </div>
  <?php
  }

  public function pk_password($label, $name = '', int $length = 12, string $param = '')
  {
    ($name === '') ? $name = $label : $name = $name;
    $template = $this->style[0];
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = null;

    switch ($template) {
      case 'float':
        self::pk_password_float('password', $length, $size, $label, $name, $value_validate, $param);
        break;

      default:
        self::pk_password_span('password', $length, $size, $label, $name, $value_validate, $param);
        break;
    }
  }

  public function pk_password_val(string $where, string $value, string $label, string $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    if (is_int($value_input[$name][0])) {
      $value_validate = $this->pk_validate_int($value_input[0][$name]);
    } else {
      $value_validate = $this->pk_validate_float($value_input[0][$name]);
    }


    ($name === '') ? $name = $label : $name = $name;
    $template = $this->style[0];
    $size = $this->pk_size_input($this->size); //richiamo il metodo per inserire la giusta classe bootstrap

    switch ($template) {
      case 'float':
        self::pk_float('password', $length, $size, $label, $name, '*****', $param);
        break;

      default:
        self::pk_span('password', $length, $size, $label, $name, '*****', $param);
        break;
    }
  }

  public function pk_password_asinc($where, $value, $label, $name, int $length = 12, string $param = '')
  {
    $value_input = array();
    $value_input = $this->pk_select(array($name), $where, $value, 'FETCH_ASSOC');

    (!isset($this->size)) ? $size = $this->pk_size_input($this->size) : null; //richiamo il metodo per inserire la giusta classe bootstrap
    $value_validate = $this->pk_validate_pass($value_input[0][$name]); /* prima di mostrarlo controllo il valore estratto dal db */

  ?>
    <input type="password" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" id="<?php echo 'password,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    <label onclick="mostraPass(<?php echo '\'' . 'password,' . $this->table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN . '\'' ?>)"> <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
        <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8M1.173 8a13 13 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5s3.879 1.168 5.168 2.457A13 13 0 0 1 14.828 8q-.086.13-.195.288c-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5s-3.879-1.168-5.168-2.457A13 13 0 0 1 1.172 8z" />
        <path d="M8 5.5a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5M4.5 8a3.5 3.5 0 1 1 7 0 3.5 3.5 0 0 1-7 0" />
      </svg></label>

  <?php

  }

  public function pk_upload(string $label, string $name = 'files', string $accept = 'audio/*,video/*,image/*', string $param = '', array $options = array('m', 12))
  {


  ?>
    <div class="col-<?php echo $options[1] ?>">
      <div class="input-group mb-3 <?php (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php (isset($size)) ?  $size : null ?>"><?php echo $label ?></span>
        <input type="file" name="<?php echo $name ?>" accept="<?php echo $accept ?>" class="form-control" <?php echo $param ?>>
      </div>
    </div>

<?php
  }
}

?>