<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller {


  public function __construct() {

    parent::__construct();
    $this->router->fetch_method();
    getallheaders();
    $this->load->helper('my_jwt_helper');
  }


  public function changepwd() {

    $json_paras = (array) json_decode(file_get_contents('php://input'), true);
    $this->load->model('MUser');
    $mobile = $this->getMobile();
    $new_pwd = $json_paras['new_pwd'];
    $salt = randstr(6);

    $pwd_try_with_salt = md5(md5($new_pwd) .  $salt);
    $data = ['password' => $pwd_try_with_salt, 'salt' => $salt];
    $this->db->where('mobile', $mobile);
    $this->db->update('nanx_user', $data);
    $db_error = $this->db->error();

    if (0 == $db_error['code']) {
      $ret = ['code' => 200, 'message' => '密码更新成功', 'data' => null];
    } else {
      $ret = ['code' => $db_error['code'], 'message' => '数据库操作失败,DBcode:' . $db_error['code'], 'data' => null];
    }
    echo json_encode($ret);
  }

  public function resetPassword() {

    $json_paras = (array) json_decode(file_get_contents('php://input'), true);
    $this->load->model('MUser');
    $mobile = $json_paras['mobile'];
    $new_pwd = '12345678';
    $salt = randstr(6);
    $pwd_try_with_salt = md5(md5($new_pwd) .   $salt);
    $data = ['password' => $pwd_try_with_salt, 'salt' => $salt];
    $this->db->where('mobile', $mobile);
    $this->db->update('nanx_user', $data);
    $db_error = $this->db->error();

    if (0 == $db_error['code']) {
      $ret = ['code' => 200, 'message' => '密码重置成功(12345678)', 'data' => null];
    } else {
      $ret = ['code' => $db_error['code'], 'message' => '数据库操作失败,DBcode:' . $db_error['code'], 'data' => null];
    }
    echo json_encode($ret);
  }



  function profile() {

    $user = $this->getUser();
    $profile = $this->MUser->getUserProfile($user);
    $ret = array(
      "code" => 200,
      "message" => "success",
      "data" => $profile
    );
    echo json_encode($ret);
  }


  public function loginMobile() {
    // sleep(1);
    // logtext("进入登陆");
    $json_paras = (array) json_decode(file_get_contents('php://input'));

    if (!array_key_exists('mobile', $json_paras)) {
      http_response_code(401);
      return false;
    }

    if (!array_key_exists('password', $json_paras)) {
      http_response_code(401);
      return false;
    }

    $mobile =   $json_paras['mobile'];
    $password = $json_paras['password'];
    $trylogin = $this->db_login($mobile, $password);
    if (!('success' == $trylogin)) {
      $ret = array('code' => 401, 'message' => 'Message:[mobile/password] not match');
      http_response_code(401);
      echo json_encode($ret);
      return false;
    }

    $userRow = $this->MUser->getUserByMobile($mobile);
    $user = $userRow['user'];
    $profile = $this->MUser->getUserProfile($user);


    $ret = [];
    if (empty($profile['role_code'])) {
      $ret = ['profile' => $profile, 'code' => 500, 'message' => '该用户无任何角色'];
      echo json_encode($ret);
      return;
    }

    $data = ['transaction_id' => $json_paras['transaction_id'], 'login_datetime' => date('Y-m-d h:i:sa'), 'mobile' => $mobile];
    $this->db->insert('nanx_qrcode_login_session', $data);

    $ret = ['token' =>  $this->JwtToken($mobile, $user),   'profile' => $profile, 'code' => 200];
    echo json_encode($ret);
  }


  public  function JwtToken($mobile, $user) {
    $this->load->helper('my_jwt_helper');
    $secret_key = 'nanx_xiaoke-20211213';
    $valid_for = '36000000';
    $token = [];
    $token['mobile'] = $mobile;
    $token['exp'] = time() + $valid_for;
    $token['user'] = $user;
    return JWT::encode($token, $secret_key);
  }


  public function db_login($mobile, $pwd_try) {
    $user = $this->db->select('id,user,password,staff_name,active,salt')->get_where('nanx_user', ['mobile' => $mobile])->result_array();

    if (1 != sizeof($user)) {
      return 'user_not_found';
    }

    if (1 == sizeof($user)) {
      $salt = $user[0]['salt'];
      $pwd_db = $user[0]['password'];

      $pwd_try_with_salt = md5(md5($pwd_try) . $salt);

      if ($pwd_try_with_salt == $pwd_db) {
        return  'success';
      } else {
        return  'false';
      }
    }
  }



  public function logout() {
    http_response_code(401);
    return false;
    die();
  }
}
