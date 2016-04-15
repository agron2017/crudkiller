<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 3/31/2016
 * Time: 5:13 PM
 */
class Entitykiller
{
    public $entitiyview;
    private $id;




    public function __construct($entity,$id){
        //$e will hold our entity
        $this->id = $id;
        $e = new $entity($id);
        $entityprops = get_object_vars($e);
        $this->entitiyview = $this->entityviewer($entityprops);
    }




    private function entityviewer($entity){
        $html = '';
        foreach($entity as $k=>$v){
            $title = str_replace('_',' ',$k);
            $html .= '<a href="#"  class="btn btn-default entitybutton" data-entity="'.$k.'" data-id="'.$this->id.'" />'.ucwords($title).'</a>';
        }
        return $html;
    }





}