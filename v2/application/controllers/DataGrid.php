<?php

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;

if (!defined('BASEPATH')) {
  exit('No direct script access allowed');
}

class DataGrid extends MY_Controller {
  public function __construct() {
    parent::__construct();
    header('Access-Control-Allow-Origin: * ');
    header('Access-Control-Allow-Headers: Origin, X-Requested-With,Content-Type, Accept,authorization');
    header('Access-Control-Allow-Credentials', true);
    if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
      exit();
    }
  }




  public function saveGridFieldOrder() {
    $args = (array) json_decode(file_get_contents("php://input"));

    $datagrid_code = $args['datagrid_code'];
    $filedids = $args['filedids'];

    $sql = "delete from nanx_activity_column_order where datagrid_code='$datagrid_code'";
    $this->db->query($sql);

    $data = [];
    foreach ($filedids as  $value) {
      $insertData = array(
        'datagrid_code' => $datagrid_code,
        'column_field' => $value,

      );
      $data[] = $insertData;
    }

    $this->db->insert_batch('nanx_activity_column_order', $data);
    $error = $this->db->error();

    if ($error['code'] == 0) {
      $ret = array('code' => 200, 'message' => '保存成功');
    } else {
      $ret = array('code' => 400, 'message' => $error['message']);
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }



  //所有字段的配置,包括 label, 是否隐藏(form,column),是否只读,插件,字典表
  public function getColsDbInfo() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $base_table = $this->MDataGrid->getBaseTableByActcode($para['DataGridCode']);
    $dataGridCode = $para['DataGridCode'];
    $full_columns = $this->db->query("show full fields  from $base_table ")->result_array();
    $fixed = [];
    $this->load->model('MFieldcfg');
    foreach ($full_columns as $column) {
      $tmp = [];
      $tmp['Field'] = $column['Field'];
      $tmp['Type'] = $column['Type'];
      $tmp['Comment'] = $column['Comment'];


      //强制的显示配置
      $this->db->where(['datagrid_code' => $dataGridCode, 'field_e' => $column['Field']]);
      $this->db->select(['field_c', 'field_width', 'label_width', 'show_as_pic', 'width', 'handler']);
      $row = $this->db->get('nanx_activity_field_special_display_cfg')->row_array();

      $tmp['width'] = '';
      $tmp['handler'] = '';
      $tmp['label'] = '';


      if ($row) {

        $tmp['width'] = $row['width'];
        $tmp['handler'] = $row['handler'];
        $tmp['label'] = $row['field_c'];
      }

      //form 中是否隐藏

      $ForbiddenFields = array_column($this->MFieldcfg->getForbiddenFields($para['DataGridCode'], 'form_hidden'), 'field');
      $tmp['form_hidden'] = false;
      if (in_array($column['Field'], $ForbiddenFields)) {
        $tmp['form_hidden'] = true;
      }
      //column 中是否隐藏

      $ForbiddenFields = array_column($this->MFieldcfg->getForbiddenFields($para['DataGridCode'], 'column_hidden'), 'field');

      if (in_array($column['Field'], $ForbiddenFields)) {
        $tmp['column_hidden'] = true;
      } else {
        $tmp['column_hidden'] = false;
      }


      //nanx_biz_column_editor_cfg ,控制 是否只读/插件名称

      $tmp['plugid'] = $this->MFieldcfg->toUnformType($column['Type']);
      $tmp['readonly'] = false;
      $tmp['uform_para'] = null;
      $tmp['default_v'] = null;
      $tmp['defaultv_para'] = null;

      $this->db->where(['datagrid_code' => $para['DataGridCode'], 'base_table' => $base_table, 'field_e' => $column['Field']]);
      $row = $this->db->get('nanx_biz_column_editor_cfg')->row_array();
      if ($row) {

        if (intval($row['readonly']) == 1) {
          $tmp['readonly'] = true;
        }

        if (strlen($row['uform_plugin']) > 3) {
          $tmp['plugid'] = $row['uform_plugin'];
        }

        $tmp['uform_para'] = $row['uform_para'];
        $tmp['default_v'] = $row['default_v'];
        $tmp['defaultv_para'] = $row['defaultv_para'];
      }

      //字典表 配置

      $this->db->where(['actcode' => $para['DataGridCode'], 'base_table' => $base_table, 'field_e' => $column['Field']]);
      $row = $this->db->get('nanx_biz_column_trigger_group')->row_array();
      $tmp['category'] = null;
      if ($row) {
        $tmp['category'] = $row['codetable_category_value'];
      }
      $fixed[] = $tmp;
    }

    //再进行排序

    $sql = " select  distinct  datagrid_code, column_field  from nanx_activity_column_order where datagrid_code='{$para['DataGridCode']}'  ";
    $Array_display_order = $this->db->query($sql)->result_array();

    $ret['code'] = 200;
    $ret['data'] =  $this->MFieldcfg->_sortFieldDisplayOrder($fixed, $Array_display_order);
    echo json_encode($ret);
  }


  public function saveTriggerGroup() {
    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $base_table = $this->MDataGrid->getBaseTableByActcode($para['DataGridCode']);
    $group_id = randstr(30);
    $dataGridCode = $para['DataGridCode'];

    if (intval($para['counter']) == 0) {
      $ret['code'] = 200;
      $ret['message'] = "请添加配置条目";
      echo json_encode($ret, JSON_UNESCAPED_UNICODE);
      die;
    }

    if (strlen($para['group_name']) == 0) {
      $ret['code'] = 200;
      $ret['message'] = "请填写联动组名称";
      echo json_encode($ret, JSON_UNESCAPED_UNICODE);
      die;
    }

    $rows = [];
    $lines = $para['lines'];
    $i = 1;

    foreach ($lines as $line) {
      $line = (array) $line;
      $tmp = [];
      $tmp['actcode'] = $dataGridCode;
      $tmp['group_id'] = $group_id;
      $tmp['group_name'] = $para['group_name'];
      $tmp['base_table'] = $base_table;

      $tmp['combo_table'] = $line['combo_table'];
      $tmp['field_e'] = $line['field_e'];
      $tmp['list_field'] = $line['list_field'];
      $tmp['value_field'] = $line['value_field'];
      $tmp['filter_field'] = $line['filter_field'];
      $tmp['level'] = $i;
      $tmp['group_type'] = 'isgroup';
      $rows[] = $tmp;
      $i++;
    }
    $this->db->insert_batch('nanx_biz_column_trigger_group', $rows);
    $db_error = $this->db->error();

    $ret['code'] = 200;
    $ret['message'] = "添加联动组成功";
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "添加联动组失败:" . $db_error['message'];
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function getTriggerGroups() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $dataGridCode = $para['DataGridCode'];

    $sql = "select distinct   group_name,group_id from nanx_biz_column_trigger_group where actcode='{$dataGridCode}' ";
    $groups = $this->db->query($sql)->result_array();
    $data = [];

    foreach ($groups as $item) {
      $group_id = $item['group_id'];
      $group_name = $item['group_name'];
      $cfgs = $this->db->query("select * from  nanx_biz_column_trigger_group where group_id='{$group_id}' ")->result_array();
      $data[] = ['group_name' => $group_name, 'group_id' => $group_id, 'cfgs' => $cfgs];
    }
    $ret['code'] = 200;
    $ret['data'] = $data;
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }



  public function deleteTriggerGroup() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $groupid = $para['groupid'];
    $sql = "delete from   nanx_biz_column_trigger_group where group_id='{$groupid}' ";
    $this->db->query($sql);
    $db_error = $this->db->error();
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "删除联动组失败:" . $db_error['message'];
    } else {
      $ret['code'] = 200;
      $ret['message'] = "删除联动组成功";
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function saveFieldCfg() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $dataGridCode = $para['DataGridCode'];
    //得到流程对应表
    $base_table = $this->MDataGrid->getBaseTableByActcode($dataGridCode);
    $this->saveFieldCfgHandler($dataGridCode, $base_table, $para);

    $db_error = $this->db->error();
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "字段配置保存失败:" . $db_error['message'];
    } else {
      $ret['code'] = 200;
      $ret['message'] = "字段配置保存成功";
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
    die;
  }


  public function saveFieldCfgHandler($dataGridCode, $base_table, $para) {


    // 修改  label
    $this->_saveFieldLabelHandler($dataGridCode, $base_table, $para);


    // 列 render
    $this->_saveFieldColRenderHandler($dataGridCode, $base_table, $para);


    // 列  编辑配置
    $this->_saveFieldColmnEditorHandler($dataGridCode, $base_table, $para);


    // 列隐藏设置
    $this->_saveFieldHiddenConfigure($dataGridCode, $para);

    // 字典表配置
    $this->_saveFieldCategeoryHandler($dataGridCode, $base_table, $para);

    // 初始值配置
    $this->_saveFieldInitValueHandler($dataGridCode, $base_table, $para);
  }


  private function _saveFieldLabelHandler($dataGridCode, $base_table, $para) {

    // 修改  label
    if (strlen($para['label']) > 1) {
      $wherecfg = ['datagrid_code' => $dataGridCode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $rows = $this->db->get_where('nanx_activity_field_special_display_cfg', $wherecfg)->result_array();
      if (count($rows) == 1) {
        $this->db->update('nanx_activity_field_special_display_cfg', ['field_c' => $para['label']], $wherecfg);
      } else {
        $wherecfg['field_c'] = $para['label'];
        $this->db->insert('nanx_activity_field_special_display_cfg', $wherecfg);
      }
    }
  }


  // 列 render

  private function _saveFieldColRenderHandler($dataGridCode, $base_table, $para) {

    if (strlen($para['handler']) > 1) {
      $wherecfg = ['datagrid_code' => $dataGridCode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $rows = $this->db->get_where('nanx_activity_field_special_display_cfg', $wherecfg)->result_array();
      if (count($rows) == 1) {
        $this->db->update('nanx_activity_field_special_display_cfg', ['handler' => $para['handler']], $wherecfg);
      } else {
        $wherecfg['handler'] = $para['handler'];
        $this->db->insert('nanx_activity_field_special_display_cfg', $wherecfg);
      }
    }

    if (strlen($para['handler']) == 0) {
      $wherecfg = ['datagrid_code' => $dataGridCode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $this->db->update('nanx_activity_field_special_display_cfg', ['handler' => null], $wherecfg);
    }
  }


  // 列 编辑 handler

  private function _saveFieldColmnEditorHandler($dataGridCode, $base_table, $para) {
    $wherecfg = ['datagrid_code' => $dataGridCode,  'base_table' => $base_table,  'field_e' => $para['Field']];
    $rows = $this->db->get_where('nanx_biz_column_editor_cfg', $wherecfg)->result_array();
    if (count($rows) == 1) {
      $this->db->update(
        'nanx_biz_column_editor_cfg',
        [
          'readonly' => $para['readonly'],
          'default_v' => $para['default_v'],
          'defaultv_para' => $para['defaultv_para'],
          'uform_para' => $para['uform_para'],
          'uform_plugin' => $para['plugid']
        ],
        $wherecfg
      );
    } else {

      $wherecfg['readonly'] = $para['readonly'];
      $wherecfg['default_v'] =  $para['default_v'];
      $wherecfg['uform_plugin'] = $para['plugid'];
      $wherecfg['uform_para'] = $para['uform_para'];
      $this->db->insert('nanx_biz_column_editor_cfg', $wherecfg);
    }
  }

  // 列隐藏设置
  private function _saveFieldHiddenConfigure($dataGridCode, $para) {

    //form 是否隐藏
    $wherecfg = ['datagrid_code' => $dataGridCode,   'field' => $para['Field'], 'forbidden_type' => 'form_hidden'];

    $this->db->where($wherecfg);
    $this->db->delete('nanx_activity_forbidden_field');
    if (intval($para['form_hidden']) == 1) {
      $this->db->insert('nanx_activity_forbidden_field', $wherecfg);
    }

    //table 是否隐藏
    $wherecfg = ['datagrid_code' => $dataGridCode,   'field' => $para['Field'], 'forbidden_type' => 'column_hidden'];
    $this->db->where($wherecfg);
    $this->db->delete('nanx_activity_forbidden_field');

    if (intval($para['column_hidden']) == 1) {
      $this->db->insert('nanx_activity_forbidden_field', $wherecfg);
    }
  }

  private function _saveFieldCategeoryHandler($dataGridCode, $base_table, $para) {
    // 字典表配置
    if (strlen($para['category']) > 1) {

      $wherecfg = ['actcode' => $dataGridCode, 'base_table' => $base_table, 'field_e' => $para['Field'], 'combo_table' => 'nanx_code_table'];
      $this->db->where($wherecfg);
      $this->db->delete('nanx_biz_column_trigger_group');

      $data = $wherecfg;
      $data['level'] = 1;
      $data['group_name'] = '字典表_' . $para['category'];
      $data['codetable_category_value'] = $para['category'];
      $data['value_field'] = 'value';
      $data['list_field'] = 'display_text';
      $data['group_id'] = randstr(10);
      $data['group_type'] = 'nogroup';
      $this->db->insert('nanx_biz_column_trigger_group', $data);
    }
  }


  private function  _saveFieldInitValueHandler($dataGridCode, $base_table, $para) {
  }




  public function getAllCategory() {
    $sql = " select distinct  category  as  catid,  category as  catname from nanx_code_table";
    $ret = ['code' => 200, 'data' => $this->db->query($sql)->result_array()];
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }



  public function actionBasedRowPuller() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $srow = (array) $args['srow'];
    $pieces = explode("/", $args['serviceurl']);
    $model =   $pieces[0];
    $function = $pieces[1];
    $this->load->model($model);
    $model_return = $this->$model->$function($srow);
    $ret = ['combinedRef' => $model_return];
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function saveFixedQueryConfigure() {
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $objectArray = $para['fixedQueryLiens'];
    // 将对象数组转换为 JSON 字符串
    $jsonString = json_encode($objectArray, JSON_UNESCAPED_UNICODE);
    $this->db->where("datagrid_code", $para['datagrid_code']);
    $this->db->update("nanx_activity", ['fixed_query_cfg' => $jsonString]);
    $ret = ['code' => 200, 'msg' => '修改成功'];
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }



  public function getPortalDataGrids() {
    $this->db->order_by('id', 'desc');
    $grids = $this->db->get('nanx_activity')->result_array();
    $ret = [];
    $ret['code'] = 200;
    $ret['data'] = $grids;
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }
}
