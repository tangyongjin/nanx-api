<?php

class MFieldcfg extends CI_Model {



    //all_db_fields 为所有字段, 获取所有的字段的配置,
    function getAllColsCfg($datagrid_code, $base_table, $all_db_fields) {
        $col_cfg = [];
        foreach ($all_db_fields as $db_field) {
            $tmp_cfg =  [];
            $tmp_cfg['field_e']     = $db_field['Field'];
            $tmp_cfg['display_cfg'] = $this->getDisplayCfg($datagrid_code, $db_field);
            $tmp_cfg['editor_cfg']  = $this->getEditorCfg($datagrid_code, $base_table, $db_field);
            $col_cfg[]              = $tmp_cfg;
        }
        return $col_cfg;
    }


    function getDisplayCfg($datagrid_code,  $db_field) {

        $title =  $db_field['Field'];
        $handler = null;

        $field_comment = $db_field['Comment'];
        if (strlen($field_comment) > 1) {
            $title = $field_comment;
        }

        $this->db->where(['datagrid_code' => $datagrid_code, 'field_e' => $db_field['Field']]);
        $row = $this->db->get('nanx_activity_field_special_display_cfg')->row_array();
        if ($row) {
            if (strlen($row['field_c']) > 0) {
                $title = $row['field_c'];
            }

            if (strlen($row['handler']) > 0) {
                $handler = $row['handler'];
            }
        }

        $display = ['field_c' => $title,  'handler' => $handler, 'show_as_pic' => "0"];
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
        return $common;
    }


    function getEditorCfg($datagrid_code, $base_table, $db_field) {
        $editor_cfg = $this->getCommonEditCfg($datagrid_code, $base_table, $db_field['Field']);
        if (strlen($editor_cfg['uform_plugin']) < 3) {
            // 没有找到 特别的 uform_plugin 配置,根据数据库类型来转换
            $field_mysql_type = $db_field['Type'];
            $editor_cfg['uform_plugin'] =  $this->toUnformType($field_mysql_type);
        }

        $editor_cfg['trigger_cfg'] = $this->getTriggerCfg($datagrid_code, $base_table, $db_field['Field']);
        $editor_cfg['uform_para'] = $editor_cfg['uform_para'];
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
        return ['join' => $join, 'transed' => $transed, 'ghost' => $ghost];
    }


    function toUnformType($field_type) {

        $uform_type = 'string';  // 即使找不到也缺省为 string

        $mysql_num_fields = [
            'bigint',
            'int',
            'float',
            'bit',
            'mediumint',
            'numeric',
            'real',
            'smallint',
            'tinyint',
            'decimal',
            'double',
            'year'
        ];

        $mysql_char_fields = [
            'char',
            'varchar',
        ];

        $mysql_text_fields = [
            'text',
            'tinytext',
            'longtext',
            'mediumtext',
        ];

        $mysql_datetime_fields = [
            'date',
            'datetime',
            'time',
            'timestamp',
        ];

        $mysql_blob_fields = [
            'binary',
            'blob',
            'bool',
            'boolean',
            'enum',
            'longblob',
            'mediumblob',
            'set',
            'tinybolb',
            'varbinary',
        ];

        if (in_array($field_type, $mysql_num_fields)) {
            $uform_type = 'number';
        }


        if (in_array($field_type, $mysql_char_fields)) {
            $uform_type = 'string';
        }

        if (in_array($field_type, $mysql_text_fields)) {
            $uform_type = 'textarea';
        }


        if (in_array($field_type, $mysql_datetime_fields)) {

            if ($field_type == 'date') {
                return 'UDateEditor';
            }

            if ($field_type == 'datetime') {
                return 'UDateTimeEditor';
            }

            if ($field_type == 'timestamp') {
                return 'UDateTimeEditor';
            }

            if ($field_type == 'time') {
                return 'UTimeEditor';
            }
            $uform_type = 'textarea';
        }

        if (in_array($field_type, $mysql_blob_fields)) {
            $uform_type = 'UBlobEditor';
        }

        return    $uform_type;
    }
}
