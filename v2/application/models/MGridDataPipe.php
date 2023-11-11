<?php

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;

class MGridDataPipe extends CI_Model implements StageInterface {

    public $payload = [];
    public function __invoke($cfg) {
        return $cfg;
    }

    public function init($config) {

        $this->payload['DataGridCode'] = $config['DataGridCode'];
        $this->payload['currentPage'] = $config['currentPage'];
        $this->payload['isFilterSelfData'] = $config['isFilterSelfData'];
        $this->payload['pageSize'] = $config['pageSize'];
        $this->payload['role'] = $config['role'];
        $this->payload['user'] = $config['user'];
        $this->payload['datagrid_type'] =  null;
        $this->payload['base_table'] =  null;
        $this->payload['_query_cfg'] = $config['query_cfg'];
        // 排序    缺省为倒序
        $this->payload['id_order'] =  'desc';

        // 下拉配置
        $this->payload['triggercfg'] = [];

        // query_cfg => where
        $this->payload['where_string'] =  '';


        // sql_base
        $this->payload['combo_fields'] = null;
        $this->payload['transformered_fields'] = null;
        $this->payload['joins'] = null;
        $this->payload['ghosts'] = null;

        $this->payload['sql_base'] =  '';
        $this->payload['sql_with_querycfg'] =  '';
        $this->payload['sql_with_author'] =  '';
        $this->payload['sql_quick'] =  '';
        $this->payload['sql_with_page'] =  '';

        // 记录总量
        $this->payload['total'] =  0;
        $this->payload['db_error_code'] =  0;
        $this->payload['db_error_message'] =  '';
        // 设置其余参数

        $this->db->where('datagrid_code', $this->payload['DataGridCode']);
        $tmp = $this->db->get('nanx_activity')->row_array();
        $this->payload['base_table'] = $tmp['base_table'];
        $this->payload['datagrid_type'] = $tmp['datagrid_type'];

        // 是否 author only
        $this->payload['is_author_only'] = $this->ifAuthorOnly();
    }


    public function setQueryCfg() {
        if (empty($this->payload['_query_cfg'])) {
            $this->payload['query_cfg'] = null;
        } else {
            $objectArray = $this->payload['_query_cfg'];
            // 将对象数组转换为 JSON 字符串
            $jsonString = json_encode($objectArray);
            // 再将 JSON 字符串解码为关联数组
            $tmp_lines = json_decode($jsonString, true);
            // 第一行的  and_or_0 设置为空格,作为修正
            $tmp_lines['0']['and_or'] = '';
            $this->payload['query_cfg']['lines'] =  $tmp_lines;
        }
    }



    public function debug() {
        header('Content-Type: application/json');
        echo json_encode($this->payload, JSON_PRETTY_PRINT);
    }


    public function ifAuthorOnly() {
        $all_fields = array_column($this->MRdbms->getTableColumnNames($this->payload['base_table']), 'Field');
        if (in_array('author', $all_fields)) {
            $this->payload['is_author_only'] = true;
        } else {
            $this->payload['is_author_only'] = false;
        }
    }

    public function setBasefields() {
        $this->load->model('MFieldcfg');
        $this->payload['base_fields']   = $this->db->list_fields($this->payload['base_table']);
    }


    public function setCommboFields() {
        $this->db->where(['actcode' => $this->payload['DataGridCode']]);
        $this->db->select('base_table,field_e,codetable_category_value,combo_table,list_field,value_field,filter_field,group_id,level');
        $combo_fields = $this->db->get('nanx_biz_column_trigger_group')->result_array();
        $this->payload['combo_fields'] = $combo_fields;
    }


    public function setTransformeredFields() {
        $transed_fields = [];
        $joins         = [];
        $ghosts        = [];


        // 对每个列,查找combo_fields,看是否需要替换为left join形式.
        $this->load->model('MFieldcfg');
        $base_fields  = $this->db->list_fields($this->payload['base_table']);

        foreach ($base_fields as $table_field) {
            $found           = $this->MFieldcfg->getLeftJoinObject($this->payload['base_table'], $table_field, $this->payload['combo_fields']);
            $transed_fields[] = $found['transed'];
            $ghosts[]        = $found['ghost'];
            $joins[]         = $found['join'];
        }

        $this->payload['transformered_fields'] = $transed_fields;
        $this->payload['joins'] = $joins;
        $this->payload['ghosts'] = $ghosts;
    }

    public function setSqlTransformered() {

        $field_list_str = '';
        $join_str       = '';
        $ghosts_str     = '';

        foreach ($this->payload['transformered_fields'] as $field) {
            $field_list_str .= $field . ",";
        }

        foreach ($this->payload['ghosts']  as $ghost) {
            if (strlen($ghost) > 10) {
                $ghosts_str .= $ghost . ",";
            }
        }

        $live_and_ghost = $field_list_str . $ghosts_str;
        $live_and_ghost = substr($live_and_ghost, 0, -1);
        foreach ($this->payload['joins']  as $join) {
            $join_str .= $join;
        }

        $sql = "select $live_and_ghost from  " . $this->payload['base_table'] . "  ";
        $sql_select = $sql . $join_str;
        $sql_select .= " order by {$this->payload['base_table']}.id   {$this->payload['id_order']}  ";
        $this->payload['sql_base'] = $sql_select;
    }


    public function setWhereString() {
        if (!$this->payload['query_cfg']) {
            $this->payload['where_string'] =  ' ';
            return;
        }

        $count = count($this->payload['query_cfg']['lines']);
        $lines   = $this->payload['query_cfg']['lines'];
        $where = '';
        for ($i = 0; $i < $count; $i++) {
            $and_or = $lines[$i]['and_or'];
            $operator = $lines[$i]['operator'];
            $wrapp_text = "'";  // 全部包装起来, 即使是数字类型的.
            $field_in_line = $lines[$i]['field'];
            $arg      = $lines[$i]['vset'];
            $pair = $this->getQueryPair($field_in_line, $arg);
            $where .= $and_or . ' (' . $pair['field'] . ' ' . $operator . ' ' . $wrapp_text .  $pair['argSTr'] . $wrapp_text . ') ';
        }
        $this->payload['where_string'] = $where;
    }


    public function getQueryPair($field_in_line, $arg) {

        $index = array_search($field_in_line, array_column($this->payload['combo_fields'], 'field_e'));
        // 无任何下拉选项
        if ($index === false) {
            return [
                'field' =>  $this->payload['base_table'] . "." . $field_in_line,
                'argSTr' => '%' . $arg . '%',
            ];
        }


        // 有下拉
        $triggercfg = $this->payload['combo_fields'][$index];

        // 如果是 code_table,category
        if ($triggercfg['combo_table'] == 'nanx_code_table') {
            $this->db->where(['category' => $triggercfg['codetable_category_value'], 'display_text' => $arg]);
            $code_found_row = $this->db->get('nanx_code_table')->row_array();
            $used_codetable_value   = $code_found_row['value'];
            return [
                'field' => $field_in_line,
                'argSTr' =>   '%' . $used_codetable_value . '%',
            ];
        }

        // 两表链接
        $pair = [
            'field' => $this->seekTransformered($triggercfg['combo_table'], $triggercfg['list_field']),
            'argSTr' =>  '%' . $arg . '%',
        ];
        return $pair;
    }




    public function setSqlWithQueryCfg() {

        if (!$this->payload['query_cfg']) {
            $this->payload['sql_with_querycfg'] =  $this->payload['sql_base'];
            return;
        }
        $fix   = 'where 1=1 and(';
        //bugfix: 不能只替换order,因为table name 或字段 可能包含order
        $sql = str_replace('order by', $fix . $this->payload['where_string'] . ') order by ', $this->payload['sql_base']);
        $this->payload['sql_with_querycfg'] = $sql;
    }


    public function setSqlWithAuthor() {

        $sql = $this->add_sql_author($this->payload['sql_with_querycfg'], $this->payload['base_table'],  $this->payload['user']);
        $this->payload['sql_with_author'] = $sql;
    }




    // 根据作者字段筛选数据.
    public function add_sql_author($sql, $table, $currentUser) {
        if ($currentUser == 'admin' ||  $currentUser == 'super') {
            return $sql;
        }
        if (!$this->payload['is_author_only']) {
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
        return substr_replace($sql, " $scopes_str ", $pointer, 0);
    }


    public function setSqlQuick() {

        $qucik = $this->QuickSql($this->payload['sql_with_author']);
        $this->payload['sql_quick'] = $qucik;
    }


    public function QuickSql($sql) {

        $arr_1 = explode(',', $sql);
        $arr_2 = explode(' from ', $sql);
        $quick_sql = $arr_1[0] . " from " . $arr_2[1];
        return $quick_sql;
    }

    public function setAllRows() {


        $all_rows = $this->db->query($this->payload['sql_quick'])->result_array();
        $this->payload['all_rows'] = $all_rows;
        $this->payload['total'] =  count($all_rows);
    }

    public function setRealRows() {

        $start = ($this->payload['currentPage'] - 1) * $this->payload['pageSize'];
        if ($this->payload['pageSize']  != "" || $this->payload['pageSize'] != null) {
            $sql   = $this->payload['sql_with_author'] . " limit {$start}, {$this->payload['pageSize']} ";
        }
        $rows          = $this->db->query($sql)->result_array();
        $this->payload['realrows']  = $rows;
        $this->payload['lastsql']   = $sql;
    }

    public function seekTransformered($table, $field) {

        $found = 'AAAA'; //如果出现就是错误了.
        foreach ($this->payload['transformered_fields']  as $one) {
            // one  的格式  table.column
            $talbe_point_column = explode('.', $one);
            $table_in_trans = $talbe_point_column[0];
            $column_in_trans = $talbe_point_column[1];
            if ((strpos($table_in_trans, $table) !== false) && (strpos($column_in_trans, $field) !== false)) {
                $found = $one;
                continue;
            }
        }
        $found_arr = explode(' ', $found);
        return $found_arr[0];
    }


    public function getter() {
        return  $this->payload;
    }
}
