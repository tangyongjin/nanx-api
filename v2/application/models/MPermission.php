<?php
class MPermission extends CI_Model {
  public function __construct() {
    parent::__construct();
  }



  /*查询所有的表格数据-----根据某个条件*/
  public function querySql($table, $field, $property) {
    $sql = 'select * from ' . $table . ' where ' . $field . '=' . "'$property'";

    return $this->db->query($sql)->result_array();
  }



  public function queryCountMethod($table) {
    $sql = 'select * from ' . $table;
    $rows = $this->db->query($sql)->result_array();
    $total = count($rows);

    return $total;
  }

  public function getAllMenuListMethod($args) {
    $size = $args['size'];
    $page = ($args['currentPage'] - 1) * $size;
    $part = '';
    $prefix_sql = "select menu_level,id 'key',menu,type,text,icon,router,parent_id,is_leaf 'isLeaf',menu_level,datagrid_code,process_key FROM boss_portal_menu_list WHERE 1=1 ";
    if (true == array_key_exists('menu', $args)) {
      $menu = $args['menu'];
      $part = " and menu like '%$menu%'";
    }
    if (true == array_key_exists('text', $args)) {
      $text = $args['text'];
      $part = " and text like '%$text%'";
    }
    $suffix_sql = " limit $page,$size";
    $sql = $prefix_sql . $part . $suffix_sql;

    return $this->db->query($sql)->result_array();
  }






  public function getFirstMenuListFunction($res) {
    $menu_id = array_to_strings(array_column($res, 'menu_id'), '');
    $sql = "select id 'key',menu,type,text title,icon,router,parent_id,is_leaf 'isLeaf' from boss_portal_menu_list where id in ($menu_id) and is_leaf='false'";

    return $this->db->query($sql)->result_array();
  }


  //查询角色列表   getRoleLists
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

  public function getRoleButtonResult($role_code) {
    $sql = "select bb.id 'key',bb.button,bb.isshow,bb.type,bb.icon,bb.text,bp.role,bb.is_leaf 'isLeaf'
                FROM boss_portal_menu_button bb
                JOIN boss_portal_role_button_permissions bp ON bb.id = bp.button_id where bp.role='$role_code'";

    return $this->db->query($sql)->result_array();
  }

  //拼接父子关系 parent_text--简单拼接 --菜单
  public function getParentText($res) {
    for ($i = 0; $i < count($res); ++$i) {
      $id = $res[$i]['parent_id'];
      if ('' != $id || null != $id) {
        $sql = "select text parent_text from boss_portal_menu_list WHERE id = '$id' ORDER BY text";
        $row = $this->db->query($sql)->row_array();
        $res[$i]['parent_text'] = $row['parent_text'];
        continue;
      }
      $res[$i]['parent_text'] = '';
    }

    return $res;
  }




  public function getMenuArrayByRole($role) {
    $sql = "select wl.menu_level,wl.id 'key',wl.menu,wl.type,wl.text,wl.icon,wl.router,wl.is_leaf 'isLeaf',wl.parent_id,wp.role 
                from boss_portal_role_menu_permissions wp 
                join boss_portal_menu_list wl on wp.menu_id=wl.id where wp.role='$role'";
    $res = $this->db->query($sql)->result_array();
    $res = $this->getParentText($res);

    return $res;
  }


  public function getTreeMenuList() {
    $sql = "select 
                wl.menu_level,wl.datagrid_code,wl.is_association_process,
                wl.process_key, 
                wl.badge,wl.badge_key,wl.id 'key',
                wl.menu,wl.type,wl.text as title,wl.icon,
                wl.router,wl.is_leaf 'isLeaf',
                wl.parent_id
                  from 
             boss_portal_menu_list wl 
           where  parent_id='' ORDER BY wl.id";
    $res = $this->db->query($sql)->result_array();

    for ($i = 0; $i < count($res); ++$i) {
      $id = $res[$i]['key'];
      $sql = "select wl.menu_level,wl.datagrid_code,wl.is_association_process,
            wl.process_key, wl.badge,wl.badge_key,
            wl.id 'key',wl.menu,wl.type,wl.text as title,wl.icon,wl.router,
            wl.is_leaf 'isLeaf',wl.parent_id 
             from  boss_portal_menu_list wl where  wl.parent_id='$id' ORDER BY wl.id";
      $row = $this->db->query($sql)->result_array();

      for ($j = 0; $j < count($row); ++$j) {


        $three = $row[$j]['key'];
        $three_sql = "select wl.menu_level,wl.datagrid_code,wl.is_association_process,wl.process_key,
         wl.badge,wl.badge_key,wl.id 'key',
                wl.menu,wl.type,wl.text as title,wl.icon,wl.router,wl.is_leaf 'isLeaf',wl.parent_id  
                 from  boss_portal_menu_list wl  where   wl.parent_id='$three' ORDER BY wl.id";
        $three_row = $this->db->query($three_sql)->result_array();

        if (count($three_row) >= 0) {
          $row[$j]['children'] = $three_row;
        }
      }
      if (count($row) >= 0) {
        $res[$i]['children'] = $row;
      }
    }
    return $res;
  }




  /*查询所有菜单-------父子结构*/
  public function getAllMenuList() {
    $sql = 'select * from boss_portal_menu_list  where parent_id is null';
    $res = $this->db->query($sql)->result_array();
    for ($i = 0; $i < count($res); ++$i) {
      $id = $res[$i]['id'];
      $row = $this->querySql('boss_portal_menu_list', 'parent_id', $id);
      if (count($row) >= 0) {
        $res[$i]['children'] = $row;
      }
    }

    return $res;
  }








  //新增菜单
  public function addMenu($role, $menu_ids) {
    $menu_id = $menu_ids[0];
    $data = array(
      'role' => $role,
      'menu_id' =>  $menu_id
    );
    $this->db->insert('boss_portal_role_menu_permissions', $data);
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



  public function insertMenu($args) {
    $data = array(
      'menu' => $args['menu'],
      'router' => $args['router'],
    );
    if (array_key_exists('type', $args)) {
      $data['type'] = $args['type'];
    }
    if (array_key_exists('process_key', $args)) {
      $data['process_key'] = $args['process_key'];
    }
    if (array_key_exists('datagrid_code', $args)) {
      $data['datagrid_code'] = $args['datagrid_code'];
    }
    if (array_key_exists('icon', $args)) {
      $data['icon'] = $args['icon'];
    }
    if (array_key_exists('text', $args)) {
      $data['text'] = $args['text'];
    }
    if (array_key_exists('undefined', $args)) {
      $data['type'] = $args['undefined'];
    }
    if (true == $args['isFirstMenu']) {

      $data['menu_level'] = 1;
      $data['parent_id'] = '';
    }
    if (false == $args['isFirstMenu']) {

      $this->db->where('id', $args['parent_id']);
      $row = $this->db->get('boss_portal_menu_list')->row_array();

      $data['menu_level'] = $row['menu_level'] + 1;
      $data['parent_id'] = $args['parent_id'];
    }

    return $data;
  }








  public function stringBooleanToBoolean($rows) {
    //第一级
    for ($i = 0; $i < count($rows); $i++) {
      $children = $rows[$i]['children'];
      if ($rows[$i]['isLeaf'] == 'false') {
        $rows[$i]['isLeaf'] = false;
      }
      if ($rows[$i]['isLeaf'] == 'true') {
        $rows[$i]['isLeaf'] = true;
      }
      //第二级
      for ($j = 0; $j < count($children); $j++) {
        if ($children[$j]['isLeaf'] == 'false') {
          $rows[$i]['children'][$j]['isLeaf'] = false;
        }
        if ($children[$j]['isLeaf'] == 'true') {
          $rows[$i]['children'][$j]['isLeaf'] = true;
        }
        //第三级
        $grandchildren = $children[$j]['children'];
        for ($x = 0; $x < count($grandchildren); $x++) {
          if ($grandchildren[$x]['isLeaf'] == 'false') {
            $rows[$i]['children'][$j]['children'][$x]['isLeaf'] = false;
          }
          if ($grandchildren[$x]['isLeaf'] == 'true') {
            $rows[$i]['children'][$j]['children'][$x]['isLeaf'] = true;
          }
        }
      }
    }
    return $rows;
  }
}
