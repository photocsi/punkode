<?php
ob_start();
use Punkode\DB_PK;

require_once __DIR__ . '/../../../setup.php';


$table = DB_PK::pk_sanitize_var($_POST['table']);
$where_name_column = DB_PK::pk_sanitize_var($_POST['where']);
($_POST['value_where'] === 'SESSION') ? $value_id = 'SESSION' : $value_id = DB_PK::pk_sanitize_var($_POST['value_where']);
$name_column = DB_PK::pk_sanitize_var($_POST['name']);
$type = DB_PK::pk_sanitize_var($_POST['type']);


switch ($type) {
    case 'text':
        $value = DB_PK::pk_sanitize_var((str_replace(' ','_',$_POST['value'])));
        break;
    case 'number':
        if(is_int($value)){
            $value = DB_PK::pk_sanitize_int($_POST['value']);
        }else{
            $value = DB_PK::pk_sanitize_float($_POST['value']);
        }
        break;
    case 'option':
        $value = DB_PK::pk_sanitize_var((str_replace(' ','_',$_POST['value'])));
        break;
    case 'textarea':
        $value = DB_PK::pk_sanitize_var($_POST['value']);
        break;
    case 'date':
        $value = DB_PK::pk_sanitize_date($_POST['value']);
        break;
    case 'check':
        $value = DB_PK::pk_sanitize_var((str_replace(' ','_',$_POST['value'])));
        break;
    case 'email':
        $value = DB_PK::pk_sanitize_email($_POST['value']);
        break;
    case 'password':
        $value = DB_PK::pk_sanitize_pass($_POST['value']);
        break;
    case 'json':
        $value = $_POST['value'];
        break;

    default:
        # code...
        break;
}


$db = new DB_PK($table);
if ($where_name_column === 'SESSION' && $value_id === 'SESSION') {
    session_start();
    $_SESSION[$name_column] = $value;
}else {
    $db->pk_update($name_column, $value, $where_name_column, $value_id);
}

return $value;
