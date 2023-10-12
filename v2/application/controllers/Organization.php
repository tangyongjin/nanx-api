<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Organization extends MY_Controller {



  public function __construct() {
    parent::__construct();
  }






  public function orgTree() {
    $this->load->model('MOrganization');
    $sql = "select id,  id as  dept_id ,dept_name,parent from  nanx_organization";
    $list = $this->db->query($sql)->result_array();
    $res = $this->MOrganization->list_to_tree('dept_id', 'parent', $list);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
  }






  public function getDeptMembers() {
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $this->load->model('MOrganization');
    $res = $this->MOrganization->getDeptMembers($para['deptid']);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
  }
}
