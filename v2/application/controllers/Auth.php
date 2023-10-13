<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Auth extends MY_Controller {

  private $user_mobile;

  public function __construct() {

    parent::__construct();
    header('Access-Control-Allow-Origin: * ');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept,authorization,Cache-Control');
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
      exit();
    }

    $this->router->fetch_method();
    getallheaders();
    $this->load->helper('my_jwt_helper');
  }




  public function getUserRolesById() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $id = $args['id'];
    $sql = "SELECT nura.role_code FROM nanx_user_role_assign nura JOIN nanx_user_role nur 
        ON nura.role_code = nur.role_code WHERE nura.userid = '$id'";
    $res = $this->db->query($sql)->result_array();
    $ret = array(
      'code' => 200,
      'message' => "success",
      'data' => $res,
    );
    echo json_encode($ret);
  }


  public function changepwd() {

    $json_paras = (array) json_decode(file_get_contents('php://input'), true);
    $this->load->model('MUser');
    // $mobile  = $json_paras['mobile'];
    $mobile = $this->getMobile();
    $new_pwd = $json_paras['new_pwd'];

    $row = $this->MUser->getUserbyMobile($mobile);
    $pwd_try_with_salt = md5(md5($new_pwd) . $row['salt']);
    $data = array(
      'password' => $pwd_try_with_salt
    );
    $this->db->where('mobile', $mobile);
    $this->db->update('nanx_user', $data);
    $db_error = $this->db->error();

    if (0 == $db_error['code']) {
      $ret = ['code' => 200, 'message' => '更新成功', 'data' => null];
    } else {
      $ret = ['code' => $db_error['code'], 'message' => '数据库操作失败,DBcode:' . $db_error['code'], 'data' => null];
    }
    echo json_encode($ret);
  }



  function profile() {

    $user = $this->getUser();
    $profile = $this->MUser->userprofile($user);
    $ret = array(
      "code" => 200,
      "message" => "success",
      "data" => $profile
    );
    echo json_encode($ret);
  }


  //扫码登录
  public function login_qrscan() {
    $secret_key = 'cnix_key_login_2342342324';
    $valid_for = '36000';

    $json_paras = (array) json_decode(file_get_contents('php://input'), true);
    $transaction_id = $json_paras['transaction_id'];
    $sql = "select * from nanx_qrcode_login_session where transaction_id='$transaction_id' ";

    $row = $this->db->query($sql)->row_array();

    //用户还没有扫码,nanx_qrcode_login_session中没有记录
    if (empty($row)) {
      http_response_code(401);
      return false;
    }

    $mobile = $row['scanner_mobile'];
    $query = $this->db->get_where('nanx_user', array(
      'mobile' => $mobile,
    ));
    $row = $query->row_array();
    if (empty($row)) {
      http_response_code(401);

      return false;
    }

    $user = $row['user'];
    $token = array();
    $token['id'] = $row['id'];
    $token['mobile'] = $mobile;
    $token['exp'] = time() + $valid_for;
    $token['user'] = $user;

    // $message_count = $this->getWechatMetionFunction($custid);
    // $addresss_count = $this->getUnFinishedAddressFunction($custid);
    // $affair_count = $this->getPendingTaskCount($user);

    // $badge_sum = $affair_count + $message_count + $addresss_count;

    $message_count = 0;
    $addresss_count = 0;
    $affair_count = 0;
    $badge_sum = 100;
    $this->load->helper('my_jwt_helper');
    $profile = $this->MUser->userprofile($user);
    echo json_encode(array(
      'code' => 200,
      'token'          => JWT::encode($token, $secret_key),
      'badge_sum'      => $badge_sum,
      'addresss_count' => $addresss_count,
      'message_count'  => $message_count,
      'affair_count'   => $affair_count,
      'profile'        => $profile,
    ));

    return true;
  }



  public function login_mobile() {
    logtext("进入登陆");
    $json_paras = (array) json_decode(file_get_contents('php://input'));

    $secret_key = 'cnix_key_login_2342342324';
    $valid_for = '36000000';

    if (!array_key_exists('mobile', $json_paras)) {
      http_response_code(401);

      return false;
    }
    if (!array_key_exists('password', $json_paras)) {
      http_response_code(401);

      return false;
    }

    // debug($json_paras);

    // $mobile = $this->decodeStr($json_paras['mobile']);
    // $password = $this->decodeStr($json_paras['password']);

    $mobile =   $json_paras['mobile'];
    $password = $json_paras['password'];


    $trylogin = $this->db_login($mobile, $password);
    // $trylogin = 'success';
    if (!('success' == $trylogin)) {
      $ret = array('code' => 401, 'message' => 'Message:[mobile/password] not match');
      http_response_code(401);
      echo json_encode($ret);
      return false;
    }



    $data = array(
      'transaction_id'  => $json_paras['transaction_id'],
      'update_datetime' => date('Y-m-d h:i:sa'),
      'scanner_mobile'  => $mobile,
      'session_id'      => $password,
    );

    $this->db->insert('nanx_qrcode_login_session', $data);
    $this->load->helper('my_jwt_helper');
    $res = $this->MUser->getUserByMobile($mobile);
    $user = $res['user'];
    $token = array();
    $token['id'] = $res['id'];
    $token['mobile'] = $mobile;
    $token['exp'] = time() + $valid_for;
    $token['user'] = $res['user'];
    $message_count = 0;
    $addresss_count = 0;
    $affair_count = 0;
    $badge_sum = 0;
    $profile = $this->MUser->userprofile($user);
    $ret = [];
    $ret = array(
      'tags' => 'jenkins',
      'token'          => JWT::encode($token, $secret_key),
      'badge_sum'      => $badge_sum,
      'addresss_count' => $addresss_count,
      'message_count'  => $message_count,
      'affair_count'   => $affair_count,
      'profile'        => $profile,
      'code' => 200
    );

    echo json_encode($ret);
  }

  public function decodeStr($encoded) {
    $decoded = '';
    for ($i = 0; $i < strlen($encoded); $i++) {
      $b = ord($encoded[$i]);
      $a = $b ^ 51;  // <-- must be same number used to encode the character
      $decoded .= chr($a);
    }
    return $decoded;
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
