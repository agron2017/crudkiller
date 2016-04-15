<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 3/28/2016
 * Time: 11:18 AM
 */

class Crudkillerdatatables
{
    private $table;
    private $id;
    private $fields;
    public $content;

    public function __construct(  $table,  $id=null,  $fields=array()  )
    {
        $this->table = $table;
        $this->content =  $this->view();
        $this->id = $id;
        $this->fields = $fields;

        switch(Request::get('action')){
            case 'index':
                $this->content = $this->index();
                break;
            case 'add':
                $this->content = $this->create();
                break;
            case 'view':
                $this->content =  $this->view();
                break;
            case 'edit':
                $this->content = $this->create($this->id);
                break;
            case 'delete':
                $this->delete($this->id);
                $this->content =  $this->index();
                break;
            case 'update':
                $this->update();
                $this->content = $this->index();
                break;
            default:
                $this->content = $this->index();
                break;

        }

    }



    private function create( $id = null )
    {
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "SHOW FULL COLUMNS FROM " . $this->table;
        $fields = $pdo->prepare($sql);
        $fields->execute();
        $results = $fields->fetchAll();
        //check to get the fields we want form our $selectcolumns, if needed

        if( count($this->fields) < 0 ){
            //need to mlogic here, so its disabled (allow/disallow editing based on auth
            foreach($results as $values){
                if(in_array($values->Field, $this->fields) ){
                    $columns[] = $values;
                }
            }
        } else{
            //or else just get all of them..
            $columns = $results;
        }
        //if we're given a key-value, then we are looking for a specific row.
        // Get the values of the row and insert them
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

        $t = '
        <div class="col-lg-5" />
        <h3>'.$title.'</h3>
        <form id="crud_'.$this->table.'_form" class="crudform " method="post" action="?action=update">
        <input type="hidden" name="table" value="'.$this->table.'" readonly />';

        foreach ($columns AS $col) {
            $colname = $col->Field;
            $value = null;
            // this should set the field values and choose the fields
            if(!empty($data))
            {
                $value = $data->$colname;
            }
            if ($col->Key=='PRI')
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
            } else if($colname=='user_editor'){
                $t.=Fields::hidden('user_editor',Session::get('user_id'));
            }
            else if( strpos($colname,'_ID') ){
                $tbl = str_replace('_ID','',$colname);
                $tbl = str_replace('FK','',$tbl);
                $tbl = lcfirst($tbl);
                $t.=Fields::selectgen($colname, $col->Comment, $tbl, true, $value   );
            } else {
                $type = $col->Type;
                switch ($type) {
                    case  strpos($type, 'int'):
                    case  strpos($type, 'smallint'):
                    case  strpos($type, 'bigint'):
                        $t.=Fields::number($colname, $value,ucfirst($colname),$col->Comment);
                        break;
                    case  strpos($type, 'tinyint'):
                        $t.=Fields::bool('active',$col->Comment,'Active','Inactive',$value);
                        break;
                    case strpos($type, 'char'):
                    case strpos($type, 'varchar'):
                    case strpos($type, 'tinytext'):
                    case strpos($type, 'text'):
                        $t.=Fields::text($colname,$value,ucfirst($colname),$col->Comment);
                        break;
                    case strpos($type, 'longtext'):
                    case strpos($type, 'mediumtext'):
                        $t.=Fields::textarea($colname,$value,ucfirst($colname),$col->Comment);
                        break;
                    case 'date':
                        $t.=Fields::date($colname,$value,ucfirst($colname),$col->Comment);
                        break;
                    case strpos($type,'timestamp'):
                        if($colname=='updated'){
                            $t.=Fields::hidden($colname,date('Y-m-d H:i:s'));
                        } else {
                            $t.=''; //do not edit time stamp
                        }
                        break;
                    case strpos($type,'time'):
                        $t.=Fields::date($colname,$value,ucfirst($colname),ucfirst($colname));
                    case strpos($type,'datetime'):
                        $t.=Fields::date($colname,$value,ucfirst($colname),ucfirst($colname));
                        break;
                    default:
                        $t.=Fields::text($colname);
                }
            }

        }
        $t .= '<br/><br/><input type="submit" class="btn btn-success" /> <a href="?action=index" class="btn btn-info" style="float: right" >Home</a>';

        $t .= '</form>
            </div>';
        return $t;
    }




    private function update()
    {
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "SHOW COLUMNS FROM " . $this->table;
        $fields = $pdo->prepare($sql);
        $fields->execute();
        $results = $fields->fetchAll();
        $sql1 = "INSERT INTO ".$this->table." ( ";
        $sql2 = ") VALUES (";
        $squd = "";
        foreach($results as $value){
            if(isset($_POST[$value->Field])){
                $sql1.= " ".$value->Field." ,";
                $sql2.= " '". $_POST[$value->Field]."' ,";
                if($value->Key!='PRI'){
                    $squd.= $value->Field." = VALUES( ".$value->Field." ) ,";
                }
            }
        }
        $sql1t  = rtrim($sql1,',');
        $sql2t  = rtrim($sql2,',');
        $sqltud = rtrim($squd,',');

        $sql = $sql1t.$sql2t.")
        ON DUPLICATE KEY UPDATE  ".$sqltud;


        $query = $pdo->prepare($sql);
        $query->execute();

        return $pdo->lastInsertId();
    }




    private function view()
    {

    }




    private function index(){

        //get the columns, and the foreign key-tables
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "SHOW COLUMNS FROM ".$this->table;
        $query = $pdo->prepare($sql);
        $query->execute();
        $columns = $query->fetchAll();

        //create foreign keys/tables
        $foreigntable = [];
        $fields = [];
        foreach($columns as $k=>$v){
            //set fields for later
            $fields[] = $v->Field;
            if( strpos( $v->Field,'FK')!==false ){
                $foreigntable[$v->Field] =  lcfirst( str_replace(['FK','_ID'],'',$v->Field) );
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
                ON ".$v.".id = ".$this->table.".FK".ucfirst($v)."_ID  ";

                //add a name to select from the table
                $select .= ",  $v.name as ".$v."_name";
            }

            $sqljoin = rtrim($sqljoin,',');
        }

        //now add in the $select, if it has changed from about
        $sql = "SELECT $select FROM ".$this->table;


        if (isset($id)){
            $sqlwhere .=  " WHERE $this->table.id = ".$id;
        }

        $sqlmaster = $sql.$sqljoin.$sqlwhere;

        $query = $pdo->prepare($sqlmaster);
        $query->execute();

        $values = $query->fetchAll();

        ?>
        <div class="container">

        <?php
        $g = ' ';
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
                                <a class=\"btn btn-primary btn-xs\" href=\"?action=view\"><span class='glyphicon glyphicon-eye-open'></span></a>
                                <a class=\"btn btn-info btn-xs\" href=\"?action=edit\"><span class='glyphicon glyphicon-edit'></span></a>
                                <a class=\"btn btn-danger btn-xs\" href=\"?action=delete\"><span class='glyphicon glyphicon-ban-circle'></span></a>
                            </div>
                      </td>";
            $t .= "</tr>";
        }
        $t .= "</tbody>";
        $t .= "</table>";
            $addbutton = '<a class="btn btn-success" href="?action=add"/>
                            Add
                          </a><br/>';
        $datatablesstyles = '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/s/bs/jq-2.1.4,pdfmake-0.1.18,dt-1.10.10,af-2.1.0,b-1.1.0,b-colvis-1.1.0,b-html5-1.1.0,b-print-1.1.0,cr-1.3.0,fc-3.2.0,fh-3.1.0,kt-2.1.0,r-2.0.0,sc-1.4.0,se-1.1.0/datatables.min.css"/>';
        $datatablesjs = '<script type="text/javascript" src="https://cdn.datatables.net/s/bs/jq-2.1.4,pdfmake-0.1.18,dt-1.10.10,af-2.1.0,b-1.1.0,b-colvis-1.1.0,b-html5-1.1.0,b-print-1.1.0,cr-1.3.0,fc-3.2.0,fh-3.1.0,kt-2.1.0,r-2.0.0,sc-1.4.0,se-1.1.0/datatables.min.js"></script>
                        <script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.10/filtering/row-based/TableTools.ShowSelectedOnly.js"></script>
                        <script type="text/javascript">
                            $(function(){
                                $(".table").DataTable();
                            });
                        </script>';
        return $datatablesstyles.$addbutton.$t.$datatablesjs;
    }




    private function delete($id){
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM " . $this->table. " WHERE id = ".$id ;
        $query = $pdo->prepare($sql);
        $query->execute();
        return;
    }


}