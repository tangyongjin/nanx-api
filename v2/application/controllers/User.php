<?php

defined('BASEPATH') or exit('No direct script access allowed');

class User extends MY_Controller {


    public function __construct() {
        parent::__construct();
    }



    public function logout() {
        http_response_code(401);
        return false;
        die();
    }




    public function saveRoleAsign() {

        $json_paras = (array) json_decode(file_get_contents('php://input'));
        $roles = $json_paras['roles'];
        $userid = $json_paras['userid'];
        $user = $this->MUser->getUserById($userid);
        $tmp = [];
        $sql = "delete  from nanx_user_role_assign where userid=  $userid ";
        $this->db->query($sql);
        foreach ($roles as $key => $role) {
            $tmp = [];
            $tmp['user'] = $user;
            $tmp['userid'] = $userid;
            $tmp['role_code'] = $role;
            $this->db->insert('nanx_user_role_assign', $tmp);
        }

        $ret = ['code' => 200, 'message' => '分配成功'];
        echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    }
}
