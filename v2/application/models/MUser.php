<?php

class MUser  extends CI_Model {

  public function __construct() {
  }


  public function userprofile($user) {
    $userinfo = $this->db->get_where('nanx_user', ['user' => $user])->row_array();


    $roles = $this->getUserRoles($user);
    if (is_null($userinfo['head_portrait'])) {
      $head_portrait = 'http://' . $_SERVER['HTTP_HOST'] . '/upload/common_avatar.png';
    } else {

      if (strpos($userinfo['head_portrait'], 'http://') === false) {
        $head_portrait =  $userinfo['head_portrait'];
      } else {
        $head_portrait = 'http://' . $_SERVER['HTTP_HOST'] . $userinfo['head_portrait'];
      }
    }


    $deptid = $userinfo['deptid'];

    $deptname = $this->getDeptname($deptid);


    $role = $roles[0];  //Fixme, 可能有多个角色.

    $ret = array(
      'user'          => $user,
      'department' => $deptname,
      'staff_name'    => $userinfo['staff_name'],
      'staff_id'      => $userinfo['id'],
      'wx_nickname' => $userinfo['wx_nickname'],
      'wx_avatar' => $userinfo['wx_avatar'],
      'mobile' => $userinfo['mobile'],
      'email' => $userinfo['email'],
      'roles' => $roles,
      'role_code'     => $role['role_code'],
      'role_name'     => $role['role_name'],

      'head_portrait' => $head_portrait,
      'receive_mail_notify'     => $userinfo['receive_mail_notify'],
      'receive_sms_notify' => $userinfo['receive_sms_notify'],
      'receive_dingtalk_notify' => $userinfo['receive_dingtalk_notify']
    );

    return $ret;
  }

  public function getDeptname($deptid) {


    $this->db->where(['id' => $deptid]);
    $row = $this->db->get('nanx_organization')->row_array();
    return $row['dept_name'];
  }



  public function getUserRoles($user) {

    $sql = "select  nanx_user_role_assign.role_code,role_name
              from nanx_user_role_assign,nanx_user_role
              where user='{$user}'
              and   nanx_user_role.role_code=nanx_user_role_assign.role_code ";

    $roles = $this->db->query($sql)->result_array();
    return $roles;
  }


  public function getUserbyMobile($mobile) {
    $this->db->where('mobile', $mobile);
    $user =  $this->db->get('nanx_user')->row_array();
    return $user;
  }


  public function getUserById($id) {

    $this->db->where(['id' => $id]);
    $row = $this->db->get("nanx_user")->row_array();
    return $row['user'];
  }
}
