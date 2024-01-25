<?php


require_once  '../setup.php';

$table=$_POST['table'];
$name=$_POST['name'];
$value=$_POST['value'];
$where=$_POST['where'];
$value_where=$_POST['value_where'];
$up= new DB_CSI($table);
$up->update($table,$name,$value,$where,$value_where);





?>