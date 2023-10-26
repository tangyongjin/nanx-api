<?php
class MPermission extends CI_Model {
  public function __construct() {
    parent::__construct();
  }


  public function addSqlPart($args) {
    $part = '';
    if (true == array_key_exists('role_code', $args)) {
      $role_code = $args['role_code'];
      if ('' != $role_code) {
        $part = " and role_code like '%$role_code%'";
      }
    }
    if (true == array_key_exists('role_name', $args)) {
      $role_name = $args['role_name'];
      if ('' != $role_name) {
        $part = " and role_name like '%$role_name%'";
      }
    }

    return $part;
  }

  //查询角色列表  getRoleLists
  public function getRoleListsIsAddCondition($part, $page, $size) {
    $prefix_sql = "select id 'key',role_code,role_name FROM nanx_user_role WHERE 1=1 ";
    $suffix_sql = " ORDER BY id desc LIMIT  $page, $size";
    $sql = $prefix_sql . $part . $suffix_sql;

    return $this->db->query($sql)->result_array();
  }

  //查询角色列表  getRoleLists
  public function queryCount($part) {
    $count_sql = 'select * FROM nanx_user_role WHERE 1=1 ';
    $count_sql = $count_sql . $part;
    $res_count = $this->db->query($count_sql)->result_array();
    $total = count($res_count);
    return $total;
  }

  //新增菜单
  public function addRoleMenu($role, $menu_ids) {
    $menu_id = $menu_ids[0];
    $data = array(
      'role' => $role,
      'menu_id' =>  $menu_id
    );
    $this->db->insert('nanx_portal_role_menu_permissions', $data);
    $ret = array('code' => 200, 'msg' => 'success');
    return $ret;
  }

  public function deleteMenuOrButton($menu_id, $role, $table, $id) {
    $menu_id = array_to_string($menu_id, '');
    $sql = 'DELETE  from  ' . $table . " where role='$role' and $id in ($menu_id)";
    $this->db->query($sql);
    $err = $this->db->error();
    if (0 == $err['code']) {
      $ret = array('code' => 200, 'msg' => '操作成功');
    }
    if (0 != $err['code']) {
      $ret = array('code' => 400, 'msg' => $err['message']);
    }

    return $ret;
  }
}
