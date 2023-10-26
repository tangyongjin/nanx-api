<?php

if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

class Permission extends MY_Controller {

  public function __construct() {
    parent::__construct();
  }

  // 登录用菜单列表
  public function getMenuTreeByRoleCode() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role_code = $args['role_code'];
    $rows = $this->MMenu->getTreeMenuList();
    $rows = $this->MMenu->stringToBoolean($rows);
    $menuList = [];

    foreach ($rows as   $one) {
      if ($one['role'] == $role_code) {

        $tmp = $one;
        $realChildren = [];
        foreach ($one['children'] as   $oneChild) {
          if ($oneChild['role'] == $role_code) {
            $realChildren[] = $oneChild;
          }
        }
        $tmp['children'] = $realChildren;
        $menuList[] = $tmp;
      }
    }

    $ret = [
      'code' => 200,
      'message'  => '获取菜单成功',
      'data' => ['menuList'  => $menuList,]
    ];
    echo json_encode($ret);
  }

  // 分配菜单用,穿梭组件
  public function getTreeMenuList() {

    $rows = $this->MMenu->getTreeMenuList();
    $rows = $this->MMenu->stringToBoolean($rows);



    $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);
    echo json_encode($ret);
  }

  // 分配菜单用,穿梭组件
  public function getRoleMenuList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role = $args['role_code'];
    $this->load->model('MPermission');
    $rows = $this->MMenu->getRoleMenuList($role);
    $rows = $this->MMenu->stringToBoolean($rows);
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);
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


  public function getUserByRole() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role = $args['role_code'];

    $sql = "SELECT nu.staff_name, nu.mobile, nano.dept_name department FROM nanx_user nu 
                left JOIN nanx_user_role_assign ns ON nu.id = ns.userid 
                left JOIN nanx_organization nano ON nu.deptid = nano.id 
                WHERE ns.role_code IN ('$role')";
    $row = $this->db->query($sql)->result_array();
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $row);
    echo json_encode($ret);
  }

  public function addRole() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $data = array(
      'role_code' => $args['role_code'],
      'role_name' => $args['role_name'],
    );
    $this->db->insert('nanx_user_role', $data);
    $this->db->insert_id();
    $ret = array('code' => 200, 'msg' => 'success');
    echo json_encode($ret);
  }





  public function getRoleList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $page = $args['currentPage'];
    $size = $args['pageSize'];
    $page = ($page - 1) * $size;
    $this->load->model('MPermission');
    $part = $this->MPermission->addSqlPart($args);
    $rows = $this->MPermission->getRoleListsIsAddCondition($part, $page, $size);
    $total = $this->MPermission->queryCount($part);
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows, 'total' => $total);
    echo json_encode($ret);
  }


  public function saveMenuPermission() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $this->load->model('MPermission');
    $state = $args['state'];
    $role  = $args['role_code'];
    $menu_id = $args['menu_id_list'];

    if ('insert' == $state) {
      $ret = $this->MPermission->addRoleMenu($role, $menu_id, 'nanx_portal_role_menu_permissions');
      echo json_encode($ret);
      return;
    }
    if ('delete' == $state) {
      $ret = $this->MPermission->deleteMenuOrButton($menu_id, $role, 'nanx_portal_role_menu_permissions', 'menu_id');
      echo json_encode($ret);
      return;
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
