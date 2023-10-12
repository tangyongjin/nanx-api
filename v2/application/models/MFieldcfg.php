<?php

class MFieldcfg extends CI_Model {

    function getMysqlDataTypes($range) {
        $data_types = array(
            'bigint',
            'binary',
            'bit',
            'blob',
            'bool',
            'boolean',
            'char',
            'date',
            'datetime',
            'decimal',
            'double',
            'enum',
            'float',
            'int',
            'longblob',
            'longtext',
            'mediumblob',
            'mediumint',
            'mediumtext',
            'numeric',
            'real',
            'set',
            'smallint',
            'text',
            'time',
            'timestamp',
            'tinybolb',
            'tinyint',
            'tinytext',
            'varbinary',
            'varchar',
            'year'
        );


        $data_types_have_length = array(
            'bigint',
            'binary',
            'bit',
            'blob',
            'bool',
            'boolean',
            'char',
            'date',
            'datetime',
            'decimal',
            'double',
            'enum',
            'float',
            'int',
            'longblob',
            'longtext',
            'mediumblob',
            'mediumint',
            'mediumtext',
            'numeric',
            'real',
            'set',
            'smallint',
            'text',
            'time',
            'timestamp',
            'tinybolb',
            'tinyint',
            'tinytext',
            'varbinary',
            'varchar',
            'year'
        );


        $data_types_need_wrapper = array(
            'char',
            'date',
            'datetime',
            'text',
            'time',
            'timestamp',
            'varchar'
        );



        if ($range == 'all') {
            return $data_types;
        }
        if ($range == 'have_length') {
            return $data_types;
        }
        if ($range == 'wrap') {
            return $data_types_need_wrapper;
        }
    }


    //all_db_fields 为所有字段, 获取所有的字段的配置,
    function getAllColsCfg($datagrid_code, $base_table, $all_db_fields, $transfer) {
        $col_cfg = array();
        $tmp_cfg = array();


        foreach ($all_db_fields as $db_field) {
            $display_cfg            = $this->getDisplayCfg($datagrid_code, $base_table, $db_field, $transfer);
            $editor_cfg             = $this->getEditorCfg($datagrid_code, $base_table, $db_field);
            $tmp_cfg['field_e']     = $db_field['Field'];
            $tmp_cfg['display_cfg'] = $display_cfg;
            $tmp_cfg['editor_cfg']  = $editor_cfg;
            $col_cfg[]              = $tmp_cfg;
        }

        return $col_cfg;
    }


    //transfer = 是否需要转换
    function getDisplayCfg($datagrid_code, $base_table, $db_field, $transfer) {
        if (!$transfer) {
            return array(
                'field_c' => $db_field['Field'],
                'value' => $db_field['Field'],
                'width' => 200,
                'show_as_pic' => 0
            );
        }

        // 先根据 datagrid_code +  field_e 查询

        $this->db->where(['datagrid_code' => $datagrid_code, 'field_e' => $db_field['Field']]);
        $this->db->select(['field_c', 'field_width', 'label_width', 'show_as_pic']);
        $row = $this->db->get('nanx_activity_field_special_display_cfg')->first_row('array');
        if ($row) {
            if (strlen($row['field_c']) == 0) {
                $row['field_c'] = $db_field['Field'];
            }
            // $row['value'] = $db_field;
            return $row;
        }


        $field_comment = $db_field['Comment'];

        if (strlen($field_comment) > 1) {
            $field_c = $field_comment;
        } else {
            $field_c = $db_field['Field'];
        }


        $display = array(
            'field_c' => $field_c,
            'value' => $db_field['Field'],
            'width' => 200,
            'show_as_pic' => "0"
        );
        return $display;
    }


    function getTriggerCfg($datagrid_code, $base_table, $field) {
        $where = array(
            'actcode' => $datagrid_code,
            'base_table' => $base_table,
            'field_e' => $field
        );


        $this->db->where($where);
        $this->db->select('combo_table,codetable_category_value,list_field,value_field,filter_field,group_id,level');
        $dropdown_from_table = $this->db->get('nanx_biz_column_trigger_group')->first_row('array');
        return  $dropdown_from_table;
    }


    function getCommonEditCfg($datagrid_code, $base_table, $field) {
        $w1 = array(
            'datagrid_code' => $datagrid_code,
            'base_table' => $base_table,
            'field_e' => $field
        );

        $this->db->where($w1);
        $common = $this->db->get('nanx_biz_column_editor_cfg')->row_array();
        // first_row('array');


        if (empty($common)) {
            $common          = array();
            $common['found'] = false;
            $common['uform_plugin'] = '';
            $common['uform_para'] = '';
            unset($common['id']);
        } else {
            unset($common['base_table']);
            unset($common['field_e']);
            $common['uform_plugin'] = $common['uform_plugin'];
            $common['uform_para'] = $common['uform_para'];
            $common['found'] = true;
        }


        $common['rowbasevalue'] = null;
        return $common;
    }

    // function getFollowCfg($base_table, $field)
    // {

    //     $where = array(
    //         'base_table' => $base_table,
    //         'field_e' => $field
    //     );

    //     $this->db->where($where);
    //     $this->db->select('base_table,field_e,combo_table,combo_table_value_field,base_table_follow_field,combo_table_follow_field');
    //     $follow_cfg = $this->db->get('nanx_biz_column_follow_cfg')->result_array();
    //     return $follow_cfg;
    // }

    function getEditorCfg($datagrid_code, $base_table, $db_field) {


        $editor_cfg = $this->getCommonEditCfg($datagrid_code, $base_table, $db_field['Field']);
        if (strlen($editor_cfg['uform_plugin']) < 3) {
            // 没有找到 特别的 uform_plugin 配置,根据数据库类型来转换
            $field_mysql_type = $db_field['Type'];
            $editor_cfg['uform_plugin'] =  $this->toUnformType($field_mysql_type);
        }


        $trigger_cfg               = $this->getTriggerCfg($datagrid_code, $base_table, $db_field['Field']);
        $editor_cfg['trigger_cfg'] = $trigger_cfg;
        $editor_cfg['uform_para'] = $editor_cfg['uform_para'];
        // $editor_cfg['follow_cfg']  = $follow_cfg;
        $editor_cfg['null_option'] = $db_field['Null'];
        return $editor_cfg;
    }




    // 所有的隐藏字段,包括 form_hidden / column_hidden
    function getForbiddenFields($activty_code, $type = null) {

        if ($type === null) {
            $this->db->where(['datagrid_code' => $activty_code]);
            $rows             = $this->db->get('nanx_activity_forbidden_field')->result_array();
            return $rows;
        } else {
            $this->db->where(['datagrid_code' => $activty_code, 'forbidden_type' => $type]);
            $rows             = $this->db->get('nanx_activity_forbidden_field')->result_array();
            return $rows;
        }
    }

    function getLeftJoinObject($basetable, $field, $combo_fileds) {

        if ($field == 'id') {
            $transed = $basetable . "." . $field;
            $join    = '';
            return array(
                'join' => '',
                'transed' => $transed,
                'ghost' => ''
            );
        }

        //根据combo方式,挨个对table_field进行检查,看是否需要进行join连接.
        //如果combo_fields为空,则直接返回field.
        if (!$combo_fileds) {
            $transed = $basetable . "." . $field;
            $join    = '';
            $ghost   = '';
        }

        //如果combo_fields不为空,则检查,找到则返回转后的,否则直接直接返回field.
        foreach ($combo_fileds as $key => $combo_4meta) {
            if ($field == $combo_4meta['field_e']) {

                //表别名
                $tb_alias = $combo_4meta['combo_table'] . "_$key";
                $transed = $tb_alias . "." . $combo_4meta['list_field'] . " as " . $combo_4meta['field_e'];
                $join = " left join {$combo_4meta['combo_table']} $tb_alias on {$tb_alias}.{$combo_4meta['value_field']}=$basetable.$field ";

                // fix  字典表
                if ('nanx_code_table' == $combo_4meta['combo_table']) {
                    $join .= " and {$tb_alias}.category='{$combo_4meta['codetable_category_value']}' ";
                }

                $ghost = " $basetable.$field  as ghost_$field";
                break;
            } else {
                $join    = '';
                $transed = $basetable . "." . $field;
                $ghost   = '';
            }
        }

        reset($combo_fileds);
        return array(
            'join' => $join,
            'transed' => $transed,
            'ghost' => $ghost
        );
    }






















    function toUnformType($field_type) {

        $data_types = $this->getMysqlDataTypes('all');  //所有的mysql类型.

        $mysql_type_found = 'char';  // 即使找不到也缺省为 char

        foreach ($data_types as $mysql_type) {
            if (strpos($field_type, $mysql_type)   !== false) {
                $mysql_type_found = $mysql_type;
                continue;
            }
        }

        $uform_type = $this->convertToUformType($mysql_type_found);
        return    $uform_type;
    }






    function convertToUformType($field_type) {
        switch (strtoupper($field_type)) {
            case 'STRING':
            case 'CHAR':
            case 'VARCHAR':
            case 'TINYBLOB':
            case 'TINYTEXT':
            case 'ENUM':
            case 'SET':
                return 'string';    //通用的字符串组件
            case 'TEXT':
            case 'LONGTEXT':
            case 'MEDIUMTEXT':
                return 'text_area';  //uform封装的textarea

            case 'DATE':
            case 'TIME':
            case 'DATETIME':
            case 'TIMESTAMP':
                return 'date';  //date	年月日输入组件
            case 'INT':
            case 'INTEGER':
            case 'BIGINT':
            case 'TINYINT':
            case 'MEDIUMINT':
            case 'DECIMAL':
            case 'DOUBLE':
            case 'SMALLINT':
            case 'YEAR':
            case 'FLOAT':
            case 'REAL':
                return 'number';  //整型输入组件
            default:
                return 'string';
        }
    }
}
