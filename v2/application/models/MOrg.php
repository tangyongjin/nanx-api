<?php

class MOrg  extends CI_Model {
  private $all_boys;
  public function __construct() {
    $this->all_boys = array();
  }

  //所有组织结构某节点下面所有子节点
  public function get_children_from_mysql($id) {
    $sql   = " SELECT * FROM  nanx_org WHERE parent  = '{$id}' ";
    // echo $sql;
    $children = $this->db->query($sql)->result_array();
    //  debug($children);
    foreach ($children as $key => $one_row) {
      $next_id = $one_row['dept_id'];
      $this->all_boys[] = $next_id;
      $children = $this->get_children_from_mysql($next_id);
    }
  }
}
