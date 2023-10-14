<?php

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;

class MDataGridCfgExecutor extends CI_Model implements StageInterface {
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
        return  $this->payload;
    }


    public function setGridMeta() {
        $this->db->where('datagrid_code', $this->payload['DataGridCode']);
        $tmp = $this->db->get('nanx_activity')->row_array();
        $this->payload['DataGridMeta'] = $tmp;
        $this->payload['base_table'] = $tmp['base_table'];
        $this->payload['fixed_query_cfg'] = $tmp['fixed_query_cfg'];
        $this->payload['layoutcfg'] = $tmp['layoutcfg'];
        $this->payload['form_title'] = $tmp['datagrid_title'];
        $this->payload['tips'] = $tmp['tips'];
        $this->payload['referino'] = [];
        $this->payload['curd']['geturl'] =  $tmp['geturl'];
        $this->payload['curd']['addurl'] = $tmp['addurl'];
        $this->payload['curd']['delurl'] = $tmp['delurl'];
        $this->payload['curd']['updateurl'] = $tmp['updateurl'];
    }


    public function setButtonCfg() {
        $btns = [];
        $sql = "select * from nanx_grid_button where datagrid_code='{$this->payload['DataGridCode']}' order by btnorder asc  ";
        $notStandardButtons = $this->db->query($sql)->result_array();
        usort($notStandardButtons, function ($item1, $item2) {
            if (empty($item1['btnorder'])) {
                $item1['btnorder'] = -1 * intval($item1['id']);
            }
            if (empty($item2['btnorder'])) {
                $item2['btnorder'] = -1 * intval($item2['id']);
            }
            return $item1['btnorder'] <=> $item2['btnorder'];
        });


        foreach ($notStandardButtons as $one_btn) {
            $btns[] = $one_btn;
        }

        $specialbtns = $this->getSpecialButtons();
        foreach ($specialbtns as $value) {
            $btns[] = $value;
        }
        $this->payload['notStandardButtonConfig'] =  $btns;
    }



    public function  setTotalColsCfg() {

        $datagrid_code =    $this->payload['DataGridCode'];
        $base_table =   $this->payload['base_table'];
        $all_db_fields =    $this->db->query("show full fields  from $base_table")->result_array();
        $this->payload['total_cols_cfg']  = $this->MFieldcfg->getAllColsCfg($datagrid_code, $base_table, $all_db_fields, true);
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


    public function setTableColumnConfig() {
        $cols = [];
        foreach ($this->payload['total_cols_cfg']  as $col) {
            if (!in_array($col['field_e'], array_column($this->payload['gridHiddenColumns'], 'field'))) {
                $tmp = [];
                $tmp['key'] = $col['field_e'];
                $tmp['title'] = $col['display_cfg']['field_c'];
                $cols[] = $tmp;
            }
        }
        $this->payload['tableColumnConfig'] = $cols;
    }


    public function setUFormConfig() {
        $this->payload['formcfg'] = $this->transUniFormformCfg($this->payload['fmsCfg']);
    }


    public function setFormColumnsConfig() {
        $cols = [];
        foreach ($this->payload['total_cols_cfg']  as $col) {
            if (!in_array($col['field_e'], array_column($this->payload['formHiddenColumns'], 'field'))) {
                $cols[] = $col;
            }
        }
        $this->payload['fmsCfg'] = $cols;
    }



    public function transUniFormformCfg($cols) {
        $ret = [];
        $ret['data'] = [];
        $ret['data']['type'] = 'object';
        $ret['data']['properties'] = [];
        $group_all = [];
        $group_all['type'] = 'object';
        $group_all['x-component'] = 'card';
        $group_all['properties'] = $this->toSchemaJson($cols);
        $ret['data']['properties']['group_all'] =    $group_all;
        return $ret['data'];
    }

    public function toSchemaJson($all_cols) {
        $ret = new stdClass();
        foreach ($all_cols as $col) {
            //skip 流程主表的公共字段
            if (in_array($col['field_e'],  [])) {
                continue;
            }
            $tmp = [];
            $tmp['grouptitle'] = '';
            $tmp['type'] = $col['editor_cfg']['uform_plugin'];
            $tmp['title'] = $col['display_cfg']['field_c'];
            $tmp['required'] = $col['editor_cfg']['null_option']  == 'NO' ? true : false;    //是否必填项
            $tmp['x-visible'] = true;   // 是否可见 
            $common_plugins = ['string', 'date', 'number'];
            if (!(in_array($col['editor_cfg']['uform_plugin'], $common_plugins))) {
                $col['editor_cfg']['trigger_cfg'] = null;
            }


            if (!empty($col['editor_cfg']['trigger_cfg'])) {
                $tmp['enum'] = [];  //控制前台出现 Select 
                $tmp['type'] = 'Assocselect';   //强制指定下.
                $tmp['x-component'] =   'Assocselect';
                $xprops = $this->getTriggerXprops($all_cols, $col);
                $xprops['uform_para'] = $col['editor_cfg']['uform_para'];
                $tmp['x-props'] = $xprops;
            } else {
                $tmp['x-props'] = [
                    'field_id' => $col['field_e'], 'trigger_style' => 'no_trigger',
                    'uform_para' => $col['editor_cfg']['uform_para']
                ];
            }

            if ($tmp['type'] == 'TableEditor') {
                $tmp['x-props'] = ['datagrid_code' => $col['editor_cfg']['uform_para']];
            }



            if (array_key_exists('readonly', $col['editor_cfg'])) {

                if (intval($col['editor_cfg']['readonly']) == 1) {
                    $tmp['x-props']['editable'] = false;
                } else {
                    $tmp['x-props']['editable'] = true;
                }
            }


            $ret->{$col['field_e']} = $tmp;
        }
        $ret = $this->combineByGroup($ret);
        return $ret;
    }

    public function getTriggerXprops($all_cols, $col) {



        $this_level = $col['editor_cfg']['trigger_cfg']['level'];
        $this_group_id = $col['editor_cfg']['trigger_cfg']['group_id'];
        $associate_field = $this->find_associate_field($all_cols, $this_level, $this_group_id);


        // 方便前台处理,所以重复
        $_tmp = [
            'level'                                       => $this_level,
            'api'                                         => 'curd/getTableData',
            'basetable'                                   => $col['editor_cfg']['trigger_cfg']['combo_table'],
            'filter_field'                                => $col['editor_cfg']['trigger_cfg']['filter_field'],
            'associate_field'                             => $associate_field,
            'trigger_group_uuid'                          => $this_group_id,
            'codetable_category_value' => $col['editor_cfg']['trigger_cfg']['codetable_category_value'],
            'label_field'                                 => $col['editor_cfg']['trigger_cfg']['list_field'],
            'value_field'                                 => $col['editor_cfg']['trigger_cfg']['value_field'],

        ];

        $xprops = [
            'level'                                       => $this_level,
            'api'                                         => 'curd/getTableData',
            'basetable'                                   => $col['editor_cfg']['trigger_cfg']['combo_table'],
            'filter_field'                                => $col['editor_cfg']['trigger_cfg']['filter_field'],
            'associate_field'                             => $associate_field,
            'trigger_group_uuid'                          => $this_group_id,
            'codetable_category_value' => $col['editor_cfg']['trigger_cfg']['codetable_category_value'],
            'label_field'                                 => $col['editor_cfg']['trigger_cfg']['list_field'],
            'value_field'                                 => $col['editor_cfg']['trigger_cfg']['value_field'],
            'query_cfg' => $_tmp,
            'ass_select_field_id' => $col['field_e']

        ];
        return $xprops;
    }

    public function combineByGroup($all_cols) {
        $grouped_ret = new stdClass();
        $all_titles = array_column((array) $all_cols, 'grouptitle');
        $all_titles_unique = array_unique($all_titles);
        $i = 0;
        foreach ($all_titles_unique as  $title) {
            $un_key = 'UFORM_NO_NAME_FIELD' . $i;
            $small_group = [];
            foreach ($all_cols  as  $key => $one_big) {
                if ($one_big['grouptitle']  ==   $title) {
                    $small_group[$key] = $one_big;
                }
            }
            $grouped_ret->$un_key  = ['type' => 'object', 'x-component' => 'card', 'x-props' => ['title' => $title], 'properties' => $small_group];
            $i++;
        }
        return $grouped_ret;
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
