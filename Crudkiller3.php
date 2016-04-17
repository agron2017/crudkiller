<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 3/28/2016
 * Time: 11:18 AM
 */

namespace Bribrink\crudkiller;

class Crudkiller3
{
    private $table;
    private $id;
    private $fields;
    public $content;
    private $keysign1 = '';
    private $keysign2 = '_id';//if needed, but optional. set to empty string - double quotes - ''  - , if not used


    public function __construct(  $table,  $id=null,  $fields=array()  )
    {
        $this->table = $table;
        $this->content =  null;
        $this->id = $id;
        $this->fields = $fields;

        switch(Request::get('action')){
            case 'add':
                $this->content = $this->create();
                break;
            case 'view':
                $this->content =  $this->view();
                break;
            case 'edit':
                //create an edit use the same form, except that edit will pre-fill the values into the form
                $this->content = $this->create($this->id);
                break;
            case 'delete':
                $this->delete($this->id);
                $this->content =  $this->index();
                break;
            case 'update':
                //this is the action to write to the database
                $this->update();
                $this->content = $this->index();
                break;
            default:
                $this->content = $this->index();
                break;

        }

    }



    private function create( $id = null ){
        $f = new Formkiller($this->table, $id, $this->fields);
        return $f->form;
    }




    private function update()
    {
        Getter::set(Request::get('table'));
    }




    private function view()
    {
        //construct the desired class name with our variables,
        $t =  ucfirst($this->table).'killer';
        $e = new Entitykiller($t,$this->id);
        return $e->entitiyview;
    }




    private function index($id=null){

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
                $foreigntable[$v->Field] =  lcfirst( str_replace([$this->keysign1,$this->keysign2],'',$v->Field) );
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
                ON ".$v.".id = ".$this->table.$this->keysign1.".".$v.$this->keysign2."  ";

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



        $sqlmaster = $sql.$sqljoin.$sqlwhere;

        $query = $pdo->prepare($sqlmaster);
        $query->execute();

        $values = $query->fetchAll();

        ?>


        <?php
        $t = ' ';
        $t .= "<table id='modeltable' class='table table table-striped ' width='100%' >";
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
            $viewlink = ' <a data-id="'.$id.'" class="btn btn-primary btn-xs " href=\"?action=view&table='.$this->table.'&id='.$id.'" \><span class="glyphicon glyphicon-eye-open"></span></a>';
            if($this->table=='personnel'){
                $viewlink = '<a class="btn btn-primary btn-xs viewbutton" href="'.Config::get('URL').'admin/personnel?personnel_id='.$id.'" \><span class="glyphicon glyphicon-eye-open"></span></a>';
            }
            $t .= "<td>
                            <div class=\"btn-group-xs\">
                               ".$viewlink."
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
        $datatablesstyles = '<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/s/bs/jq-2.1.4,pdfmake-0.1.18,dt-1.10.10,af-2.1.0,b-1.1.0,b-colvis-1.1.0,b-html5-1.1.0,b-print-1.1.0,cr-1.3.0,fc-3.2.0,fh-3.1.0,kt-2.1.0,r-2.0.0,sc-1.4.0,se-1.1.0/datatables.min.css"/>';
       $datatablesjs = '<script type="text/javascript" src="https://cdn.datatables.net/s/bs/jq-2.1.4,pdfmake-0.1.18,dt-1.10.10,af-2.1.0,b-1.1.0,b-colvis-1.1.0,b-html5-1.1.0,b-print-1.1.0,cr-1.3.0,fc-3.2.0,fh-3.1.0,kt-2.1.0,r-2.0.0,sc-1.4.0,se-1.1.0/datatables.min.js"></script>
                        <script type="text/javascript" src="https://cdn.datatables.net/plug-ins/1.10.10/filtering/row-based/TableTools.ShowSelectedOnly.js"></script>
                        ';
       //return $datatablesstyles.$addbutton.$t.$datatablesjs; with cdn datatables
        //local hosted (seems smoother performance)
       return $addbutton.$t;
    }



    private function delete($id){
        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "DELETE FROM " . $this->table. " WHERE id = ".$id ;
        $query = $pdo->prepare($sql);
        $query->execute();
        return;
    }



}