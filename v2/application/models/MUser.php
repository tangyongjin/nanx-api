<?php

class MUser  extends CI_Model {


  public function userprofile($user) {
    $userinfo = $this->db->get_where('nanx_user', ['user' => $user])->row_array();


    $role = $this->getUserRole($user);
    $head_portrait = $this->getUserPortrait($userinfo);
    $deptid = $userinfo['deptid'];
    $deptname = $this->getDeptname($deptid);



    $ret = array(
      'user'          => $user,
      'department' => $deptname,
      'staff_name'    => $userinfo['staff_name'],
      'staff_id'      => $userinfo['id'],
      'wx_nickname' => $userinfo['wx_nickname'],
      'wx_avatar' => $userinfo['wx_avatar'],
      'mobile' => $userinfo['mobile'],
      'email' => $userinfo['email'],
      'role_code'     => $role['role_code'],
      'role_name'     => $role['role_name'],
      'head_portrait' => $head_portrait,
      'receive_mail_notify'     => $userinfo['receive_mail_notify'],
      'receive_sms_notify' => $userinfo['receive_sms_notify'],
    );

    return $ret;
  }

  public function getDeptname($deptid) {
    $this->db->where(['id' => $deptid]);
    $row = $this->db->get('nanx_organization')->row_array();
    return $row['dept_name'];
  }


  private function getUserPortrait($userinfo) {

    if (is_null($userinfo['head_portrait'])) {
      $head_portrait = 'http://' . $_SERVER['HTTP_HOST'] . '/upload/common_avatar.png';
    } else {
      if (strpos($userinfo['head_portrait'], 'http://') === false) {
        $head_portrait =  $userinfo['head_portrait'];
      } else {
        $head_portrait = 'http://' . $_SERVER['HTTP_HOST'] . $userinfo['head_portrait'];
      }
    }
    return $head_portrait;
  }


  public function getUserRole($user) {

    $sql = "select  nanx_user_role_assign.role_code,role_name
              from nanx_user_role_assign,nanx_user_role
              where user='{$user}'
              and   nanx_user_role.role_code=nanx_user_role_assign.role_code limit 1";

    $role = $this->db->query($sql)->row_array();
    return $role;
  }


  public function getUserbyMobile($mobile) {
    $this->db->where('mobile', $mobile);
    $user =  $this->db->get('nanx_user')->row_array();
    return $user;
  }
}
