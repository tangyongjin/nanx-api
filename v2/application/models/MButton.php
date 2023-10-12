<?php

class MButton  extends CI_Model {

    function __construct() {
        parent::__construct();
    }


    //设置按钮的数据
    public function setButtonData($args) {

        $data = array(
            'using_component' => $args['using_component'],
            'name'          => $args['name'],
            'button_code'   => $args['button_code'],
            'icon'          => $args['icon'],
            'style'         => $args['style'],
            'file_path'     => $args['file_path'],

        );
        if (array_key_exists("component_name", $args) == true) {
            $data['component_name']  = $args['component_name'];
        }
        return $data;
    }


    public function getButtonListLikeNameOrButtonCodeMethod($args) {

        $sql = "select  * from boss_portal_button where 1=1";
        $part = "";
        if (array_key_exists("name", $args) == true) {
            $name = $args['name'];
            $part = $part . " and name like '%$name%' ";
        }
        if (array_key_exists("button_code", $args) == true) {
            $button_code = $args['button_code'];
            $part = $part . " and button_code like '%$button_code%' ";
        }

        $sql = $sql . $part;

        return $sql;
    }
}
