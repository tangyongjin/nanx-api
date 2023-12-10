<?php
class MK8s extends CI_Model {
  public function __construct() {
    parent::__construct();
  }



  public function Gontainers($para) {

    // debug($para);
    // $pageSize = $para['pageSize'];
    // $currentPage = $para['currentPage'];

    $rows = [];
    $rows[] = ['id' => 1, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 2, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 3, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 4, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 5, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 6, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 7, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 8, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 9, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 10, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 11, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 12, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 13, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 14, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 15, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 16, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 17, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 18, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 19, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 20, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 21, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 22, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 23, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 24, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 25, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 26, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 27, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 28, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 29, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 30, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 31, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 32, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 33, 'name' => 'nginx', 'sex' => 'nginx'];
    $rows[] = ['id' => 34, 'name' => 'nginx', 'sex' => 'nginx'];

    return $rows;
    // $total = count($rows);
    // $currentPageRows = paginateRows($rows, $pageSize, $currentPage);
    // return ['total'=>$total,$currentPageRows;
  }
}
