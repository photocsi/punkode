<?php

namespace Punkode;

/**Descrizione della classe 
 * 
 * 
 */

trait INPUT_HTML_PK
{

  static function  pk_label($type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="input-group">
      <div class="mb-3 <?php echo (isset($size)) ?  'input-group-'.$size : null ?> ">
      <span class="input-group-text" id="basic-addon1"><?php echo $label ?></span>
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" id="LabelInput" >
        </div>
      </div>
    </div>

  <?php
  }

  static function  pk_placeholder($type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="mb-3 <?php echo (isset($size)) ?  'input-group-'.$size : null ?> ">
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" id="floatingInput" placeholder="<?php echo $label ?>" >
      </div>
    </div>

  <?php
  }

  static function  pk_span($type,$length, $size, $label, $name, $value_validate, $param)
  {
    (isset($value_validate)) ? $value="value='$value_validate'" : $value='';
?>
    <div class="col-sm-<?php echo $length ?>">
      <div class="input-group mb-3 input-group-<?php echo (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input type="<?php echo $type ?>" name="<?php echo $name ?>" <?php echo $value ?> class="form-control <?php echo (isset($size)) ?  $size : null ?>"  <?php echo $param ?> >
      </div>
    </div>

  <?php
  }

  static function  pk_float($type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="form-floating mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" id="floatingInput" placeholder="<?php echo $label ?>" >
        <label for="floatingInput"><?php echo $label ?></label>
      </div>
    </div>

  <?php
  }

  static function  pk_password_label($type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
      <label for="LabelInput"><?php echo $label ?></label>
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" id="LabelInput" autocomplete>
        
      </div>
    </div>

  <?php
  }

  static function  pk_submit($type,$length, $size, $label, $name, $value_validate, $param)
  {
    (isset($value_validate)) ? $value="value='$value_validate'" : $value='';
?> 
    <div class="col-sm-<?php echo $length ?>">
    <span class="input-group-text mb-3 input-group-text-<?php echo $length ?>" id="basic-addon1<?php echo $name ?>" style="padding: 2px !important;">
    <button type="submit" class="btn btn-info" name="salva_move_photo"><?php echo $label ?></button>
    <input type="<?php echo $type ?>" name="<?php echo $name ?>" <?php echo $value ?> class="form-control" <?php echo $param ?> aria-label="<?php echo $name ?>" aria-describedby="basic-addon1<?php echo $name ?>">
    </div>

  <?php
  }

  static function  pk_password_submit($type,$length, $size, $label, $name, $value_validate, $param)
  {
    (isset($value_validate)) ? $value="value='$value_validate'" : $value='';
?> 
    <div class="col-sm-<?php echo $length ?>">
    <span class="input-group-text" id="basic-addon1<?php echo $name ?>" style="padding: 2px !important;">
    <button type="submit" class="btn btn-info" name="salva_move_photo"><?php echo $label ?></button>
    <input type="<?php echo $type ?>" name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" <?php echo $param ?> aria-label="<?php echo $name ?>" aria-describedby="basic-addon1<?php echo $name ?>" autocomplete>
    </div>

  <?php
  }

  static function  pk_password_placeholder($type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" id="floatingInput" placeholder="<?php echo $label ?>" autocomplete >
      </div>
    </div>

  <?php
  }

  static function  pk_password_span($type,$length, $size, $label, $name, $value_validate, $param)
  {
?>
    <div class="col-sm-<?php echo $length ?>">
      <div class="input-group mb-3 <?php echo (isset($size)) ?  $size : null ?>">
        <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
        <input type="<?php echo $type ?>" name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control <?php echo (isset($size)) ?  $size : null ?>"  <?php echo $param ?> autocomplete>
      </div>
    </div>

  <?php
  }

  static function  pk_password_float($type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="form-floating mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" id="floatingInput" placeholder="<?php echo $label ?>" autocomplete>
        <label for="floatingInput"><?php echo $label ?></label>
      </div>
    </div>

  <?php
  }

  static function pk_option_label($length, $size, $label, $name, $param,$value){
    ?>
    <div class="col-sm-<?php echo $length ?>">
    <div class="input-group mb-3">
    <label for="LabelOption"><?php echo $label ?></label>
    <select name="<?php echo $name ?>" class="form-select form-select-<?php echo $size ?> mb-3" id="LabelOption" aria-label="Small select example" <?php echo $param ?>>
   

    
            <?php
  
  }
  
  static function pk_option_placeholder($length, $size, $label, $name,  $value ,$param){
    ?>
  <div class="col-sm-<?php echo $length ?>">
    <div class="input-group">
    <select name="<?php echo $name ?>" class="form-select form-select-<?php echo $size ?> mb-3" aria-label="Small select example" <?php echo $param ?>>
    <option selected><?php echo $label ?></option>
            <?php
  
  }

static function pk_option_span($length, $size, $label, $name,$value,$param){
  ?>
  <div class="col-sm-<?php echo $length ?>">
  <div class="input-group input-group-<?php echo $size ?> mb-3">
  <span class="input-group-text " for="inputGroupSelect01<?php echo $label ?>"><?php echo $label ?></span>
        <select <?php echo $param ?> name="<?php echo $name ?>" id="inputGroupSelect01<?php echo $label ?>" class="form-select" aria-label="Default select example" <?php echo $param ?>>
          <option selected><?php echo $value ?></option>
          
          <?php

}

static function pk_option_float($length, $size, $label, $name, $param,$value){
  ?>
<div class="col-sm-<?php echo $length ?>">
<div class="input-group mb-3">
<label for="floatingSelect<?php echo $label ?>"><?php echo $label ?></label>
  <select name="<?php echo $name ?>" class="form-select" id="floatingSelect<?php echo $label ?>" aria-label="Floating label select example" <?php echo $param ?>>
  <option selected><?php echo $value ?></option>
  
          <?php

}

static function  pk_option_submit($length, $size, $label, $name, $value_validate, $param)
  {
    (isset($value_validate)) ? $value="value='$value_validate'" : $value='';
?> 
    <div class="col-sm-<?php echo $length ?>">
    <div class="input-group input-group-<?php echo $size ?> mb-3">
    <span class="input-group-text " id="basic-addon1<?php echo $name ?>" style="padding: 2px !important;">
    <button type="submit" class="btn btn-info" name="salva_move_photo"><?php echo $label ?></button>
    <select name="<?php echo $name ?>" class="form-select" id="SubmitSelect<?php echo $label ?>" aria-label="<?php echo $name ?>" aria-describedby="basic-addon1<?php echo $name ?>" <?php echo $param ?>>
  <option selected><?php echo $value ?></option>

  <?php
  }

static function  pk_span_asinc($table,$where,$value,$type,$length, $size, $label, $name, $value_validate, $param)
{
?>
  <div class="col-sm-<?php echo $length ?>">
    <div class="input-group mb-3 <?php echo (isset($size)) ?  $size : null ?>">
      <span class="input-group-text <?php echo $size ?>"><?php echo $label ?></span>
      <input type="<?php echo $type ?>" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" class="form-control"   
       id="<?php echo $type. ',' . $table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
    </div>
  </div>

<?php
}

static function  pk_float_asinc($table,$where,$value,$type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="form-floating mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
      <input type="<?php echo $type ?>" name="<?php echo $name ?>" value="<?php echo $value_validate ?>" class="form-control"   
      id="<?php echo $type. ',' . $table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" <?php echo $param ?>>
        <label for="floatingInput"><?php echo $label ?></label>
      </div>
    </div>

  <?php
  }

  static function  pk_label_asinc($table,$where,$value,$type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
      <label for="LabelInput"><?php echo $label ?></label>
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control"
        id="<?php echo $type. ',' . $table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" >
        
      </div>
    </div>

  <?php
  }

  static function  pk_placeholder_asinc($table,$where,$value,$type,$length, $size, $label, $name, $value_validate, $param)
  {
  ?>
    <div class="col-sm-<?php echo (isset($length)) ?  $length : null ?>">
      <div class="mb-3 <?php echo (isset($size)) ?  $size : null ?> ">
        <input type="<?php echo $type ?>" <?php echo $param ?> name="<?php echo $name ?>" value="<?php echo (isset($value_validate)) ?  $value_validate : null ?>" class="form-control" 
        id="<?php echo $type. ',' . $table . ',' . $where . ',' . $value . ',' . $name . ',' . PKURLMAIN ?>" placeholder="<?php echo $label ?>" >
      </div>
    </div>

  <?php
  }


  
  public function pk_table_html($element, $action,$style)
  {
  ?>
    <div class="card  m-3">
      <a href=<?php __FILE__ ?>>
        <h5 class="card-header text-center "><?php echo 'Tabella "' . $this->table . '"';  ?></h5>
      </a>
      <div class="card-header text-center">
        <div class="row">
          <div class="col-4" style="text-align:start ; padding-left: 1rem">
            <?php ($element === 'all' || $element === 'edit') ? $this->pk_insert_modal($action,'NEW RECORD',$style) : null ?>
          </div>
          <div class="col-5" style="text-align:start ; padding-left: 1rem">

          </div>
          <div class="col-1" style="text-align:start ; padding-right: 1rem">
            <?php
            ($element === 'all') ? $this->pk_add_column_modal($action,'ADD COLUMN',$style) : null;
            ?>
          </div>
          <div class="col-1" style="text-align:start ; padding-right: 1rem">
            <?php

            ($element === 'all') ? $this->pk_remove_column_modal($action,'DELETE COLUMN',$style) : null;

            ?>
          </div>
          <div class="col-1" style="text-align:start ; padding-right: 1rem">
            <?php

            ($element === 'all') ? $this->pk_move_column_modal($action,'MOVE COLUMN',$style) : null;
            ?>
          </div>
        </div>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-bordered border-primary">

        <?php
      }
    }
