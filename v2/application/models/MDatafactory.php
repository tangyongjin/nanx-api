<?php
class MDatafactory extends CI_Model {
  function getSqlByActCode($actcode, $basetable, $id_order) {

    $this->db->where(['actcode' => $actcode]);
    $this->db->select('base_table,field_e,codetable_category_value,combo_table,list_field,value_field,filter_field,group_id,level');
    $combo_fields = $this->db->get('nanx_biz_column_trigger_group')->result_array();


    $transed_fields = [];
    $joins         = [];
    $ghosts        = [];

    $field_list_str = '';
    $join_str       = '';
    $ghosts_str     = '';

    // 对每个列,查找combo_fields,看是否需要替换为left join形式.
    $this->load->model('MFieldcfg');
    $base_fields  = $this->db->list_fields($basetable);

    foreach ($base_fields as $table_field) {
      $found           = $this->MFieldcfg->getLeftJoinObject($basetable, $table_field, $combo_fields);
      $transed_fields[] = $found['transed'];
      $ghosts[]        = $found['ghost'];
      $joins[]         = $found['join'];
    }

    foreach ($transed_fields as $field) {
      $field_list_str .= $field . ",";
    }

    foreach ($ghosts as $ghost) {
      if (strlen($ghost) > 10) {
        $ghosts_str .= $ghost . ",";
      }
    }

    $live_and_ghost = $field_list_str . $ghosts_str;
    $live_and_ghost = substr($live_and_ghost, 0, -1);
    foreach ($joins as $join) {
      $join_str .= $join;
    }

    $sql = "select $live_and_ghost from $basetable ";
    $sql_select = $sql . $join_str;
    $sql_select .= " order by $basetable.id $id_order";

    return   ['sql' => $sql_select, 'transed_fields' => $transed_fields, 'joins' => $joins, 'ghosts' => $ghosts];
  }




  function getDatabyBasetable($actcode, $table, $id_order, $query_cfg, $start, $pageSize, $currentUser) {

    $sqlobj = $this->getSqlByActCode($actcode, $table, $id_order);  // 已经联合查询下拉trigger
    $sql      = $sqlobj['sql'];


    if ($query_cfg) {
      $sql          = $this->buildSql_with_query_cfg($sqlobj['transed_fields'],  $table, $sql, $query_cfg);
    }




    $sql = $this->add_sql_author($sql, $table, $currentUser);
    logtext($sql);


    $qucik = $this->QuickSql($sql);
    // debug($qucik);
    // die();
    $all_rows = $this->db->query($qucik)->result_array();
    // debugtime('2:sql count end');

    $total = count($all_rows);


    // $all_rows = $this->db->query($sql)->result_array();
    // $total = count($all_rows);

    if ($pageSize != "" || $pageSize != null) {
      $sql   = $sql . " limit $start,$pageSize";
    }
    // debug($sql);
    $rows          = $this->db->query($sql)->result_array();
    $data['rows']  = $rows;
    $data['total'] = $total;
    $data['table'] = $table;
    $data['sql']   = $sql;
    return $data;
  }

  function  QuickSql($sql) {
    // return $sql ;
    // 首先以逗号分割.

    $arr_1 = explode(',', $sql);

    $arr_2 = explode(' from ', $sql);

    // debug($arr_1);
    // debug($arr_2);

    $quick_sql = $arr_1[0] . " from " . $arr_2[1];
    // echo $quick_sql;
    return $quick_sql;
  }






  // 根据作者字段筛选数据.
  function add_sql_author($sql, $table, $currentUser) {

    if ($currentUser == 'admin' ||  $currentUser == 'super') {
      return $sql;
    }

    $this->load->model('MRdbms');
    $all_fields =  array_column($this->MRdbms->getTableColumnNames($table), 'Field');
    if (!in_array('author', $all_fields)) {
      return $sql;
    }



    $this->load->model('MOrganization');
    $scopes = $this->MOrganization->getUserScope($currentUser);
    $scopes_str = array_to_string($scopes, "'");
    $pointer = strrpos($sql, 'order', 0);  // 最后一次出现的位置 


    if (strpos($sql, 'where') == false) {
      //如果sql中没有where ,则使用where 
      $scopes_str =  " where   $table" . ".author in  ( $scopes_str )  ";
    } else {
      $scopes_str =  " and   $table" . ".author in  ( $scopes_str )  ";
    }
    $newstr = substr_replace($sql, " $scopes_str ", $pointer, 0);
    return $newstr;
  }





  //在transed_fields 寻找替换后的 带下划线的表名,返回 tablename_?.col
  public function seekTransferred($transed_fields, $table, $field) {


    // debug($transed_fields);

    $found = 'AAAA'; //如果出现就是错误了.
    foreach ($transed_fields as $one) {
      // one  的格式  table.column
      $talbe_point_column = explode('.', $one);
      $table_in_trans = $talbe_point_column[0];
      $column_in_trans = $talbe_point_column[1];
      if ((strpos($table_in_trans, $table) !== false) && (strpos($column_in_trans, $field) !== false)) {
        // debug("寻找到: $one 包含 $table and $field");
        $found = $one;
        continue;
      }
    }
    $found_arr = explode(' ', $found);
    return $found_arr[0];
  }




  function buildSql_with_query_cfg($transed_fields, $table, $sql, $query_cfg) {

    $count = $query_cfg['count'];
    $lines   = $query_cfg['lines'];



    $all_fields = $this->db->query("show full fields from  $table")->result_array();
    $all_fields = array_retrieve($all_fields, array(
      'Field',
      'Type'
    ));


    $fix   = 'where 1=1 and(';
    $where = '';
    for ($i = 0; $i < $count; $i++) {
      $and_or = $lines['and_or_' . $i];
      if ($i == 0) {
        $and_or = '';  // 去除第一个的 and_or 字符串.
      }
      $field = $lines['field_' . $i];
      $wrapp_text = "'";  // 全部包装起来, 即使是数字类型的.
      $field_in_line = $lines['field_' . $i];
      $arg      = $lines['vset_' . $i];
      $this->db->where(['actcode' => $query_cfg['actcode'], 'field_e' => $field_in_line]);
      $triggercfg = $this->db->get('nanx_biz_column_trigger_group')->row_array();
      if ($triggercfg) {

        // debug($triggercfg);

        if ($triggercfg['combo_table'] == 'nanx_code_table') {
          $this->db->where(['category' => $triggercfg['codetable_category_value'], 'display_text' => $lines['vset_' . $i]]);
          $code_found_row = $this->db->get('nanx_code_table')->row_array();
          $used_codetable_value   = $code_found_row['value'];
          $field = $lines['field_' . $i];
          $arg      = $used_codetable_value;
        } else {

          //   combo_table 不能直接使用名字,必须加上 下划线和序列号.
          // debug($transed_fields);
          $field = $this->seekTransferred($transed_fields, $triggercfg['combo_table'], $triggercfg['list_field']);
          // debug($field);
        }
      } else {
        $field = $table . "." . $field_in_line;
      }



      $operator = $lines['operator_' . $i];
      if ($operator == 'like') {
        $arg = '%' . $arg . '%';
      }
      if ($operator == 'like_begin') {
        $operator = 'like';
        $arg      = $arg . '%';
      }
      if ($operator == 'like_end') {
        $operator = 'like';
        $arg      = '%' . $arg;
      }
      $where .= $and_or . ' (' . $field . ' ' . $operator . ' ' . $wrapp_text . $arg . $wrapp_text . ') ';
    }
    //bugfix: 不能只替换order,因为table name 或字段 可能包含order
    $sql = str_replace('order by', $fix . $where . ') order by ', $sql);
    return $sql;
  }



  function getDatabySql($sql) {

    $data = array();
    $this->load->model('MDataGrid');
    $sqltype = $this->MDataGrid->judeSqlType($sql);
    $dbres = $this->db->query($sql);
    if ($dbres) {
      if ($sqltype == 'select') {
        $data['dbok'] = true;
        $data['rows'] = $dbres->result_array();
        $total         = count($data['rows']);
        $data['total'] = $total;
      } else {
        $data['dbok'] = true;
        $data['total'] = 1;
        $effected = $this->db->affected_rows();
        $data['rows'] = array(array('sqltype' => $sqltype, 'effected' => $effected));
      }
    } else {
      $data['dbok'] = false;
      $data['rows'] = null;
      $data['sql_code'] = $this->db->_error_number();
      $data['sql_error_msg'] =   $this->db->_error_message();
    }

    $data['sql'] = $sql;
    return $data;
  }
}
