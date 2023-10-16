<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Button extends MY_Controller {



    public function __construct() {
        parent::__construct();

        header('Access-Control-Allow-Origin: * ');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With,Content-Type, Accept,authorization');
        header('Access-Control-Allow-Credentials', true);
        if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
            exit();
        }
    }

    public function getAllButtons() {
        $sql = "select  * from boss_portal_button  ";
        $btns = $this->db->query($sql)->result_array();
        $ret = array('code' => 200, 'msg' => 'success', 'data' => $btns,);
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
}
