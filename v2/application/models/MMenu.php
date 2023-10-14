<?php
class MMenu extends CI_Model {
  public function __construct() {
    parent::__construct();
  }

  /*查询某个角色的菜单-------父子结构*/
  public function getMenuTreeByRoleCode($role) {
    $sql = " select menu.menu, menu.menu_level,menu.datagrid_code, menu.is_association_process,
    menu.process_key,menu.badge,menu.badge_key,menu.id 'key',
    menu.type,menu.text,menu.icon,menu.router,
    if(menu.is_leaf='true',true,false ) as  'isLeaf',
    menu.parent_id,menu_role.role ,
    menu.listorder
    from boss_portal_role_menu_permissions menu_role 
    join boss_portal_menu_list menu 
    on menu_role.menu_id=menu.id 
    where menu_role.role='$role' 
    and parent_id is NULL 
    ORDER BY menu.listorder";


    $res = $this->db->query($sql)->result_array();

    for ($i = 0; $i < count($res); ++$i) {
      $id = $res[$i]['key'];
      $sql = " select menu.menu, menu.menu_level,menu.datagrid_code, menu.is_association_process,
      menu.process_key, menu.badge,menu.badge_key,menu.id 'key',
      menu.type,menu.text, menu.icon,menu.router,
      if(menu.is_leaf='true',true,false ) as  'isLeaf',
      menu.parent_id,menu_role.role
      from boss_portal_role_menu_permissions menu_role 
      join boss_portal_menu_list menu 
      on menu_role.menu_id=menu.id 
      where menu_role.role='$role' 
      and menu.parent_id='$id' 
      ORDER by  menu.listorder";



      $row = $this->db->query($sql)->result_array();
      for ($j = 0; $j < count($row); ++$j) {
        $three = $row[$j]['key'];
        $three_sql = "select wl.menu,wl.menu_level,wl.datagrid_code,
            wl.is_association_process,wl.process_key, wl.badge,wl.badge_key,wl.id 'key',
            wl.type,wl.text,wl.icon,wl.router,
            if(wl.is_leaf='true',true,false ) as  'isLeaf',
            wl.parent_id,wp.role
            from boss_portal_role_menu_permissions wp 
            join boss_portal_menu_list wl on wp.menu_id=wl.id 
            where wp.role='$role' and wl.parent_id='$three' 
            ORDER BY wl.listorder";

        $three_row = $this->db->query($three_sql)->result_array();
        $row[$j]['children'] = $three_row;
      }
      $res[$i]['children'] = $row;
    }
    return $res;
  }

  /**穿梭菜单用 */
  public function getRoleMenuList($role) {
    $sql = "select wl.menu_level,wl.id 'key',wl.menu,wl.type,wl.text,wl.icon,wl.router,
                if(wl.is_leaf='true',true,false ) as  'isLeaf',
                wl.parent_id,wp.role 
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
    $sql = "select 
                wl.menu_level,wl.datagrid_code,wl.is_association_process,
                wl.process_key, 
                wl.badge,wl.badge_key,wl.id 'key',
                wl.menu,wl.type,wl.text as title,wl.icon,
                wl.router,
                if(wl.is_leaf='true',true,false ) as  'isLeaf',
                wl.parent_id
                from 
                boss_portal_menu_list wl 
                where  parent_id is NULL ORDER BY wl.id";
    $res = $this->db->query($sql)->result_array();
    $len1 = count($res);
    for ($i = 0; $i < $len1; ++$i) {
      $id = $res[$i]['key'];
      $sql = "select wl.menu_level,wl.datagrid_code,wl.is_association_process,
            wl.process_key, wl.badge,wl.badge_key,
            wl.id 'key',wl.menu,wl.type,wl.text as title,wl.icon,wl.router,
            if(wl.is_leaf='true',true,false ) as  'isLeaf',
            wl.parent_id 
            from  boss_portal_menu_list wl where  wl.parent_id='$id' ORDER BY wl.id";
      $rows = $this->db->query($sql)->result_array();
      $len2 = count($rows);
      for ($j = 0; $j < $len2; ++$j) {
        $three = $rows[$j]['key'];
        $three_sql = "select wl.menu_level,wl.datagrid_code,wl.is_association_process,
                wl.process_key,
                wl.badge,wl.badge_key,wl.id 'key',
                wl.menu,wl.type,wl.text as title,wl.icon,
                if(wl.is_leaf='true',true,false ) as  'isLeaf',
                wl.parent_id  
                from  boss_portal_menu_list wl  
                where   wl.parent_id='$three' ORDER BY wl.id";
        $three_row = $this->db->query($three_sql)->result_array();
        $rows[$j]['children'] = $three_row;
      }
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
        $grandchildren = $children[$j]['children'];
        $len3 = count($grandchildren);
        for ($x = 0; $x < $len3; $x++) {
          $grandchildren[$x]['isLeaf'] == '0' ? $rows[$i]['children'][$j]['children'][$x]['isLeaf'] = false : $rows[$i]['children'][$j]['children'][$x]['isLeaf'] = true;
        }
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
