<?php

class MOrganization  extends CI_Model {



  public function __construct() {

    $this->load->model('Muser');
    $this->sub_depts = array();
  }


  public function getUserID($user) {
    $this->db->where('user', $user);
    $user =  $this->db->get('nanx_user')->row_array();
    return intval($user['id']);
  }


  public function getUserDeptId($user) {
    $this->db->where('user', $user);
    $user =  $this->db->get('nanx_user')->row_array();
    return intval($user['deptid']);
  }



  //获取部门所有用户
  public function getDeptMembers($deptid) {
    $sql = "select user,staff_name as username from   nanx_user     where  deptid= $deptid ";
    $rows =  $this->db->query($sql)->result_array();
    return $rows;
  }


  public function getDptLeader($deptid) {
    $this->db->where('id', $deptid);
    $row =  $this->db->get('nanx_organization')->row_array();

    if ($row) {
      return intval($row['dept_leader']);
    } else {
      return   -1;
    }
  }



  public function isLeader($userid, $deptid) {

    $dptleaderid = $this->getDptLeader($deptid);
    if ($userid  ===  $dptleaderid) {
      return true;
    } else {
      return false;
    }
  }


  function list_to_tree($id_field, $parent_field,  $list) {
    $list = array_column($list, null, $id_field);
    foreach ($list as $key => $val) {
      if ($val[$parent_field]) {
        if (isset($list[$val[$parent_field]])) {
          $list[$val[$parent_field]]['children'][] = &$list[$key];
        }
      }
    }
    foreach ($list as $key => $val) {
      if ($val[$parent_field]) unset($list[$key]);
    }
    return array_values($list);
  }


  function get_children_dept($dept_id) {
    $sql  = " SELECT * FROM   nanx_organization     WHERE parent  =$dept_id ";
    // debug($sql);


    $children = $this->db->query($sql)->result_array();
    foreach ($children as  $one_row) {
      $next_id = $one_row['id'];
      $this->sub_depts[] = $next_id;
      $children = $this->get_children_dept($next_id);
    }
  }


  //获取某个用户可以查看的所有"作者字段" (包括自己)
  function getUserScope($user) {

    $userid = $this->getUserID($user);
    $dept_id = $this->getUserDeptId($user);

    $this->get_children_dept($dept_id);
    $this->MOrganization->sub_depts;

    $isLeader = $this->isLeader($userid, $dept_id);

    if ($isLeader) {
      $this->get_children_dept($dept_id);
      $sub_depts = $this->sub_depts;
      $sub_depts[] = $dept_id;
      $depts_str = array_to_string($sub_depts, null);
      $sql = "select user from nanx_department_members where dptid in( $depts_str) ";
      $scope = $this->db->query($sql)->result_array();
      $scope = array_column($scope, 'user');
      return $scope;
    } else {
      return [$user];
    }
  }
}
