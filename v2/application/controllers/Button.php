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
        $sql = "select  * from nanx_portal_button  ";
        $btns = $this->db->query($sql)->result_array();
        $ret = array('code' => 200, 'msg' => 'success', 'data' => $btns,);
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }

    public function getGridButtons() {
        $json_paras = (array) json_decode(file_get_contents('php://input'), true);
        $DataGridCode  = $json_paras['DataGridCode'];
        $sql = "select * from nanx_grid_button where datagrid_code='{$DataGridCode}' order by btnorder asc  ";
        $Buttons = $this->db->query($sql)->result_array();
        $ret = [];
        $ret['code'] = 200;
        $ret['buttons'] = $Buttons;
        $ret['msg'] = "success";
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        return;
    }
    public function addGridButton() {
        $json_paras = (array) json_decode(file_get_contents('php://input'), true);
        $btncode  = $json_paras['btncode'];
        $DataGridCode = $json_paras['DataGridCode'];

        $already_btns = $this->db->query("select * from nanx_grid_button where datagrid_code='{$DataGridCode}' ")->result_array();
        $counter = count($already_btns);
        $btnorder = intval($counter) + 1;
        $this->db->insert('nanx_portal_button_actcode', ['button_code' => $btncode, 'datagrid_code' => $DataGridCode, 'btnorder' => $btnorder]);

        $db_error = $this->db->error();

        if (0 == $db_error['code']) {
            $ret = ['code' => 200, 'message' => '添加按钮成功'];
        } else {
            $ret = ['code' => $db_error['code'], 'message' => '添加按钮失败,DBcode:' . $db_error['code'], 'data' => null];
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        return;
    }

    public function deleteGridButton() {
        $json_paras = (array) json_decode(file_get_contents('php://input'), true);
        $button_code  = $json_paras['button_code'];
        $datagrid_code = $json_paras['datagrid_code'];

        $this->db->where(['button_code' => $button_code, 'datagrid_code' => $datagrid_code]);
        $this->db->delete('nanx_portal_button_actcode');
        $db_error = $this->db->error();
        if (0 == $db_error['code']) {
            $ret = ['code' => 200, 'message' => '删除按钮成功'];
        } else {
            $ret = ['code' => $db_error['code'], 'message' => '添加按钮失败,DBcode:' . $db_error['code']];
        }
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
        return;
    }
}
