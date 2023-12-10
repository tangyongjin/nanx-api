<?php
class MDataGrid extends CI_Model {


  function judeSqlType($sql) {
    $sql     = trim($sql);
    $sqltype = 'noselect';
    if (strtolower(substr($sql, 0, 6)) == 'select') {
      $sqltype = 'select';
    }

    if (strtolower(substr($sql, 0, 6)) == 'update') {
      $sqltype = 'update';
    }

    if (strtolower(substr($sql, 0, 6)) == 'delete') {
      $sqltype = 'delete';
    }
    return $sqltype;
  }

  function  getBaseTableByActcode($dataGridCode) {
    $this->db->where('datagrid_code', $dataGridCode);
    $row = $this->db->get_where('nanx_activity')->row_array();
    return $row['base_table'];
  }


  public function callerIncaller($url, $para) {
    $c_and_m = explode('/', $url);
    $c = $c_and_m[0];
    $m = $c_and_m[1];
    $this->load->model($c);
    $result = $this->$c->$m($para);
    return $result;
  }
}
