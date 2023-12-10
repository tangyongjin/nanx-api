<?php

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;


if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

class DataGridCfg extends MY_Controller {

  public function __construct() {
    parent::__construct();
    header('Access-Control-Allow-Origin: * ');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With,Content-Type, Accept,authorization');
    header('Access-Control-Allow-Credentials', true);
    if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
      exit();
    }
  }



  public function fetchDataGridCfg() {

    $para = (array) json_decode(file_get_contents("php://input"));
    $res = [];
    $res['code'] = 200;

    $this->db->where('datagrid_code', $para['DataGridCode']);
    $tmp = $this->db->get('nanx_activity')->row_array();
    if ($tmp['datagrid_type'] == 'table') {
      $res['data'] = $this->MTableGridCfgAssemble->PipeRunner($para);
      echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }

    if ($tmp['datagrid_type'] == 'service') {
      $res['data'] = $this->MServiceGridCfgAssemble->PipeRunner($para);
      echo json_encode($res, JSON_UNESCAPED_UNICODE);
    }
  }
}
