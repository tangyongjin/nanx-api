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

  function  getBaseTableByActcode($actcode) {
    $this->db->where('datagrid_code', $actcode);
    $row = $this->db->get_where('nanx_activity')->row_array();
    return $row['base_table'];
  }
}
