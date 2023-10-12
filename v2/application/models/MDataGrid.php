<?php
class MDataGrid extends CI_Model {




  public function setACtcfgStatic($user, $actcode, $json) {
    $this->db->where(['user' => $user, 'actcode' => $actcode]);
    $rows = $this->db->get('boss_static_actcfg')->result_array();
    if (count($rows) >= 1) {
      $this->db->update('boss_static_actcfg', ['actcfg' => $json]);
    } else {
      $this->db->insert('boss_static_actcfg', ['user' => $user, 'actcode' => $actcode, 'actcfg' => $json]);
    }
  }



  function skip_field($total_cols_cfg, $forbidden_fields, $hidden_type) {

    $forbidden_fields = array_column($forbidden_fields, 'forbidden_type', 'field');

    $fixed = [];
    foreach ($total_cols_cfg as  $item) {
      if (array_key_exists($item['field_e'], $forbidden_fields)) {
        if ($forbidden_fields[$item['field_e']]  == $hidden_type) {
          //do nothing
          // debug([$item, $hidden_type]);
        } else {
          $fixed[] = $item;
        }
      } else {
        $fixed[] = $item;
      }
    }
    return $fixed;
  }



  function callerIncaller($url, $para) {
    $c_and_m = explode("/", $url);
    $c       = $c_and_m[0];
    $m       = $c_and_m[1];

    $this->load->model($c);

    $result = $this->$c->$m($para);
    return $result;
  }



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



  //return col_cfg

  function getFieldsRouter($activity_summary, $para_array) {

    $datagrid_type = $activity_summary['datagrid_type'];
    $base_table    = $activity_summary['base_table'];

    // debug($datagrid_type);
    if ($datagrid_type == 'html') {
      return [];
    }

    if (($datagrid_type == 'table') || ($base_table == 'nanx_code_table')) {
      //通过表类型得到属性
      $col_cfg = $this->getFieldesByType_table($activity_summary, $para_array);

      return $col_cfg;
    }

    if ($datagrid_type == 'tree') {
      $col_cfg = $this->getFieldesByType_tree($activity_summary, $para_array);
      return $col_cfg;
    }



    if ($datagrid_type == 'sql') {
      $col_cfg = $this->getFieldesByType_sql($activity_summary, $para_array);
      return $col_cfg;
    }

    if ($datagrid_type == 'service') {

      $col_cfg = $this->getFieldesByType_service($activity_summary, $para_array);
      return $col_cfg;
    }
  }


  //获取所有字段的配置,先不出来隐藏字段
  function  getFieldesByType_table($activity_summary, $para_array) {

    $datagrid_code = $activity_summary['datagrid_code'];
    $base_table    = $activity_summary['base_table'];

    $all_db_fields =    $this->db->query("show full fields  from $base_table")->result_array();

    //all_db_fields 为所有字段, 获取所有的字段的配置,
    $all_col_cfg  = $this->MFieldcfg->getAllColsCfg($datagrid_code, $base_table, $all_db_fields, true);

    return $all_col_cfg;
  }


  function  getFieldesByType_sql($activity_summary, $para_array) {

    $datagrid_code = $activity_summary['datagrid_code'];
    $sql           = $activity_summary['sql'];


    if (isset($para_array['para_json'])) {
      $sql_para  = $para_array['para_json'];
      $sql_fixed = strMarcoReplace($sql, $sql_para);
    } else {
      $sql_fixed = $sql;
    }



    $sqltype = $this->judeSqlType($sql_fixed);
    $ret     = array();
    if ($sqltype == 'select') {
      $dbres = $this->db->query($sql_fixed);
      if ($dbres) {
        $ret['dbok'] = true;
        $ret['rows'] = $dbres->result_array();
      } else {
        $ret['dbok'] = false;
        $ret['rows'] = null;
      }
    } else // not execute, assume sql sytanx is right.
    {
      $ret['rows'] = array(
        array(
          'sqltype' => $sqltype,
          'effected' => 20
        )
      );
      $ret['dbok'] = true;
    }

    if (!$ret['dbok']) {
      $col_cfg = array(
        'sql_syntax_error' => true
      );
    } else {
      if ($ret['rows']) {
        $fields_e = array_keys($ret['rows'][0]);
        if ($datagrid_code == 'NANX_TB_LAYOUT') {
          $arr      = getLayoutFields($ret['rows']);
          $fields_e = $arr['cols'];
        }
      } else {
        $fields_e = array(
          0 => 'id'
        );
        if ($datagrid_code == 'NANX_TB_LAYOUT') {
          $fields_e = array(
            0 => 'col_0'
          );
        }
      }
      $this->load->model('MFieldcfg');
      $col_cfg = $this->MFieldcfg->getAllColsCfg($datagrid_code, 'NULL', $fields_e, true);
    }
    return $col_cfg;
  }


  function getFieldesByType_service($activity_summary, $para_array) {
    $datagrid_code = $activity_summary['datagrid_code'];
    $service_url   = $activity_summary['service_url'];
    $base_table    = $activity_summary['base_table'];

    if ($base_table == 'nanx_code_table') {
      return null;
    }

    $ret = $this->callerIncaller($service_url, $para_array);


    if ($ret) {
      $ret2arr  = $ret;

      if ($datagrid_code == 'NANX_FS_2_TABLE') {
        $col_cfg = array();
        if (in_array($para_array['file_type'], array(
          'php',
          'js'
        ))) {
          array_push($col_cfg, array(
            'field_e' => 'id',
            'display_cfg' => array(
              'field_c' => 'id',
              'value' => 'id'
            )
          ));
          array_push($col_cfg, array(
            'field_e' => 'Filename',
            'display_cfg' => array(
              'field_c' => 'Filename',
              'value' => 'Filename'
            )
          ));
          array_push($col_cfg, array(
            'field_e' => 'Size',
            'display_cfg' => array(
              'field_c' => 'Size',
              'value' => 'Size'
            )
          ));
          array_push($col_cfg, array(
            'field_e' => 'Date',
            'display_cfg' => array(
              'field_c' => 'Date',
              'value' => 'Date'
            )
          ));
        }

        if (in_array($para_array['file_type'], array(
          'img'
        ))) {
          $file_trunk = $para_array['file_trunk'];
          for ($i = 0; $i < $file_trunk; $i++) {
            $col_i = array(
              'field_e' => $i,
              'display_cfg' => array(
                'field_c' => $i,
                'value' => $i
              )
            );
            array_push($col_cfg, $col_i);
          }
        }
      }
    }
    return $col_cfg;
  }



  function getFieldesByType_tree($activity_summary, $para_array) {
    $datagrid_code = $activity_summary['datagrid_code'];
    $base_table    = $activity_summary['base_table'];

    $para_array['transfer']           = true;
    $fields_e_with_idforbidden_option = $this->skip_field($datagrid_code, $this->db->list_fields($base_table), 'column_hidden');


    $fields_e = $fields_e_with_idforbidden_option['NotForbidden'];
    $col_cfg  = $this->MFieldcfg->getAllColsCfg($datagrid_code, $base_table, $fields_e, $para_array['transfer']);

    if ($fields_e_with_idforbidden_option['id_hidden']) {
      $id_col_cfg = $this->MFieldcfg->getAllColsCfg($datagrid_code, $base_table, ['id'], $para_array['transfer']);
      $id_col_cfg[0]['display_cfg']['idhidden'] = true;
      $col_cfg = array_merge($id_col_cfg, $col_cfg);
    }
    return $col_cfg;
  }


  function getDataGridConfig($para_array) {


    $this->load->model('MFieldcfg');
    $datagrid_code = $para_array['datagrid_code'];
    $this->db->where('datagrid_code', $datagrid_code);

    //得到actcode的对应信息
    $activity_summary            = $this->db->get('nanx_activity')->row_array();

    $datagrid_type    = $activity_summary['datagrid_type']; //table

    $total_cols_cfg = $this->getFieldsRouter($activity_summary, $para_array);

    $forbidden_fields = $this->MFieldcfg->getForbiddenFields($datagrid_code, 'column_hidden');


    $col_cfg = $this->skip_field($total_cols_cfg,  $forbidden_fields, 'column_hidden');


    $forbidden_fields = $this->MFieldcfg->getForbiddenFields($datagrid_code, 'form_hidden');
    $fms_cfg = $this->skip_field($total_cols_cfg,  $forbidden_fields, 'form_hidden');



    if (array_key_exists('sql_syntax_error', $col_cfg)) {
      $activity_summary['sql_syntax_error'] = $col_cfg['sql_syntax_error'];
      $col_cfg                              = array();
    }


    $id_order = $activity_summary['id_order'];
    if (empty($id_order)) {
      $id_order = "desc";
    }


    $activity_summary['idOrder']     = $id_order;
    $activity_summary['colsCfg']            = $col_cfg;  // 去除了隐藏列以后的所有列,给table 用
    $activity_summary['fmsCfg']            = $fms_cfg;  // 去除了隐藏列以后的所有列,给form 用
    return $activity_summary;
  }






  function  getBaseTableByActcode($actcode) {
    $this->db->where('datagrid_code', $actcode);
    $row = $this->db->get_where('nanx_activity')->row_array();
    return $row['base_table'];
  }



  public function isProcessMaintable($maintable) {

    $sql = "select * from boss_process_maintable where maintable='$maintable'";
    $row = $this->db->query($sql)->result_array();

    if (count($row) > 0) {
      return true;
    } else {
      return false;
    }
  }


  //获取grid-referinfo 配置
  public function getGridReferenceInfoCfg($actcode) {
    $referino = [];
    return $referino;
  }
}
