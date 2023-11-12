<?php

if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

class Permission extends MY_Controller {

  public function __construct() {
    parent::__construct();
  }

  public function getTreeMenuList() {
    $rows = $this->MMenu->getTreeMenuList();
    $rows = $this->MMenu->stringToBoolean($rows);
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);
    echo json_encode($ret);
  }

  public function getMenuTreeByRoleCode() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role_code = $args['role_code'];
    // 获取系统所有的菜单
    $rows = $this->MMenu->getTreeMenuList();
    $rows = $this->MMenu->stringToBoolean($rows);
    $menuList = [];

    // 获取 role 用到的 menuIds
    $this->db->where('role', $role_code);
    $this->db->select('menu_id');
    $menuIds = $this->db->get('nanx_portal_role_menu_permissions')->result_array();
    $menuIds = array_retrieve($menuIds, 'menu_id');

    foreach ($rows as   $one) {
      if (in_array($one['key'], $menuIds)) {
        $tmp = $one;
        $realChildren = [];
        foreach ($one['children'] as   $oneChild) {
          if (in_array($oneChild['key'], $menuIds)) {
            $realChildren[] = $oneChild;
          }
        }
        $tmp['children'] = $realChildren;
        $menuList[] = $tmp;
      }
    }

    $ret = [
      'code' => 200,
      'data' => ['menuList'  => $menuList,]
    ];
    echo json_encode($ret);
  }




  public function getRolesByMenuId() {
    $args = (array) json_decode(file_get_contents("php://input"));
    $menuId = $args['menu_id'];
    $sql = "SELECT nr.id,nr.role_code,nr.role_name from nanx_portal_role_menu_permissions bp 
                LEFT JOIN nanx_user_role nr on bp.role=nr.role_code  
                where bp.menu_id='$menuId'  ";
    $res = $this->db->query($sql)->result_array();
    $ret = array("code" => 200, 'data' => $res);
    echo json_encode($ret);
  }




  public function saveMenuPermission() {
    $args = (array) json_decode(file_get_contents('php://input'));


    $rolecode = $args['rolecode'];
    $menu_level = $args['menu_level'];
    $menuid = $args['menuid'];
    $parentid = $args['parentid'];

    if ($menu_level  == 1) {
      $arr = ['role' => $rolecode, 'menu_id' => $menuid];
      $this->db->where($arr);
      $this->db->delete('nanx_portal_role_menu_permissions');
      $this->db->insert('nanx_portal_role_menu_permissions', $arr);
    }

    if ($menu_level  == 2) {
      $arr1 = ['role' => $rolecode, 'menu_id' => $parentid];
      $this->db->where($arr1);
      $this->db->delete('nanx_portal_role_menu_permissions');

      $arr2 = ['role' => $rolecode, 'menu_id' => $menuid];
      $this->db->where($arr2);
      $this->db->delete('nanx_portal_role_menu_permissions');

      $this->db->insert('nanx_portal_role_menu_permissions', $arr1);
      $this->db->insert('nanx_portal_role_menu_permissions', $arr2);
    }

    $ret = ['code' => 200, 'msg' => 'success'];
    echo json_encode($ret);
  }


  public function deleteMenuPermission() {
    $args = (array) json_decode(file_get_contents('php://input'));


    $rolecode = $args['rolecode'];
    $menu_level = $args['menu_level'];
    $menuid = $args['menuid'];
    $parentid = $args['parentid'];

    if ($menu_level  == 1) {
      $arr = ['role' => $rolecode, 'menu_id' => $menuid];
      $this->db->where($arr);
      $this->db->delete('nanx_portal_role_menu_permissions');
    }

    if ($menu_level  == 2) {

      $arr2 = ['role' => $rolecode, 'menu_id' => $menuid];
      $this->db->where($arr2);
      $this->db->delete('nanx_portal_role_menu_permissions');

      $sql = " select id  from  nanx_portal_menu_list where parent_id= $parentid
               and id in (select menu_id from  nanx_portal_role_menu_permissions)  
             ";
      $existsSecondlevelMenu = $this->db->query($sql)->result_array();
      if (count($existsSecondlevelMenu)  == 0) {
        $arr1 = ['role' => $rolecode, 'menu_id' => $parentid];
        $this->db->where($arr1);
        $this->db->delete('nanx_portal_role_menu_permissions');
      }

      $ret = ['code' => 200, 'msg' => 'success', 'sql' => $sql];
      echo json_encode($ret);
    }
  }










  public function getUsersByMenuId() {
    $args = (array) json_decode(file_get_contents("php://input"));
    $menuId = $args['menu_id'];

    $sql = "SELECT nu.id,nr.role_code, nr.role_name, nu.staff_name, nu.head_portrait,nao.dept_name
                FROM nanx_portal_role_menu_permissions bp 
                LEFT JOIN nanx_user_role nr ON bp.role = nr.role_code 
                LEFT JOIN nanx_user_role_assign na ON na.role_code = nr.role_code 
                LEFT JOIN nanx_user nu ON nu.id = na.userid 
                LEFT JOIN nanx_organization nao on  nu.deptid=nao.id
                WHERE bp.menu_id = '$menuId'";
    $res = $this->db->query($sql)->result_array();
    $data = [];
    foreach ($res as  $value) {
      if ($value['head_portrait'] == "" || $value['head_portrait'] == NULL) {
        $value['head_portrait'] = 'http://' . $_SERVER['HTTP_HOST'] . "/avatar/common_avatar.png";
      } else {
        $value['head_portrait'] = $value['head_portrait'];
      }
      $data[] = $value;
    }
    $ret = array("code" => 200, 'data' => $data);
    echo json_encode($ret);
  }
}
