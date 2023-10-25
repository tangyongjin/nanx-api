<?php

class MRdbms extends CI_Model {

    function getTableColInfo($table) {
        $sql = " show full fields from $table ";
        $cols_all = $this->db->query($sql)->result_array();
        $col_info = array();
        $id = 0;
        foreach ($cols_all as $col) {
            $value_list = array_values($col);
            $col_obj = $this->getColumnDetail($value_list);
            $col_obj['id'] = $id;
            $id++;
            $col_info[] = $col_obj;
        }
        return $col_info;
    }





    function translateType($t) {
        switch (strtoupper($t)) {
            case 'STRING':
            case 'CHAR':
            case 'VARCHAR':
            case 'TINYBLOB':
            case 'TINYTEXT':
            case 'ENUM':
            case 'SET':
                return 'C';
            case 'TEXT':
            case 'LONGTEXT':
            case 'MEDIUMTEXT':
                return 'X';
            case 'IMAGE':
            case 'LONGBLOB':
            case 'BLOB':
            case 'MEDIUMBLOB':
            case 'BINARY':
                return 'B';
            case 'YEAR':
            case 'DATE':
                return 'D';
            case 'TIME':
            case 'DATETIME':
            case 'TIMESTAMP':
                return 'T';
            case 'INT':
            case 'INTEGER':
            case 'BIGINT':
            case 'TINYINT':
            case 'MEDIUMINT':
            case 'SMALLINT':
                return 'I';
            default:
                return 'N';
        }
    }



    function getTableColumnNames($table) {
        $fields = $this->db->query("show columns from $table")->result_array();
        return $fields;
    }



    function getColumnDetail($data) {


        $fld = array();
        $fld['field_name'] = $data[0];
        $type = $data[1];
        $fld['multi_set'] = array();
        $fld['scale'] = null;

        if (preg_match("/^(.+)\((\d+),(\d+)/", $type, $query_array)) {
            $fld['datatype'] = $query_array[1];
            $fld['length'] = is_numeric($query_array[2]) ? $query_array[2] : '';
            $fld['scale'] = is_numeric($query_array[3]) ? $query_array[3] : '';
        } elseif (preg_match("/^(.+)\((\d+)/", $type, $query_array)) {
            $fld['datatype'] = $query_array[1];
            $fld['length'] = is_numeric($query_array[2]) ? $query_array[2] : '';
        } elseif (preg_match("/^(enum|set)\((.*)\)$/i", $type, $query_array)) {
            $fld['datatype'] = $query_array[1];
            $arr = explode(",", $query_array[2]);
            $fld['enums'] = $arr;
            foreach ($arr as $val) {
                $new_val = trim($val, "'");
                $new_val = trim($new_val, '"');
                $fld['multi_set'][] = array($new_val);
            }
            $zlen = max(array_map("strlen", $arr)) - 2; // PHP >= 4.0.6
            $fld['length'] = ($zlen > 0) ? $zlen : 1;
        } else {
            $fld['datatype'] = $type;
            $fld['length'] = '';
        }
        $fld['not_null'] = ($data[3] != 'YES');
        $fld['primary_key'] = ($data[4] == 'PRI');
        $fld['unique_key'] = ($data[3] == 'UNI');
        $fld['auto_increment'] = (strpos($data[6], 'auto_increment') !== false);
        $fld['binary'] = (strpos($type, 'blob') !== false || strpos($type, 'binary') !== false);
        $fld['unsigned'] = (strpos($type, 'unsigned') !== false);
        $fld['zerofill'] = (strpos($type, 'zerofill') !== false);
        if (!$fld['binary']) {
            $d = $data[5];
            if ($d != '' && $d != 'NULL') {
                $fld['has_default'] = true;
                $fld['default_value'] = $d;
            } else {
                $fld['has_default'] = false;
            }
        }
        $fld['ctype'] = $this->translateType($fld['datatype']);

        $fld['comment'] = $data[8];
        if (!$fld['scale'] == null) {
            $fld['length'] = $fld['length'] . ',' . $fld['scale'];
        }
        return $fld;
    }




    public function getColNullInfo($base_table) {
        $col_info = $this->getTableColInfo($base_table);

        $col_null_set = array();
        foreach ($col_info as   $one_col_info) {
            $col_null_set[$one_col_info['field_name']] = $one_col_info['not_null'];
        }

        return $col_null_set;
    }


    public function fixNull($base_table, $rawData) {
        $null_cfg = $this->getColNullInfo($base_table);
        $columns = array_keys($rawData);
        foreach ($columns as $one_column) {
            //如果数据库里面定义为非空,并且前台传来的数据长度为0,则设置为null,让数据库报错.
            if ($null_cfg[$one_column] && 0 == strlen($rawData[$one_column])) {
                $rawData[$one_column] = null;
            }
        }
        return $rawData;
    }
}
