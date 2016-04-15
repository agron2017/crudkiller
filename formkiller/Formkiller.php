<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 3/31/2016
 * Time: 5:33 PM
 */
class Formkiller
{
    public $form;
    public $datatables;

    private $table;
    private $id;
    private $fields;
    private $activeclass;




    public function __construct($table, $id, $fields = array() )
    {


        //set the active Classtable()
        $classname = ucfirst( str_replace('_','', $table ).'table' );
        $this->activeclass = new $classname();

        $this->table = $table;
        $this->id = $id;
        $this->fields = $fields;
        $this->form = $this->makeform($id);
        $this->datatables = $this->datatables($id);
    }




    private function makeform($id){


        $results = [];
        foreach( get_object_vars($this->activeclass) as $key ){
            $results[] = $key;
        }



        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "SHOW FULL COLUMNS FROM " . $this->table;
        $fields = $pdo->prepare($sql);
        $fields->execute();
        //$results = $fields->fetchAll();
        //check to get the fields we want form our $selectcolumns, if needed



        if( count($this->fields) < 0 ){
            //need to logic here, so its disabled (allow/disallow editing based on auth
            foreach($results as $values){
                if(in_array($values->name, $this->fields) ){
                    $columns[] = $values;
                }
            }
        } else{
            //or else just get all of them..
            $columns = $results;
        }




        $data = null; //initialize

        if( isset($id) ){
            //if we're not given specific fields, then we're getting every field
            $pdo = DatabaseFactory::getFactory()->getConnection();
            $sql = "SELECT * FROM " . $this->table. " WHERE id = ".$id ;
            $query = $pdo->prepare($sql);
            $query->execute();
            $data = $query->fetchObject();
        }

        $title = ucwords( str_replace('_',' ',$this->table) ); //splits table name, replaces the underscore (naming convention) and caps the first letter of each resulting word
        //form layouts:
        $cl = count($columns);
        $colswanted = ($cl<12) ? 2 : 3;
        $colslegth = $cl/$colswanted;

        $t = '

        <h3>'.$title.'</h3>
        <hr/>
        <div class="row">

        <form id="crud_'.$this->table.'_form" class="crudform " method="post" action="?action=update&table='.$this->table.'">
        <input type="hidden" name="table" value="'.$this->table.'" readonly />
        <div class="col-md-4" >';

        $count = 0; //counter for the form columns
        foreach ($columns AS $col) {

            //$colname = $col->Field;
            $colname = $col->name;
            $value = null;

            // this should set the field values and choose the fields
            if(!empty($data))
            {
                $value = $data->$colname;
            }

            //begin printing fields
            if ($col->name=='id')
            {
                $t.=Fields::hidden($colname, $value, $colname);
            }
            else if(isset( $this->select ) && $colname==$this->select){
                $list = array();
                $options = FormModel::getrecord($this->optiontable);
                foreach($options as $k=>$v){
                    $list[$v->id] = $v->name;
                }
                $t.=Fields::select($colname, null, $list,  $value );
            } else if( $colname=='editor' || $colname=='created'||$colname=='updated'){
                $t.= '';
            }
            else if( strpos($colname,'_type') ){
                $tbl = str_replace('_type','',$colname);
                $tbl = lcfirst($tbl);
                $t.=Fields::selectgen($colname, $col->label, $tbl, true, $value   );
            } else if( strpos($colname,'users') ){
                $t .= '';
            }
            else if( strpos($colname,'_id') ){
                $tbl = str_replace('_id','',$colname);
                $tbl = lcfirst($tbl);
                $t.=Fields::selectgen($colname, $col->label, $tbl, true, $value   );
            } else {
                $type = $col->type;
                switch ($type) {
                    case  strpos($type, 'int'):
                    case  strpos($type, 'smallint'):
                    case  strpos($type, 'bigint'):
                        $t.=Fields::number($colname, $value,ucfirst($colname),$col->label);
                        break;
                    case  strpos($type, 'tinyint'):
                        $t.=Fields::bool('active',$col->label,'Active','Inactive',$value);
                        break;
                    case strpos($type, 'char'):
                    case strpos($type, 'varchar'):
                    case strpos($type, 'tinytext'):
                    case strpos($type, 'text'):
                        $t.=Fields::text($colname,$value,ucfirst($colname),$col->label);
                        break;
                    case strpos($type, 'longtext'):
                    case strpos($type, 'mediumtext'):
                        $t.=Fields::textarea($colname,$value,ucfirst($colname),$col->label);
                        break;
                    case strpos($type,'timestamp'):
                        if($colname=='updated'){
                            $t.=Fields::hidden($colname,date('Y-m-d H:i:s'));
                        } else {
                            $t.=''; //do not edit time stamp
                        }
                        break;
                    case strpos($type,'time'):
                    case strpos($type,'date'):
                        $t.=Fields::date($colname,$value,ucfirst($colname),$col->label);
                        break;
                    case strpos($type,'year'):
                        $t.=Fields::year($colname,$value,ucfirst($colname),$col->label);
                        break;
                    case strpos($type,'datetime'):
                        $t.=Fields::date($colname,$value,ucfirst($colname),ucfirst($colname));
                        break;
                    default:
                        $t.=Fields::text($colname);
                }
            }
            $count++;
            if($count > $colslegth){
                $t .= '</div><div class="col-md-4" >';
                $count = 0;
            }

        }
        $t .= '<br/><input type="submit" class="btn btn-success" /> <a href="?action=index&id='.$id.'&table='.$this->table.' " class="btn btn-info" style="float: right" >Home</a></div></div>';

        $t .= '</form>
            ';
        return $t;

    }


    private function datatables( $id=null ){

        //get the columns, and the foreign key-tables
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "SHOW FULL COLUMNS FROM ".$this->table;
        $query = $pdo->prepare($sql);
        $query->execute();
        $columns = $query->fetchAll();

        //create foreign keys/tables
        $foreigntable = [];
        $fields = [];
        foreach($columns as $k=>$v){
            //set fields for later
            $fields[] = $v->Field;
            if( strpos( $v->Field,'_id')!==false ){
                $foreigntable[$v->Field] =  lcfirst( str_replace(['_id'],'',$v->Field) );
            }
        }

        $select = "$this->table.*";
        $pdo = DatabaseFactory::getFactory()->getConnection();
        //set up our strings to manipulate
        $sql = null;
        $sqljoin = null;
        $sqlwhere = null;

        if(count($foreigntable)>0){

            foreach($foreigntable as $k=>$v){
                $sqljoin .=
                    " LEFT JOIN ".$v."
                ON ".$v.".id = ".$this->table.".".ucfirst($v)."_id  ";

                //add a name to select from the table
                $select .= ",  $v.name as ".$v."_name";
            }

            $sqljoin = rtrim($sqljoin,',');
        }

        //now add in the $select, if it has changed from about
        $sql = "SELECT $select FROM ".$this->table;

        if (!is_null($id)){
            $sqlwhere .=  " WHERE $this->table.id = ".$id;
        }
        /*
                if (isset($id)){
                    $sqlwhere .=  " WHERE $this->table.id = ".$id;
                } */

        $sqlmaster = $sql.$sqljoin.$sqlwhere;

        $query = $pdo->prepare($sqlmaster);
        $query->execute();

        $values = $query->fetchAll();

        ?>
        <div class="container">

        <?php
        $t = ' ';
        $t .= "<table id='modeltable' class='table table-striped' width='100%'>";
        $t .= "<thead>";
        foreach($fields as $h){
            if(in_array($h,$this->fields)){
                $t .= "<th>".$h."</th>";
            }
        }
        $t .= "<th>Actions</th>";
        $t .= "</thead>";
        $t .= "<tbody>";
        foreach($values as $tr){
            $id = $tr->id;
            $t .= "<tr data-row='".$tr->id."' class='gettable'>";
            foreach($tr as $k=>$v){

                if(in_array($k,$this->fields)){
                    if( isset($foreigntable[$k]) ){
                        $tmp = $foreigntable[$k]."_name";
                        $v = $tr->$tmp;
                    }
                    $t .= "<td>".$v."</td>";
                }
            }
            $t .= "<td>
                            <div class=\"btn-group-xs\">
                                <a class=\"btn btn-primary btn-xs\" href=\"?action=view&table=".$this->table."&id=".$id." \"><span class='glyphicon glyphicon-eye-open'></span></a>
                                <a class=\"btn btn-info btn-xs\" href=\"?action=edit&id=".$id."&table=".$this->table." \"><span class='glyphicon glyphicon-edit'></span></a>
                                <a class=\"btn btn-danger btn-xs\" href=\"?action=delete&id=".$id."&table=".$this->table."\"><span class='glyphicon glyphicon-ban-circle'></span></a>
                            </div>
                      </td>";
            $t .= "</tr>";
        }
        $t .= "</tbody>";
        $t .= "</table>";
        $addbutton = '<a class="btn btn-success" href="?action=add&table='.$this->table.'" />
                            Add
                          </a><br/>';


        return $addbutton.$t;
    }

}