<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 4/4/2016
 * Time: 11:00 AM
 */
class Getter
{

    public static function get($table,$id=null)
    {

        $pdo = DatabaseFactory::getFactory()->getConnection();
        //set up our strings to manipulate
        $sql = null;
        $sqljoin = null;
        $sqlwhere = null;
        $tclass = ucfirst($table).'table';
        $t = new $tclass();
        $foreigntable = [];

        //gets the properties from the generated table class
        $select = '';
        foreach( get_object_vars ( $t ) as $key=>$val ){
            if( strpos($key,'_id')!==false ){
                $k = rtrim($key,'_id');
                $foreigntable[]=$k;
            }
            $select .= $table.'.'.$key.' ,';
        }
       $select = rtrim($select,',');

        if(count($foreigntable)>0){

            foreach($foreigntable as $k=>$v){
                $sqljoin .=
                    " LEFT JOIN ".$v."
                ON ".$v.".id = ".$table.'.'.$v."_id  ";

                //add a name to select from the table
                $select .= ",  $v.name as ".$v."_name";
            }

            $sqljoin = rtrim($sqljoin,',');
        }

        //now add in the $select, if it has changed from about
        $sql = "SELECT $select FROM ".$table;

        if (!is_null($id)){
            $sqlwhere .=  " WHERE $table.id = ".$id;
        }
                /*
                if (isset($id)){
                    $sqlwhere .=  " WHERE $this->table.id = ".$id;
                } */

        $sqlmaster = $sql.$sqljoin.$sqlwhere;

        echo $sqlmaster; return;

        $query = $pdo->prepare($sqlmaster);
        $query->execute();

        $values = $query->fetchAll();
        return $values;
    }




    public static function run($query){
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $query = $pdo->prepare($query);
        $query->execute();
        $values = $query->fetchAll();
        return $values;
    }




    public static function set($table)
    {
        $id = $_POST['id'];
        // get it out of the foreach loop, so it wont try to set id = ''
        unset($_POST['id']);
        unset($_POST['table']);
        if ($table == 'users') {
            return;
        }
        $time = new DateTime();
        $updated = $time->format('Y-m-d H:i:s');
        $editor = Session::get('user_id');



        if( $id === '' ){//ie empty string

            $sql = " ";
            $sql .= " INSERT INTO ".$table." ( ";

            $sql_values = ' VALUES (';

            foreach($_POST as $key=>$val){
                if(!is_numeric($val)){
                    $val = '"'.$val.'"';
                }
                $sql .= $key.' ,';
                $sql_values .= $val.' ,';
            }
            $sql .= 'updated , editor ';
            $sql_values .= ' \''.$updated.'\' ,'.$editor. " )";


            $sql .= " )";
            $sql .= $sql_values;

            echo $sql;



        } else {

            $sql = ' UPDATE '.$table.' SET ';

            foreach($_POST as $key=>$val){
                if(!is_numeric($val)){
                    $val = '"'.$val.'"';
                }
                $sql .= $key.' = '.$val.' ,';
            }
            $sql .= ' updated = \''.$updated.'\', editor = '.$editor.' ';
            $sql .= ' WHERE id = '.$id;
            //echo $sql;


        }

        $pdo = DatabaseFactory::getFactory()->getConnection();
        $query = $pdo->prepare($sql);
        $query->execute();
        return $pdo->lastInsertId();

    }



}


