
<?php
require_once 'db_pdo-class.php';

class TOOL_PUNK extends DB_CSI
{

    public function update_record($table){

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        
         if (isset($_POST['edit_record'])) {
               $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
               for ($i=1; $i < count($array_key)-1 ; $i++) { 
                $this->update($table,$array_key[$i],$array_value[$i], $array_key[0], $array_value[0]);
               } 
                
            } 
        }
    }

 public function insert_record($table)
    {
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['insert_record'])) {
                $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
                array_pop($array_key);
                array_pop($array_value);

                $this->insert($table, $array_key, $array_value);

              
            }
        }
    }

    public function delete_record($table)
    {

        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            if (isset($_POST['delete_record'])) {
                 $array_key = array_keys($_POST);
                foreach ($_POST as  $value) {
                    $array_value[] = $value;
                }
             $this->delete($table, $array_key[0],$array_value[0]); 
          
            }
        }

    } 

    static function ciclo_post($post)
    {
        $array_key = array_keys($post);
        var_dump($array_key);
    }
}
