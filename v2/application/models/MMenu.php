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

    for ($i = 0; $i < count($res); ++$i) {
      $id = $res[$i]['key'];
      $sql = "select wl.menu_level,wl.datagrid_code,wl.is_association_process,
            wl.process_key, wl.badge,wl.badge_key,
            wl.id 'key',wl.menu,wl.type,wl.text as title,wl.icon,wl.router,
            if(wl.is_leaf='true',true,false ) as  'isLeaf',
            wl.parent_id 
            from  boss_portal_menu_list wl where  wl.parent_id='$id' ORDER BY wl.id";
      $row = $this->db->query($sql)->result_array();

      for ($j = 0; $j < count($row); ++$j) {
        $three = $row[$j]['key'];
        $three_sql = "select wl.menu_level,wl.datagrid_code,wl.is_association_process,
                wl.process_key,
                wl.badge,wl.badge_key,wl.id 'key',
                wl.menu,wl.type,wl.text as title,wl.icon,
                if(wl.is_leaf='true',true,false ) as  'isLeaf',
                wl.parent_id  
                from  boss_portal_menu_list wl  
                where   wl.parent_id='$three' ORDER BY wl.id";
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
    //第一级
    for ($i = 0; $i < count($rows); $i++) {
      $children = $rows[$i]['children'];
      if ($rows[$i]['isLeaf'] == '0') {
        $rows[$i]['isLeaf'] = false;
      }
      if ($rows[$i]['isLeaf'] == '1') {
        $rows[$i]['isLeaf'] = true;
      }
      //第二级
      for ($j = 0; $j < count($children); $j++) {
        if ($children[$j]['isLeaf'] == '0') {
          $rows[$i]['children'][$j]['isLeaf'] = false;
        }
        if ($children[$j]['isLeaf'] == '1') {
          $rows[$i]['children'][$j]['isLeaf'] = true;
        }
        //第三级
        $grandchildren = $children[$j]['children'];
        for ($x = 0; $x < count($grandchildren); $x++) {
          if ($grandchildren[$x]['isLeaf'] == '0') {
            $rows[$i]['children'][$j]['children'][$x]['isLeaf'] = false;
          }
          if ($grandchildren[$x]['isLeaf'] == '1') {
            $rows[$i]['children'][$j]['children'][$x]['isLeaf'] = true;
          }
        }
      }
    }
    return $rows;
  }

  public function getParentText($res) {
    for ($i = 0; $i < count($res); ++$i) {
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
