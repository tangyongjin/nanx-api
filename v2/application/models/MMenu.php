<?php
class MMenu extends CI_Model {
  public function __construct() {
    parent::__construct();
  }


  /**穿梭菜单用 ,获取已经分配的 菜单key */
  public function getRoleMenuList($role) {
    $sql = "select wl.id 'key', if(wl.is_leaf='true',true,false ) as  'isLeaf',wl.parent_id 
                from boss_portal_role_menu_permissions wp
                join boss_portal_menu_list wl on wp.menu_id=wl.id 
                where wp.role='$role'";
    $res = $this->db->query($sql)->result_array();
    foreach ($res as &$value) {
      $value['children'] = [];
    }
    $res = $this->getParentText($res);
    return $res;
  }

  public function getTreeMenuList() {
    $sql = "select   menu_role.role, 
            menu.menu_level,menu.datagrid_code,
            menu.id 'key',
            menu.menu,menu.type,menu.text ,menu.text as title,menu.icon,
            menu.router,
            if(menu.is_leaf='true',true,false ) as  'isLeaf',
            menu.parent_id
            from 
            boss_portal_menu_list menu
            left join  boss_portal_role_menu_permissions menu_role
            on menu_id=menu.id
            where  parent_id is NULL ORDER BY menu.listorder";

    $res = $this->db->query($sql)->result_array();
    $len1 = count($res);
    for ($i = 0; $i < $len1; ++$i) {
      $id = $res[$i]['key'];
      $sql = "select  menu_role.role,  
              menu.menu_level,menu.datagrid_code,
              menu.id 'key',
              menu.menu,menu.type,menu.text ,menu.text as title,menu.icon,
              menu.router,
              if(menu.is_leaf='true',true,false ) as  'isLeaf',
              menu.parent_id 
              from  boss_portal_menu_list menu
              left join  boss_portal_role_menu_permissions menu_role
              on menu_id=menu.id
              where  menu.parent_id='$id' ORDER BY menu.listorder";
      $rows = $this->db->query($sql)->result_array();
      $res[$i]['children'] = $rows;
    }
    return $res;
  }





  public function stringToBoolean($rows) {
    $len = count($rows);
    for ($i = 0; $i < $len; $i++) {
      $children = $rows[$i]['children'];
      $rows[$i]['isLeaf'] == '0' ? $rows[$i]['isLeaf'] = false : $rows[$i]['isLeaf'] = true;
      $len2 = count($children);
      for ($j = 0; $j < $len2; $j++) {
        $children[$j]['isLeaf'] == '0' ? $rows[$i]['children'][$j]['isLeaf'] = false : $rows[$i]['children'][$j]['isLeaf'] = true;
      }
    }
    return $rows;
  }

  public function getParentText($res) {
    $len = count($res);
    for ($i = 0; $i < $len; ++$i) {

      $id = $res[$i]['parent_id'];
      if ('' != $id || null != $id) {
        $sql = "select text as  parent_text from boss_portal_menu_list WHERE id = '$id' ";
        $row = $this->db->query($sql)->row_array();
        $res[$i]['parent_text'] = $row['parent_text'];
        continue;
      }
      $res[$i]['parent_text'] = '';
    }
    return $res;
  }
}
