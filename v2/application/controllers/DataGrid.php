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




  public function saveActCodeColumnOrder() {
    $args = (array) json_decode(file_get_contents("php://input"));

    $datagrid_code = $args['datagrid_code'];
    $filedids = $args['filedids'];

    $sql = "delete from nanx_activity_column_order where datagrid_code='$datagrid_code'";
    $this->db->query($sql);

    $data = [];
    foreach ($filedids as $key => $value) {
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

  public function exportExcel() {

    // /total_cols_cfg
    // DataGridMeta['geturl']

    $para = (array) json_decode(file_get_contents("php://input"));

    // debug($para);

    $para['pageSize'] = 1000;
    $actcfg = $this->MDataGridCfgAssemble->PipeRunner($para);

    $cols = [];
    foreach ($actcfg['total_cols_cfg'] as $one_col) {
      $tmp = [];
      $tmp['key'] = $one_col['field_e'];
      $tmp['title'] = $one_col['display_cfg']['field_c'];
      $cols[] = $tmp;
    }
    if ($actcfg['DataGridMeta']['geturl'] == 'curd/listData') {
      $this->load->model('MCurd');
      $para = [];
      $para['DataGridCode'] = $actcfg['DataGridCode'];
      $para['currentPage'] = 1;
      $para['pageSize'] = 10000000;
      $para['role'] = $this->getUser();
      $para['user'] = $this->getUser();
      $para['table'] = $actcfg['DataGridMeta']['base_table'];
      $result = $this->MCurd->getActivityData($this->getUser(), $para);
      $records = $result['rows'];
    }
    $this->MExcel->exportExcel($actcfg['DataGridMeta']['datagrid_title'], $cols, $records);
  }

  public function getFlowTplButtons() {

    $btns = [
      ['button_code' => 'BpmProcess'],
      ['button_code' => 'bpmInfo'],
      ['button_code' => 'viewProcess']
    ];
    return $btns;
  }



  public function getCRUDBtns() {
    $btns = [
      ['button_code' => 'addData'],
      ['button_code' => 'editData'],
      ['button_code' => 'deleteData'],
      ['button_code' => 'refreshTable'],
      ['button_code' => 'tableSearch']
    ];
    return $btns;
  }



  public function batchSetButtons() {
    $json_paras = (array) json_decode(file_get_contents('php://input'), true);
    $actioncode  = $json_paras['actioncode'];
    $batch_type = $json_paras['batch_type'];

    if ($batch_type == "reset") {
      $this->db->delete('boss_portal_button_actcode', array('datagrid_code' => $actioncode));
      $ret['code'] = 200;
      $ret['msg'] = "success";
      echo json_encode($ret, JSON_UNESCAPED_UNICODE);
      return;
    }

    if ($batch_type == "curd_template") {
      $btns = $this->getCRUDBtns();
      $ret = $this->insertBatchSetButtonOrder($btns, $actioncode);
      echo json_encode($ret, JSON_UNESCAPED_UNICODE);
      return;
    }

    if ($batch_type == "flow_template") {
      $btns = $this->getFlowTplButtons();
      $ret  = $this->insertBatchSetButtons($btns, $actioncode);
      echo json_encode($ret, JSON_UNESCAPED_UNICODE);
      return;
    }


    if ($batch_type == "bpmstart_template") {
      $btns = [
        ['button_code' => 'BpmStart']
      ];
      $ret = $this->insertBatchSetButtons($btns, $actioncode);
      echo json_encode($ret, JSON_UNESCAPED_UNICODE);
      return;
    }
  }



  public function insertBatchSetButtons($btns, $actioncode) {
    for ($i = 0; $i < count($btns); $i++) {
      $data = array(
        'datagrid_code' => $actioncode,
        "button_code" => $btns[$i]['button_code'],
      );

      $this->db->insert('boss_portal_button_actcode', $data);
    }
    $ret = [];
    $ret['code'] = 200;
    $ret['msg'] = "success";
    return $ret;
  }


  public function insertBatchSetButtonOrder($btns, $actioncode) {

    for ($i = 0; $i < count($btns); $i++) {
      $data = array(
        'datagrid_code' => $actioncode,
        "button_code" => $btns[$i]['button_code'],
        "btnorder"    => $i + 1
      );

      $this->db->insert('boss_portal_button_actcode', $data);
    }
    $ret = [];
    $ret['code'] = 200;
    $ret['msg'] = "success";
    return $ret;
  }


  public function addActionButton() {
    $json_paras = (array) json_decode(file_get_contents('php://input'), true);

    // debug($json_paras);
    $this->db->insert('nanx_grid_button', $json_paras);

    $db_error = $this->db->error();
    $ret = [];
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "添加按钮错误:" . $db_error['message'];
    } else {
      $ret['code'] = 200;
      $ret['message'] = "添加按钮成功";
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function addDataGridCode() {
    $json_paras = (array) json_decode(file_get_contents('php://input'), true);
    $json_paras['datagrid_type'] = 'table';

    $this->db->insert('nanx_activity', $json_paras);
    $ret = [];
    $db_error = $this->db->error();
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "添加Action错误:" . $db_error['message'];
    } else {
      $ret['code'] = 200;
      $ret['message'] = "添加Action成功";
    }


    $menu = [
      'menu' => $json_paras['datagrid_code'],
      'text' => $json_paras['datagrid_title'],
      'router' => '/table/commonXTable',
      'type' => '菜单',
      'is_association_process' => 'n',
      'datagrid_code' => $json_paras['datagrid_code'],
      'menu_level' => 1

    ];
    $this->db->insert('boss_portal_menu_list', $menu);
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function modifyActionCode() {
    $json_paras = (array) json_decode(file_get_contents('php://input'), true);

    $this->db->where('datagrid_code', $json_paras['datagrid_code']);
    $this->db->update('nanx_activity', $json_paras);
    $db_error = $this->db->error();
    $ret = [];
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "修改Action错误:" . $db_error['message'];
    } else {
      $ret['code'] = 200;
      $ret['message'] = "修改Action成功";
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }





  public function deleteGridCode() {
    $args = (array) json_decode(file_get_contents('php://input'));
    $id = $args['id'];
    $this->db->where('id', $id);
    $this->db->delete('nanx_activity');
    $db_error = $this->db->error();
    if (0 == $db_error['code']) {
      $ret = ['code' => 200, 'message' => '删除Action成功', 'data' => null];
    } else {
      $ret = ['code' => $db_error['code'], 'message' => '删除Action失败,DBcode:' . $db_error['code'], 'data' => null];
    }
    echo json_encode($ret);
  }

  //所有字段的配置,包括 label, 是否隐藏(form,column),是否只读,插件,字典表
  public function getActCols() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $base_table = $this->MDataGrid->getBaseTableByActcode($para['DataGridCode']);
    $actcode = $para['DataGridCode'];
    $full_columns = $this->db->query("show full fields  from $base_table ")->result_array();
    $fixed = [];
    $this->load->model('MFieldcfg');
    foreach ($full_columns as $column) {
      $tmp = [];
      $tmp['Field'] = $column['Field'];
      $tmp['Type'] = $column['Type'];
      $tmp['Comment'] = $column['Comment'];


      //强制的显示配置
      $this->db->where(['datagrid_code' => $actcode, 'field_e' => $column['Field']]);
      $this->db->select(['field_c',  'grouptitle', 'field_width', 'label_width', 'show_as_pic', 'width', 'handler']);
      $row = $this->db->get('nanx_activity_field_special_display_cfg')->row_array();

      $tmp['width'] = '';
      $tmp['handler'] = '';
      $tmp['label'] = '';
      $tmp['grouptitle'] = '';


      if ($row) {
        $tmp['width'] = $row['width'];
        $tmp['handler'] = $row['handler'];
        $tmp['label'] = $row['field_c'];
        $tmp['grouptitle'] = $row['grouptitle'];
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
      $tmp['readonly'] = false;
      $tmp['pluginname'] = $this->MFieldcfg->toUnformType($column['Type']);

      $this->db->where(['datagrid_code' => $para['DataGridCode'], 'base_table' => $base_table, 'field_e' => $column['Field']]);
      $row = $this->db->get('nanx_biz_column_editor_cfg')->row_array();


      if ($row) {
        if (intval($row['readonly']) == 1) {
          $tmp['readonly'] = true;
        }

        if (strlen($row['uform_plugin']) > 3) {
          $tmp['pluginname'] = $row['uform_plugin'];
        }

        $tmp['uform_para'] = $row['uform_para'];
      }

      //字典表 配置

      $this->db->where(['actcode' => $para['DataGridCode'], 'base_table' => $base_table, 'field_e' => $column['Field']]);
      $row = $this->db->get('nanx_biz_column_trigger_group')->row_array();
      $tmp['category'] = null;
      if ($row) {
        $tmp['category'] = $row['codetable_category_value'];
      }
      $tmp['initvalue'] = '';
      $fixed[] = $tmp;
    }

    $ret['code'] = 200;
    $ret['data'] =  $fixed;
    echo json_encode($ret);
  }



  public  function saveTriggerGroup() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $base_table = $this->MDataGrid->getBaseTableByActcode($para['DataGridCode']);
    $group_id = randstr(30);
    $actcode = $para['DataGridCode'];

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
      $tmp['actcode'] = $actcode;
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
    $actcode = $para['DataGridCode'];

    $sql = "select distinct   group_name,group_id from nanx_biz_column_trigger_group where actcode='{$actcode}' ";
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
    $actcode = $para['DataGridCode'];
    //得到流程对应表
    $base_table = $this->MDataGrid->getBaseTableByActcode($actcode);
    $this->saveFieldCfgHandler($actcode, $base_table, $para);

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


  public function saveFieldCfgHandler($actcode, $base_table, $para) {

    //得到流程对应表
    $base_table = $this->MDataGrid->getBaseTableByActcode($actcode);


    if (!array_key_exists('uform_para', $para)) {
      $para['uform_para'] = '';
    }


    if (!array_key_exists('grouptitle', $para)) {
      $para['grouptitle'] = '';
    }


    // 修改  label
    if (strlen($para['label']) > 1) {
      $wherecfg = ['datagrid_code' => $actcode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $rows = $this->db->get_where('nanx_activity_field_special_display_cfg', $wherecfg)->result_array();
      if (count($rows) == 1) {
        $this->db->update('nanx_activity_field_special_display_cfg', ['field_c' => $para['label']], $wherecfg);
      } else {
        $wherecfg['field_c'] = $para['label'];
        // $wherecfg['grouptitle'] = $para['grouptitle'];
        $this->db->insert('nanx_activity_field_special_display_cfg',  $wherecfg);
      }
    }


    // 修改  grouptitle
    if (strlen($para['grouptitle']) > 1) {
      $wherecfg = ['datagrid_code' => $actcode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $rows = $this->db->get_where('nanx_activity_field_special_display_cfg', $wherecfg)->result_array();
      if (count($rows) == 1) {
        $this->db->update('nanx_activity_field_special_display_cfg', ['grouptitle' => $para['grouptitle']], $wherecfg);
      } else {
        $wherecfg['grouptitle'] = $para['grouptitle'];
        $this->db->insert('nanx_activity_field_special_display_cfg',  $wherecfg);
      }
    }



    if (strlen($para['width']) > 1) {
      $wherecfg = ['datagrid_code' => $actcode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $rows = $this->db->get_where('nanx_activity_field_special_display_cfg', $wherecfg)->result_array();
      if (count($rows) == 1) {
        $this->db->update('nanx_activity_field_special_display_cfg', ['width' => $para['width'],], $wherecfg);
      } else {
        $wherecfg['width'] = $para['width'];
        $this->db->insert('nanx_activity_field_special_display_cfg',  $wherecfg);
      }
    }
    if (strlen($para['handler']) > 1) {
      $wherecfg = ['datagrid_code' => $actcode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $rows = $this->db->get_where('nanx_activity_field_special_display_cfg', $wherecfg)->result_array();
      if (count($rows) == 1) {
        $this->db->update('nanx_activity_field_special_display_cfg', ['handler' => $para['handler'],], $wherecfg);
      } else {
        $wherecfg['handler'] = $para['handler'];
        $this->db->insert('nanx_activity_field_special_display_cfg',  $wherecfg);
      }
    }

    if (strlen($para['handler']) == 0) {
      $wherecfg = ['datagrid_code' => $actcode,  'base_table' => $base_table,  'field_e' => $para['Field']];
      $this->db->update('nanx_activity_field_special_display_cfg', ['handler' => null], $wherecfg);
    }


    $wherecfg = ['datagrid_code' => $actcode,  'base_table' => $base_table,  'field_e' => $para['Field']];
    $rows = $this->db->get_where('nanx_biz_column_editor_cfg', $wherecfg)->result_array();
    if (count($rows) == 1) {

      $this->db->update(
        'nanx_biz_column_editor_cfg',
        ['readonly' => $para['readonly'], 'uform_para' => $para['uform_para'],  'uform_plugin' => $para['pluginname']],
        $wherecfg
      );
    } else {

      $wherecfg['readonly'] = $para['readonly'];
      $wherecfg['uform_plugin'] = $para['pluginname'];
      $wherecfg['uform_para'] = $para['uform_para'];
      $this->db->insert('nanx_biz_column_editor_cfg',  $wherecfg);
    }



    //form 是否隐藏  
    $wherecfg = ['datagrid_code' => $actcode,   'field' => $para['Field'], 'forbidden_type' => 'form_hidden'];

    //先清空
    $this->db->where($wherecfg);
    $this->db->delete('nanx_activity_forbidden_field');
    if (intval($para['form_hidden']) == 1) {
      $this->db->insert('nanx_activity_forbidden_field', $wherecfg);
    }





    //table 是否隐藏  
    $wherecfg = ['datagrid_code' => $actcode,   'field' => $para['Field'], 'forbidden_type' => 'column_hidden'];

    //先清空
    $this->db->where($wherecfg);
    $this->db->delete('nanx_activity_forbidden_field');
    if (intval($para['column_hidden']) == 1) {

      $this->db->insert('nanx_activity_forbidden_field', $wherecfg);
    }


    // 字典表配置
    //category nanx_biz_column_trigger_group  Field

    $wherecfg = ['actcode' => $actcode, 'base_table' => $base_table, 'field_e' => $para['Field'], 'combo_table' => 'nanx_code_table'];
    $this->db->where($wherecfg);
    $this->db->delete('nanx_biz_column_trigger_group');


    // $para['category'] 有设置才是使用字典表,否则不需要操作. 
    if (strlen($para['category']) > 1) {
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



  function getAllCategory() {
    $sql = " select distinct  category  as  catid,  category as  catname from nanx_code_table";
    $ret = ['code' => 200, 'data' => $this->db->query($sql)->result_array()];
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }




  function saveOverrideQueryCfg() {
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);

    $actcode =   $para['DataGridCode'];
    $query_cfg_field =   $para['query_cfg_field'];
    $query_cfg_value =   $para['query_cfg_value'];
    $cfg = ['count' => 1, 'lines' => ['and_or_0' => 'and', 'field_0' => $query_cfg_field, 'operator_0' => '=', 'vset_0' => $query_cfg_value]];
    $cfg = json_encode($cfg);
    $this->db->where(['datagrid_code' => $actcode]);
    $this->db->update('nanx_activity', ['fixed_query_cfg' => $cfg]);
    $db_error = $this->db->error();
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "保存Querycfg失败:" . $db_error['message'];
    } else {
      $ret = ['code' => 200, 'message'  => '设置query_cfg成功'];
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function  addGridReferCfg() {

    $args = (array) json_decode(file_get_contents('php://input'));
    $data = [];




    $data['reftype'] = $args['reftype'];
    $data['maintable'] = $args['maintable'];


    if (array_key_exists('datagrid_code', $args)) {
      $data['datagrid_code'] = $args['datagrid_code'];
    }

    if (array_key_exists('table_button_code', $args)) {
      $data['table_button_code'] = $args['table_button_code'];
    }




    if (array_key_exists('act_name', $args)) {
      $data['act_name'] = $args['act_name'];
    }

    if (array_key_exists('onaction', $args)) {
      $data['onaction'] = $args['onaction'];
    }


    if (array_key_exists('infotitle', $args)) {
      $data['infotitle'] = $args['infotitle'];
    }

    if (array_key_exists('refer_actcode', $args)) {
      $data['refer_actcode'] = $args['refer_actcode'];
    }


    if (array_key_exists('refertable', $args)) {
      $data['refertable'] = $args['refertable'];
    }


    if (array_key_exists('column_to_filter', $args)) {
      $data['column_to_filter'] = $args['column_to_filter'];
    }

    if (array_key_exists('maincolumn', $args)) {
      $data['maincolumn'] = $args['maincolumn'];
    }

    if (array_key_exists('serviceurl', $args)) {
      $data['serviceurl'] = $args['serviceurl'];
    }

    if (array_key_exists('statictext', $args)) {
      $data['statictext'] = $args['statictext'];
    }



    if (array_key_exists('colsused', $args)) {
      $data['colsused'] = array_to_string($args['colsused'], null);
    }


    if (array_key_exists('btntext', $args)) {
      $data['btntext'] = $args['btntext'];
    }



    if (array_key_exists('sql', $args)) {
      $data['sql'] = $args['sql'];
    }



    $this->db->insert('boss_act_referenceinfo_cfg', $data);
    $db_error = $this->db->error();
    if (0 == $db_error['code']) {
      $ret = ['code' => 200, 'message' => '保存成功', 'data' => null];
    } else {
      $ret = ['code' => $db_error['code'], 'message' => '数据库操作失败,DBcode:' . $db_error['code'], 'data' => null];
    }
    echo json_encode($ret);
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

  public function batchUpdateFieldCfg() {



    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $datagrid_code = $para['datagrid_code'];

    $base_table = $this->MDataGrid->getBaseTableByActcode($datagrid_code);
    $submitData = $para['submitData'];

    foreach ($submitData as $key => $one_filed_cfg) {
      # code...
      $this->saveFieldCfgHandler($datagrid_code, $base_table, (array) $one_filed_cfg);
    }

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


  public function saveTips() {

    $ret = [];
    $post = file_get_contents('php://input');
    $para = (array) json_decode($post);
    $actcode = $para['DataGridCode'];
    $tips = $para['tips'];
    $this->db->where(['datagrid_code' => $actcode]);
    $this->db->update('nanx_activity', ['tips' => $tips]);
    $db_error = $this->db->error();
    if ($db_error['code'] > 0) {
      $ret['code'] = 500;
      $ret['message'] = "保存Tips失败:" . $db_error['message'];
    } else {
      $ret = ['code' => 200, 'message'  => '保存Tips成功'];
    }
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }


  public function getPortalDataGrids() {
    $this->db->where('portaluse', 'y');
    $this->db->order_by('id', 'desc');
    $grids = $this->db->get('nanx_activity')->result_array();
    $ret = [];
    $ret['code'] = 200;
    $ret['data'] = $grids;
    echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  }
}
