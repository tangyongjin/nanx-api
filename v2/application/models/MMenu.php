<?php
class MMenu extends CI_Model {
  public function __construct() {
    parent::__construct();
  }



  public function getTreeMenuList() {

    $commonsql =   "select   
    menu ,
    menu_level,datagrid_code,
    id 'key',
    text as title,
    icon,
    router,
    if(is_leaf='true',true,false ) as  'isLeaf',
    parent_id
    from 
    nanx_portal_menu_list menu
    where  ";

    $sql =  $commonsql . "  parent_id is NULL ORDER BY listorder";
    $res = $this->db->query($sql)->result_array();
    $len_level_1 = count($res);
    for ($i = 0; $i < $len_level_1; ++$i) {
      $id = $res[$i]['key'];
      $sql =  $commonsql . " parent_id='$id' ORDER BY listorder";
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
}
