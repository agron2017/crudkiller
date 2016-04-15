<?php

/**
 * Created by PhpStorm.
 * User: bribrink
 * Date: 1/6/2016
 * Time: 8:23 AM
 */
class Fields
{



    public static function hidden($name, $value=null, $placeholder=null, $label=null, $readonly=null){
        if($readonly!=null){
            $readonly = 'readonly';
        }
            $html = '
            <input type="hidden" name="'.$name.'" value="'.$value.'"  placeholder="'.$placeholder.'" '.$readonly.'  />
            ';
        return $html;
    }




    public static function number($name,  $value=null, $placeholder=null, $label=null, $readonly=null){

        $label = '<label>'.$label.'</label>';

        if($readonly!=null){
            $readonly = 'readonly';
        }
        $html = '
            <div class="form-group">
            '.$label.'
            <input class="form-control" type="text" name="'.$name.'" value="'.$value.'"   placeholder="'.$placeholder.'" '.$readonly.'   />
            </div>
            ';
        return $html;

    }




    public static function text($name,  $value=null, $placeholder=null, $label=null, $readonly=null){

            $label1 = '<label>'.$label.'</label>';

            if($readonly!=null){
            $readonly = 'readonly';
            }
            $html = '
            <div class="form-group">
            '.$label1.'
            <input class="form-control" type="text" name="'.$name.'" value="'.$value.'"   placeholder="'.$placeholder.'" '.$readonly.'   />
            </div>
            ';
        return $html;

    }



    public static function selectgen($name, $label, $table,  $required=null,  $selected=null ){
        if($required!=null){$required = 'required'; }
        $s='';
        $s.='<div class="form-group">
            <label>'.$label.'</label>
            <select class="form-control" name="'.$name.'" required >';
        $s.='<option value=""    > Select </option>';


        $pdo = DatabaseFactory::getFactory()->getConnection();
        $sql = "SELECT id, name FROM  ".$table;
        $query = $pdo->prepare($sql);
        $query->execute();
        $options = $query->fetchAll();
        foreach($options as $k=>$v){

           $s.='<option value="'.$v->id.'" ';
               if($v->id == $selected){$s.=' selected ';}
           $s .= '    >'.$v->name.'</option>';
        }
        $s .='</select>
        </div>';
        return $s;
    }



    public static function select($name, $placeholder=null, $options, $selected=null, $required=null ){
        //selected - the 'key' in 'values' ...
        if($required!=null){$required = 'required'; }
        $s='';
        $s.='<div class="form-group">
            <label>'.$placeholder.'</label>
            <select class="form-control" name="'.$name.'" required >';
        $s.='<option value=""    > '.$placeholder.'</option>';

            foreach($options as $d=>$name){
                if ($d!=$selected) {
                    $s.='<option value="'.$d.'"    >'.$name.'</option>';
                } else {
                    $s.='<option value="'.$d.'" selected   >'.$name.'</option>';
                }
            }

        $s .='</select>
        </div>';
        return $s;
    }



    public static function bool($name, $title, $on=null, $off=null, $selected=null){
        $on1 = ''; $on2 = '';
        if ($selected==1){
            $on1 = 'checked';
        } else {
            $on2 = 'checked';
        }
        $on = (is_null($on)) ? 'Yes' : $on;
        $off = (is_null($off)) ? 'Off' : $off;
        $html = '

                    <label class="radio-inline" ><input type="radio" name="'.$name.'" '.$on1.' >'.$on.'</label>

                    <label class="radio-inline" ><input type="radio" name="'.$name.'" '.$on2.' >'.$off.'</label>

                ';
        return $html;
    }



    public static function date($name, $value=null, $placeholder=null, $title=null){

        $html = '
        <div class="form-group">
        <label>'.$title.'</label>
        <input type="date" name="'.$name.'" value="'.$value.'"  placeholder="'.$placeholder.'" class="form-control datepicker"  />
        </div>';
        return $html;
    }




    public static function year($name, $value=null, $placeholder=null, $title=null, $min=1975, $max=2020){
        //just a date select box
        $s='';
        $s.='<div class="form-group">
            <label>'.$placeholder.'</label>
            <select class="form-control" name="'.$name.'" required >';

        for($i=$min; $i<$max; $i++){
            $selected = ' ';
            if ( $i==$value ) {
                $selected = ' selected ';
            }
            $s.='<option value="'.$i.'"  '.$selected.'   >'.$i.'</option>';
        }

        $s .='</select>
        </div>';
        return $s;
    }




    public static function textarea($name,  $value=null, $title=null, $required=null){
        $r = ($required!=null)?'required':'';
        $t = ($title!=null)?$title:'';
        $html = '
            <div class="form-group">
            <label>'.$t.'</label>
            <textarea class="form-control" name="'.$name.'" class="form-control"  rows="3"  '.$r.' >'.$value.'</textarea>
            </div>
        ';
        return $html;
    }




    public static function checkbox($name, $value=null, $label=null, $checked=null){
        $html = '
        <div class="form-horizontal">
        <label>'.$label.'
        <input class="checkbox" type="checkbox" name="'.$name.'" value="'.$value.'"   placeholder="'.$label.'"  '.$checked.' >
        </label>
        </div>
        ';
        return $html;
    }




    public static function button($name, $value=null, $placeholder=null, $id=null, $class=null, $data=null  ){
        //for consistency, but we dont want it serialized into a form submit, so we're using as a data-tag, maybe to
        //open up another table or something
        if($id!=null){
            $id = 'id="'.$id.'"';
        }

        $html = '
        <div class="form-group">
        <button  '.$id.' class="btn btn-default '.$class.'" value="'.$value.'"  placeholder="'.$placeholder.'"  data-name="'.$name.'" '.$data.'  >'
            .$value.
        '</button>
        </div>';
        return $html;
    }


    public static function readonly($name, $value=null, $label=null ){
        $t = ($label!=null)?$label:'';
        $html = '
            <div class="form-group">
            <label>'.$t.'</label>
            <input class="form-control" type="text" name="'.$name.'" value="'.$value.'"  readonly />
            </div>
            ';
        return $html;

    }


    public static function formlabel( $value=null){
        $html = '<h3>'.$value.'</h3>';
        return $html;
    }





}
