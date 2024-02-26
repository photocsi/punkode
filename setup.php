
<?php

class SETUP_PK 
{
    public $db= "punkdb"; 
    public $host= "localhost";
    public $user= "root";
    public $password= "";

    public function __construct($db='optional',$host='optional',$user='optional',$password='optional')
    {
      ($db != 'optional') ? $this->db=$db : null;
      ($host != 'optional') ? $this->host=$host : null;
      ($user != 'optional') ? $this->user=$user : null;
      ($password != 'optional') ? $this->password=$password : null;


    }
    
}

define('FASTDIR', __DIR__ );
define('FASTPAGE', __FILE__);



/* 
^(?=.*\d)(?=.*[A-Z])(?=.*[a-z])(?=.*[^\w\d\s:])([^\s]){8,16}$
la password deve contenere 1 numero (0-9)
la password deve contenere 1 lettera maiuscola
la password deve contenere 1 lettera minuscola
la password deve contenere 1 numero non alfanumerico
la password è composta da 8-16 caratteri senza spazi */


require_once FASTDIR.'/includes/db_pdo-class.php';
require_once FASTDIR.'/includes/input-class.php';
require_once FASTDIR.'/includes/table-class.php';
require_once FASTDIR.'/includes/tool-class.php';
require_once FASTDIR.'/includes/manag_table-class.php';
require_once FASTDIR.'/includes/trait-class.php';

?>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>


