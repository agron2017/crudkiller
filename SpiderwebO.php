<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 3/30/2016
 * Time: 8:10 AM
 */
//Updated on April 1, 2016..
echo "Beginning..";


        //configs..

        $user    = 'root';
        $pass    = '';
        $host    = 'localhost';
        $db      = 'tracker';
        $charset = 'utf8';

        $pdo = new PDO("mysql:host=$host;dbname=$db;charset=$charset", $user, $pass);

        if($pdo){
            echo "PDO Connection successfull...";
        }





       $dir = __DIR__;
       $keysign1 = '';
       $keysign2 = '_id';//if needed, but optional. set to empty string - double quotes - ''  - , if not used
       $dbname = $db;
       //end config



       $dbvarname = 'Tables_in_'.$dbname;
       $sql = "SHOW FULL TABLES WHERE Table_Type = 'BASE TABLE' ";
       $query = $pdo->prepare($sql);
       $query->execute();
       $tables = $query->fetchAll();
       $dbtables = [];
       $default_cols_to_display = ' <?php '.PHP_EOL.' class Displaycols {'.PHP_EOL.'  '.PHP_EOL;

       foreach($tables as $k=>$v){
           $dbtables[] = $v[$dbvarname];
       }
       $ownedby = [];
       $tablefields = [];



       foreach($dbtables as $t){
           $sql = "SHOW FULL COLUMNS FROM ".$t;
           $query = $pdo->prepare($sql);
           $query->execute();
           $results = $query->fetchAll();

           $fieldsarray = [];
           foreach($results as $k=>$v){

               $field = $v['Field'];
               $fieldtype = (object)['type' => $v['Type'], 'label' => $v['Comment'] ];

               $fieldsarray[$field] = $fieldtype;

               $tmp = [];

               //sets the owned-by array here by checking the is had the keysigns,
               //indicating that it is a foreign key to another table
               //remember, strpos returns index of first found string, which is 0! evaluates to FALSE here
               $testfield = strpos($field, $keysign2);
               if( $testfield !== FALSE ){
                   $stringsompare = strtolower( str_replace([$keysign1,$keysign2],'',$field) );
                   $ownedby[$stringsompare][] = $t;
               }
           }
           $tablefields[$t] = (object) $fieldsarray;

           //a config array for default display columns for the table view
           $default_cols_to_display .= '       public $'.$t.' = [\'id\', \'name\' ]; '.PHP_EOL.PHP_EOL;

       }
        $default_cols_to_display .= '
        '.PHP_EOL.'
        } ';







       foreach($ownedby as $k=>$v) {
           $text = '<?php '.PHP_EOL.'namespace Bribrink\Crudkiller\crudkiller;'.PHP_EOL;
           $tmp1 = ' '.PHP_EOL;

           //create the classes
           $text .= 'class '.ucfirst($k).'killer {  '.PHP_EOL.PHP_EOL;

           echo $k;

           print_r($tablefields[$k] );

           foreach($v as $p):
           $text .= '
           public $'.$p.' = \''.$p.'\';  '.PHP_EOL;
           $tmp1 .= '
           $this->'.$p.' = self::get( \''.$p.'\', $id );   '.PHP_EOL;
               endforeach;

           $text .= '
           public $myself;'.PHP_EOL;

           //create the class


           $text .= '


           function __construct($id=null)
            {
            '.$tmp1.'

            $this->myself = self::getme(  $id  );'.PHP_EOL.'
           // $this->all = self::all();'.PHP_EOL.'

           } '.PHP_EOL;





       $text .= '
            private static function get($tbl,$id=null){
                $sql = " SELECT * FROM $tbl ";
                if(!is_null($id)){
                    $sql .= "  WHERE '.$keysign1.$k.$keysign2.' = ".$id;
                }
                return self::connect($sql);
            }'.PHP_EOL.'

            private static function getme($id=null){
                $sql = " SELECT * FROM '.$k.' WHERE id = ".$id;
                $results = self::connect($sql);
                return $results;
            }'.PHP_EOL.'

             private static function all($id=null){
                $sql = " SELECT * FROM '.$k.' ";
                return self::connect($sql);
            }'.PHP_EOL.'

            private static function connect($sql){
                $pdo = DatabaseFactory::getFactory()->getConnection();
                $query = $pdo->prepare($sql);
                $query->execute();
                return $query->fetchAll();
            }'.PHP_EOL.'

       }    ';

           $d = $dir.'/crudkiller/';
           if (!is_dir($d)) {
               mkdir($d, 0777, true); // true for recursive create
           }

           $file = fopen($d.'/'.ucfirst($k).'killer.php','w');
           fwrite($file,$text);
           fclose($file);



       }


        //table class logic
        foreach($tablefields as $f=>$v ){
            $classname = str_replace( '_','', ucfirst($f) ).'table';
            $text = '';
            $text_construct_vars = '';
            $text .= '<?php '.PHP_EOL;
            $text .= '
                   class '.$classname.' { '.PHP_EOL;

            foreach($v as $n=>$t){

                $text .= '
                        public $'.$n.';
                        ';

                $text_construct_vars .= '
                        $this->'.$n.' = (object)[\'name\'=>\''.$n.'\',\'type\'=>\''.$t->type.'\', \'label\' => \''.$t->label.'\' ];
                        ';
            }
            $text .= PHP_EOL;

            $text .= '
                   public function __construct() { '.PHP_EOL;
                $text .= $text_construct_vars;
            $text .= ' } '.PHP_EOL;

            $text .= '             } '.PHP_EOL.PHP_EOL;



            $d = $dir.'/tables/';
            if (!is_dir($d)) {
                mkdir($d, 0777, true); // true for recursive create
            }
            $file = fopen($d.'/'.$classname.'.php','w');
            fwrite($file,$text);
            fclose($file);

        }

        //default cols array
        $d = $dir.'/';
        $file = fopen($d.'Displaycols.php','w');
        fwrite($file,$default_cols_to_display);
        fclose($file);




print_r($tablefields);



