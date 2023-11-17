<?php

class MUser  extends CI_Model {


  public function getUserProfile($user) {
    $userinfo = $this->db->get_where('nanx_user', ['user' => $user])->row_array();
    // debug($userinfo);


    $role = $this->getUserRole($user);
    $head_portrait = $this->getUserPortrait($userinfo);
    $deptid = $userinfo['deptid'];
    $deptname = $this->getDeptname($deptid);



    $ret = array(
      'user'          => $user,
      'department' => $deptname,
      'staff_name'    => $userinfo['staff_name'],
      'staff_id'      => $userinfo['id'],
      'mobile' => $userinfo['mobile'],
      'email' => $userinfo['email'],
      'role_code'     => $role['role_code'],
      'role_name'     => $role['role_name'],
      'head_portrait' => $head_portrait,
    );

    return $ret;
  }

  public function getDeptname($deptid) {
    $this->db->where(['id' => $deptid]);
    $row = $this->db->get('nanx_organization')->row_array();
    return $row['dept_name'];
  }


  public function getUserPortrait($userinfo) {

    if (is_null($userinfo['head_portrait'])) {
      $head_portrait = '/avatar/common_avatar.png';
      return $head_portrait;
    }
    if ($userinfo['head_portrait'] == '') {
      $head_portrait = '/avatar/common_avatar.png';
      return $head_portrait;
    }

    return $userinfo['head_portrait'];
  }


  public function getUserRole($user) {

    $sql = "select  nanx_user_role_assign.id, nanx_user_role_assign.role_code,
              role_name,nanx_user_role_assign.user 
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
