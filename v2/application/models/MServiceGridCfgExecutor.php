<?php

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;

class MServiceGridCfgExecutor extends CI_Model implements StageInterface {
    public $payload = [];
    public function __invoke($cfg) {
        return $cfg;
    }

    public function init($config) {
        $this->payload['DataGridCode'] = $config['DataGridCode'];
        $this->payload['user'] = $config['user'];
        $this->payload['role'] = $config['role'];
    }

    public function debug() {
        debug($this->payload);
    }


    public function getter() {
        unset($this->payload['total_cols_cfg']);
        unset($this->payload['formUsedCols']);
        return  $this->payload;
    }


    // 确保返回数组
    private function get_fixed_query_array($tmp) {
        if ($tmp['fixed_query_cfg'] == '') {
            return [];
        }
        if (is_null($tmp['fixed_query_cfg'])) {
            return [];
        }
        return   json_decode($tmp['fixed_query_cfg']);
    }

    public function setGridMeta() {
        $this->db->where('datagrid_code', $this->payload['DataGridCode']);
        $tmp = $this->db->get('nanx_activity')->row_array();
        $tmp['fixed_query_cfg']  = $this->get_fixed_query_array($tmp);
        $this->payload['DataGridMeta'] = $tmp;
        $this->payload['base_table'] = $tmp['base_table'];
        $this->payload['fixed_query_cfg'] =  $tmp['fixed_query_cfg'];
        $this->payload['datagrid_title'] = $tmp['datagrid_title'];
        $this->payload['service_fields'] = $tmp['service_fields'];
        $this->payload['tips'] = $tmp['tips'];
        $this->payload['referino'] = [];
        $this->payload['curd']['geturl'] = $tmp['service_url'];
        $this->payload['curd']['addurl'] = $tmp['addurl'];
        $this->payload['curd']['delurl'] = $tmp['delurl'];
        $this->payload['curd']['service_url'] = $tmp['service_url'];


        $this->payload['curd']['updateurl'] = $tmp['updateurl'];
    }


    public function setButtonCfg() {
        $btns = [];
        $sql = "select * from nanx_grid_button where datagrid_code='{$this->payload['DataGridCode']}' order by btnorder asc  ";
        $Buttons = $this->db->query($sql)->result_array();
        usort($Buttons, function ($item1, $item2) {
            if (empty($item1['btnorder'])) {
                $item1['btnorder'] = -1 * intval($item1['id']);
            }
            if (empty($item2['btnorder'])) {
                $item2['btnorder'] = -1 * intval($item2['id']);
            }
            return $item1['btnorder'] <=> $item2['btnorder'];
        });


        foreach ($Buttons as $one_btn) {
            $btns[] = $one_btn;
        }

        $specialbtns = $this->getSpecialButtons();
        foreach ($specialbtns as $value) {
            $btns[] = $value;
        }
        $this->payload['buttons'] =  $btns;
    }


    private function jsonAsTable() {


        $str = $this->payload['service_fields'];
        $cols = json_decode($str, true);


        $arr = [];
        foreach ($cols as  $oneCol) {

            $_tmp = ['field_e' => $oneCol, 'display_cfg' => ['field_c' => $oneCol]];
            $arr[] = $_tmp;
        }
        return $arr;
    }


    public function  setTotalColsCfg() {
        $this->payload['total_cols_cfg']  = $this->jsonAsTable();
    }

    public function  setColumnHiddenCols() {
        $this->payload['gridHiddenColumns'] =  $this->MFieldcfg->getForbiddenFields($this->payload['DataGridCode'], 'column_hidden');
    }

    public function  setFormHiddenCols() {
        $this->payload['formHiddenColumns'] =  $this->MFieldcfg->getForbiddenFields($this->payload['DataGridCode'], 'form_hidden');
    }

    public function getSpecialButtons() {
        return [];
    }


    public function setTableColumnRender() {
        $cols = [];
        foreach ($this->payload['total_cols_cfg']  as $col) {
            if (!in_array($col['field_e'], array_column($this->payload['gridHiddenColumns'], 'field'))) {
                $tmp = [];
                $tmp['key'] = $col['field_e'];
                $tmp['title'] = $col['display_cfg']['field_c'];
                if (array_key_exists('handler', $col['display_cfg'])) {
                    $tmp['handler'] = $col['display_cfg']['handler'];
                } else {
                    $tmp['handler'] = null;
                }
                $cols[] = $tmp;
            }
        }
        $this->payload['tableColumnConfig'] = $cols;
    }




    public function reorderColumns() {


        $gridcode = $this->payload['DataGridCode'];
        $sql = " select  distinct  datagrid_code, column_field  from nanx_activity_column_order where datagrid_code='$gridcode'  ";
        $Array_display_order = $this->db->query($sql)->result_array();
        $_sorted_all = $this->MFieldcfg->_sortFieldDisplayOrder($this->payload['total_cols_cfg'], $Array_display_order);
        $this->payload['total_cols_cfg'] = $_sorted_all;
    }


    public function setFormUsedColumns() {
        $cols = [];
        foreach ($this->payload['total_cols_cfg']  as $col) {
            if (!in_array($col['field_e'], array_column($this->payload['formHiddenColumns'], 'field'))) {
                $cols[] = $col;
            }
        }
        $this->payload['formUsedCols'] = $cols;
    }


    public function setUFormConfig() {
        $this->payload['formcfg'] = $this->transUniFormformCfg($this->payload['formUsedCols']);
    }


    public function transUniFormformCfg($cols) {

        $group_all = [];
        $group_all['type'] = 'object';
        $group_all['x-component'] = 'card';
        $group_all['properties'] = $this->toSchemaJson($cols);
        return  $group_all;
    }

    public function toSchemaJson($all_cols) {
        $ret = new stdClass();
        foreach ($all_cols as $col) {

            $tmp = [];
            $tmp['type'] = 'string';
            $tmp['title'] = $col['display_cfg']['field_c'];
            $tmp['required'] = false;
            $tmp['editable'] = true;
            $tmp['x-props'] =  null;
            $ret->{$col['field_e']} = $tmp;
        }
        return $ret;
    }


    public function getTriggerXprops($all_cols, $col) {

        $this_level = $col['editor_cfg']['trigger_cfg']['level'];
        $this_group_id = $col['editor_cfg']['trigger_cfg']['group_id'];
        $associate_field = $this->find_associate_field($all_cols, $this_level, $this_group_id);


        // 方便前台处理,所以重复
        $_tmp = [
            'field_id' => $col['field_e'],
            'uform_para' => $col['editor_cfg']['uform_para'],
            'level'                                       => $this_level,
            'api'                                         => 'curd/getTableData',
            'basetable'                                   => $col['editor_cfg']['trigger_cfg']['combo_table'],
            'filter_field'                                => $col['editor_cfg']['trigger_cfg']['filter_field'],
            'associate_field'                             => $associate_field,
            'trigger_group_uuid'                          => $this_group_id,
            'codetable_category_value' => $col['editor_cfg']['trigger_cfg']['codetable_category_value'],
            'label_field'                                 => $col['editor_cfg']['trigger_cfg']['list_field'],
            'value_field'                                 => $col['editor_cfg']['trigger_cfg']['value_field'],
            'as_select_field_id' => $col['field_e'],

        ];
        $xprops = ['trigger_cfg' => $_tmp];
        return $xprops;
    }



    public function find_associate_field($all_cols, $this_level, $this_group_id) {
        $associate_field = null;
        foreach ($all_cols as $col) {
            if (!empty($col['editor_cfg']['trigger_cfg'])) {
                if (
                    intval($col['editor_cfg']['trigger_cfg']['level']) == (intval($this_level) + 1)
                    && $this_group_id == $col['editor_cfg']['trigger_cfg']['group_id']
                ) {
                    $associate_field = $col['field_e'];
                }
            }
        }
        return $associate_field;
    }
}
