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

        $this->load->helper('my_jwt_helper');
        $this->load->model("MButton");
    }



    public function addButtonAndActionCodeConnect() {

        $args = (array) json_decode(file_get_contents("php://input"));
        $data = array(
            'datagrid_code' => $args['datagrid_code'],
        );
        $this->db->where('id', $args['id']);
        $this->db->update('boss_portal_button', $data);
        $err = $this->db->error();
        if ($err['code'] == 0) {
            $ret = array('code' => 200, 'msg' => '操作成功');
        } else {
            $ret = array('code' => 400, 'msg' => $err['message']);
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }









    public function insertButton() {


        $args = (array) json_decode(file_get_contents("php://input"));
        $data = $this->MButton->setButtonData($args);

        $this->db->insert('boss_portal_button', $data);
        $err = $this->db->error();
        if ($err['code'] == 0) {
            $ret = array('code' => 200, 'msg' => '操作成功');
        } else {
            $ret = array('code' => 400, 'msg' => $err['message']);
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }





    public function deleteButtonById() {


        $args = (array) json_decode(file_get_contents("php://input"));

        $this->db->delete('boss_portal_button', array('id' => $args['id']));
        $err = $this->db->error();
        if ($err['code'] == 0) {
            $ret = array('code' => 200, 'msg' => '操作成功');
        } else {
            $ret = array('code' => 400, 'msg' => $err['message']);
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
    public function updateButtonById() {
        $args = (array) json_decode(file_get_contents("php://input"));
        $data = $this->MButton->setButtonData($args);

        $this->db->where('id', $args['id']);
        $this->db->update('boss_portal_button', $data);
        $err = $this->db->error();
        if ($err['code'] == 0) {
            $ret = array('code' => 200, 'msg' => '操作成功');
        } else {
            $ret = array('code' => 400, 'msg' => $err['message']);
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }





    public function getButtonListLikeNameOrButtonCodeMethod() {

        $args = (array) json_decode(file_get_contents("php://input"));


        $sql  = $this->MButton->getButtonListLikeNameOrButtonCodeMethod($args);
        $total = $this->db->query($sql)->result_array();
        if (array_key_exists("size", $args) == true) {
            $size = $args['size'];
            $page = ($args['page'] - 1) * $size;
            $sql = $sql . " order by id  limit $page,$size ";
        }


        $data = $this->db->query($sql)->result_array();
        foreach ($data as $key => $btn) {
            $data[$key]['key'] = $btn['id'];
        }


        $ret = array('code' => 200, 'msg' => 'success', 'data' => $data, 'total' => count($total));
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
}
