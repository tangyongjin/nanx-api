<?php

class MCurd extends CI_Model {
  public function __construct() {
    $this->load->model('MDatafactory');
    parent::__construct();
  }

  public function getBasetable($datagrid_code) {
    $this->db->where('datagrid_code', $datagrid_code);
    $row = $this->db->get('nanx_activity')->row_array();

    return $row['base_table'];
  }




  public function getdata_table_type($currentUser, $para) {

    if (array_key_exists('query_cfg', $para)) {
      $query_cfg = (array) $para['query_cfg'];
      if (empty($query_cfg)) {
        $query_cfg = null;
      } else {
        $query_cfg['actcode'] = $para['DataGridCode'];
        $query_cfg['lines'] =  (array) $query_cfg['lines'];
      }
    } else {
      $query_cfg = null;
    }



    $table = $para['table'];
    $id_order = (isset($para['id_order'])) ? $para['id_order'] : 'desc';  //缺省为倒序
    $pageSize    = $para['pageSize'];
    $start = ($para['currentPage'] - 1) * $pageSize;
    $result = $this->MDatafactory->getDatabyBasetable($para['DataGridCode'], $table, $id_order, $query_cfg, $start, $pageSize, $currentUser);
    return $result;
  }



  public function getActivityData($currentUser, $para) {

    if (array_key_exists('code', $para)) {
      $code = $para['code'];
      $this->db->where('datagrid_code', $code);
      $query = $this->db->get('nanx_activity');
      $cfg = $query->first_row('array');

      $datagrid_type = $cfg['datagrid_type']; //table
    } else {
      $datagrid_type = 'table';
    }

    if ($datagrid_type  == "service") {
      return $this->getdata_service($para);
    } else {
      if ($datagrid_type == 'table') {
        return $this->getdata_table_type($currentUser, $para);
      }


      if (('tree' == $datagrid_type) || ('NANX_TBL_DATA' == $para['code'])) {
        return $this->getdata_table_type($currentUser, $para);
      }
    }

    if ('sql' == $datagrid_type) {
      return $this->getdata_sql($para);
    }
  }






  public function getdata_service($p) {
    $table = $p['table'];
    if (isset($_GET['start'])) {
      $start = $_GET['start'];
      $limit = $_GET['limit'];
      $this->db->limit($limit, $start);
    }

    if (array_key_exists('id_order', $p)) {
      $idorder = $p['id_order'];
    } else {
      $idorder = 'asc';
    }

    $this->db->order_by('id', $idorder);

    if (array_key_exists('cols_selected', $p)) {
      $cols_selected = $p['cols_selected'];
      $this->db->select($cols_selected);
    }
    if (array_key_exists('filter_field', $p) && array_key_exists('filter_value', $p)) {
      if (strlen($p['filter_field']) > 1) {
        $this->db->where($p['filter_field'], $p['filter_value']);
      }
    }

    $rows = $this->db->get($table)->result_array();
    $sql = $this->db->last_query();
    $total = $this->db->count_all($table);

    $result['rows'] = $rows;
    $result['total'] = $total;
    $result['table'] = $table;
    $result['sql'] = $sql;

    return $result;
  }

  public function getdata_sql($p) {
    $activty_code = $p['code'];
    $this->db->where('datagrid_code', $activty_code);
    $query = $this->db->get('nanx_activity');
    $cfg = $query->first_row('array');
    $sql = $cfg['sql'];

    if (isset($p['para_json'])) {
      $sql_fixed = strMarcoReplace($sql, $p['para_json']);
    } else {
      $sql_fixed = $sql;
    }

    $result = $this->MDatafactory->getDatabySql($sql_fixed);
    if ('NANX_TB_LAYOUT' == $activty_code) {
      $mixed = getLayoutFields($result['rows']);
      $rows = $mixed['data'];
      $result['rows'] = $rows;
      $result['total'] = count($rows);
      $result['sql'] = $sql_fixed;
    }

    return $result;
  }
}
