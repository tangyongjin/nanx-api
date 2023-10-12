<?php

if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

class Permission extends MY_Controller {

  public function __construct() {
    parent::__construct();
  }

  public function getMenuIdAssosiedUsers() {
    $args = (array) json_decode(file_get_contents("php://input"));
    $menuId = $args['menu_id'];

    $currentPage  = $args['currentPage'];
    $size         = $args['pagesize'];
    $page = ($currentPage - 1) * $size;

    $sql = "SELECT nu.id,nr.role_code, nr.role_name, nu.staff_name, nu.head_portrait,nao.dept_name
                FROM boss_portal_role_menu_permissions bp 
                LEFT JOIN nanx_user_role nr ON bp.role = nr.role_code 
                LEFT JOIN nanx_user_role_assign na ON na.role_code = nr.role_code 
                LEFT JOIN nanx_user nu ON nu.id = na.userid 
                LEFT JOIN nanx_organization nao on  nu.deptid=nao.id
                WHERE bp.menu_id = '$menuId' limit $page,$size";
    $res = $this->db->query($sql)->result_array();
    $data = [];
    foreach ($res as $key => $value) {
      if ($value['head_portrait'] == "" || $value['head_portrait'] == NULL) {
        $value['head_portrait'] = 'http://' . $_SERVER['HTTP_HOST'] . "/upload/common_avatar.png";
      } else {
        $value['head_portrait'] = 'http://' . $_SERVER['HTTP_HOST'] . $value['head_portrait'];
      }
      $data[] = $value;
    }
    $ret = array("code" => 200, 'data' => $data);

    echo json_encode($ret);
  }



  public function getRoleByMenuId() {
    $args = (array) json_decode(file_get_contents("php://input"));
    $menuId = $args['menu_id'];
    $currentPage  = $args['currentPage'];
    $size         = $args['pagesize'];
    $page = ($currentPage - 1) * $size;


    $sql = "SELECT nr.id,nr.role_code,nr.role_name from boss_portal_role_menu_permissions bp 
                LEFT JOIN nanx_user_role nr on bp.role=nr.role_code  
                where bp.menu_id='$menuId' limit $page,$size";
    $res = $this->db->query($sql)->result_array();

    $ret = array("code" => 200, 'data' => $res);

    echo json_encode($ret);
  }


  public function getUserByRole() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role = $args['role_code'];

    $sql = "SELECT nu.staff_name, nu.mobile, nano.dept_name department FROM nanx_user nu 
                JOIN nanx_user_role_assign ns ON nu.id = ns.userid 
                JOIN nanx_organization nano ON nu.deptid = nano.id 
                WHERE ns.role_code IN ('$role')";
    $row = $this->db->query($sql)->result_array();

    $ret = array('code' => 200, 'msg' => 'success', 'data' => $row);
    echo json_encode($ret);
  }


  public function getFirstMenuList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role = $args['role_code'];
    $this->load->model('MPermission');
    $res = $this->MPermission->querySql('boss_portal_role_menu_permissions', 'role', $role);
    $rows = $this->MPermission->getFirstMenuListFunction($res);
    $rows = $this->MMenu->stringToBoolean($rows);
    if (count($rows) >= 0) {
      $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);
    }
    if (count($rows) < 0) {
      $ret = array('code' => 400, 'msg' => 'error', 'data' => array());
    }
    echo json_encode($ret);
  }

  public function getSecondMenuList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role = $args['role_code'];
    $key = $args['key'];
    $this->load->model('MPermission');
    $sql = "SELECT bl.id 'key',bl.menu,bl.type,bl.text title,bl.icon,bl.router,bl.parent_id,bl.is_leaf 'isLeaf'
                FROM boss_portal_menu_list bl join boss_portal_role_menu_permissions bp
                on bl.id=bp.menu_id WHERE bl.parent_id = '$key' and bp.role='$role'
                AND bl.is_leaf = 'true'";
    $rows = $this->db->query($sql)->result_array();
    $rows = $this->MMenu->stringToBoolean($rows);
    if (count($rows) >= 0) {
      $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);
    }
    if (count($rows) < 0) {
      $ret = array('code' => 400, 'msg' => 'error', 'data' => array());
    }
    echo json_encode($ret);
  }



  public function getRoleButtonList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role_code = $args['role_code'];
    $this->load->model('MPermission');
    $rows = $this->MPermission->getRoleButtonResult($role_code);
    $rows = $this->MMenu->stringToBoolean($rows);
    if (count($rows) >= 0) {
      $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);
    }
    if (count($rows) < 0) {
      $ret = array('code' => 400, 'msg' => 'error', 'data' => array());
    }
    echo json_encode($ret);
  }

  public function addRole() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $data = array(
      'role_code' => $args['role_code'],
      'role_name' => $args['role_name'],
    );
    $this->db->insert('nanx_user_role', $data);
    $inset_res = $this->db->insert_id();
    if (count($inset_res) >= 0) {
      $ret = array('code' => 200, 'msg' => 'success');
    }
    if (count($inset_res) < 0) {
      $ret = array('code' => 400, 'msg' => 'error');
    }
    echo json_encode($ret);
  }

  public function deleteRole() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $id = $args['id'];
    $this->db->delete('nanx_user_role', array('id' => $id));
    $err = $this->db->error();
    if (0 == $err['code']) {
      $ret = array('code' => 200, 'msg' => '操作成功');
    } else {
      $ret = array('code' => 400, 'msg' => $err['message']);
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }

  public function updateRole() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $id = $args['key'];
    $data = array(
      'role_code' => $args['role_code'],
      'role_name' => $args['role_name'],
    );
    $this->db->where('id', $id);
    $this->db->update('nanx_user_role', $data);
    $err = $this->db->error();
    if (0 == $err['code']) {
      $ret = array('code' => 200, 'msg' => 'success');
    } else {
      $ret = array('code' => 400, 'msg' => 'error');
    }
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
    if (count($rows) >= 0) {
      $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows, 'total' => $total);
    }
    if (count($rows) < 0) {
      $ret = array('code' => 400, 'msg' => 'error', 'data' => array(), 'total' => 0);
    }
    echo json_encode($ret);
  }


  public function addMenu() {
    $args = (array) json_decode(file_get_contents('php://input'));

    $this->load->model('MPermission');
    $data = $this->MPermission->insertMenu($args);
    $this->db->insert('boss_portal_menu_list', $data);

    $err = $this->db->error();
    if (0 == $err['code']) {
      $ret = array('code' => 200, 'msg' => 'success');
    } else {
      $ret = array('code' => 400, 'msg' => $err['message']);
    }
    echo json_encode($ret);
  }




  public function deleteMenu() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $id = $args['id'];
    $this->load->model('MPermission');
    $this->db->where('id', $id);
    $res = $this->db->get('boss_portal_menu_list')->row_array();


    if ('false' == $res['is_leaf']) {
      $res = $this->MPermission->querySql('boss_portal_menu_list', 'parent_id', $id);
      $chidldren_id = array_to_string(array_column($res, 'id'));
      $sql = "delete from boss_portal_menu_list where id in ('$chidldren_id')";
      $this->db->query($sql);
    }

    $this->db->delete('boss_portal_menu_list', array('id' => $id));
    $err = $this->db->error();
    if (0 == $err['code']) {
      $ret = array('code' => 200, 'msg' => '操作成功');
    } else {
      $ret = array('code' => 400, 'msg' => $err['message']);
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }



  public function updateMenu() {
    $this->load->model('MPermission');
    $args = (array) json_decode(file_get_contents('php://input'));

    $id = $args['id'];
    $data = $this->MPermission->insertMenu($args);

    $this->db->where('id', $id);
    $this->db->update('boss_portal_menu_list', $data);
    $err = $this->db->error();

    if (0 == $err['code']) {
      $ret = array('code' => 200, 'msg' => 'success');
    } else {
      $ret = array('code' => 400, 'msg' => $err['message']);
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function getAllMenuList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $this->load->model('MPermission');

    $rows = $this->MPermission->getAllMenuListMethod($args);
    $rows = $this->MPermission->getParentText($rows);
    $rows = $this->MMenu->stringToBoolean($rows);

    $total = $this->MPermission->queryCountMethod('boss_portal_menu_list');
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows, 'total' => $total);
    echo json_encode($ret);
  }


  public function saveMenuPermissions() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $this->load->model('MPermission');

    $state = $args['state'];
    $role  = $args['role_code'];
    $menu_id = $args['menu_id_list'];

    if ('insert' == $state) {
      $ret = $this->MPermission->addMenu($role, $menu_id, 'boss_portal_role_menu_permissions');
      echo json_encode($ret);
      return;
    }
    if ('delete' == $state) {
      $ret = $this->MPermission->deleteMenuOrButton($menu_id, $role, 'boss_portal_role_menu_permissions', 'menu_id');
      echo json_encode($ret);
      return;
    }
    $ret = array('code' => 400, 'msg' => 'error');
    echo json_encode($ret);
  }





  // 登录用菜单列表

  public function getMenuList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role_code = $args['role_code'];
    $menuList = $this->MMenu->getMenuListByRolecode($role_code);
    if (count($menuList) >= 0) {
      $ret = array(
        'code' => 200,
        'message'  => '获取菜单成功',
        'data' => array(
          'menuList'  => $menuList,
          'rights'    => [],
        ),
      );
    }
    echo json_encode($ret);
  }



  public function getTreeMenuList() {
    $this->load->model('MPermission');
    $res = $this->MPermission->getTreeMenuList();
    $res =  $this->MPermission->stringBooleanToBoolean($res);
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $res);
    echo json_encode($ret);
  }




  // 分配菜单用
  public function getRoleMenuList() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $role = $args['role_code'];
    $this->load->model('MPermission');
    $rows = $this->MPermission->getMenuArrayByRole($role);
    $rows = $this->MMenu->stringToBoolean($rows);
    $ret = array('code' => 200, 'msg' => 'success', 'data' => $rows);

    echo json_encode($ret);
  }
}
