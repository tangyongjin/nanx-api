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
    $res['data'] = $this->MDataGridCfgAssemble->PipeRunner($para);
    echo json_encode($res, JSON_UNESCAPED_UNICODE);
  }
}
