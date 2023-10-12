<?php
class MMenu extends CI_Model {
  public function __construct() {
    parent::__construct();
  }

  // 登录用菜单列表
  public function getMenuListByRolecode($rolecode) {
    $rows = $this->getMenuTreeByRoleCode($rolecode);
    $menuList = $this->stringToBoolean($rows);
    return $menuList;
  }

  /*查询某个角色的菜单-------父子结构*/
  public function getMenuTreeByRoleCode($role) {
    $sql = "select wl.menu_level,wl.datagrid_code, wl.is_association_process,
    wl.process_key,wl.badge,wl.badge_key,wl.id 'key',
    wl.menu,wl.type,wl.text,wl.icon,wl.router,wl.is_leaf 'isLeaf',wl.parent_id,wp.role 
            from boss_portal_role_menu_permissions wp 
            join boss_portal_menu_list wl 
            on wp.menu_id=wl.id where wp.role='$role' and parent_id='' ORDER BY wl.listorder";

    $res = $this->db->query($sql)->result_array();

    for ($i = 0; $i < count($res); ++$i) {
      $id = $res[$i]['key'];
      $sql = "select wl.menu_level,wl.datagrid_code, wl.is_association_process,wl.process_key,
      wl.badge,wl.badge_key,wl.id 'key',wl.menu,wl.type,wl.text,
      wl.icon,wl.router,wl.is_leaf 'isLeaf',wl.parent_id,wp.role
      from boss_portal_role_menu_permissions wp join boss_portal_menu_list wl on wp.menu_id=wl.id 
      where wp.role='$role' and wl.parent_id='$id' ORDER
            by  wl.listorder,wl.id";



      $row = $this->db->query($sql)->result_array();

      for ($j = 0; $j < count($row); ++$j) {
        $three = $row[$j]['key'];
        $three_sql = "select wl.menu_level,wl.datagrid_code,
        wl.is_association_process,wl.process_key, wl.badge,wl.badge_key,wl.id 'key',
        wl.menu,wl.type,wl.text,wl.icon,wl.router,wl.is_leaf 'isLeaf',wl.parent_id,wp.role
        from boss_portal_role_menu_permissions wp join boss_portal_menu_list wl on wp.menu_id=wl.id where wp.role='$role' and wl.parent_id='$three' ORDER BY wl.id";

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

  public function stringToBoolean($rows) {
    for ($i = 0; $i < count($rows); ++$i) {
      if ('false' == $rows[$i]['isLeaf']) {
        $rows[$i]['isLeaf'] = false;
      }
      if ('true' == $rows[$i]['isLeaf']) {
        $rows[$i]['isLeaf'] = true;
      }
    }
    return $rows;
  }
}
