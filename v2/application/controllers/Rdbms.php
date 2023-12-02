<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Rdbms extends MY_Controller {

    public function getTableColumnNames($table) {
        $sql = "show full fields  from $table";

        $fields = $this->db->query($sql)->result_array();
        return $fields;
    }


    public function adu() {
        $post = file_get_contents('php://input');
        $para_array = (array) json_decode($post);
        $optype = $para_array['optype'];

        if (('NANX_TBL_DATA' == $optype) || ('NANX_SYS_CONFIG' == $optype)) {
            $this->data_adu($para_array);
        }

        if ('NANX_TBL_STRU' == $optype) {
            $this->tb_structure_adu($para_array);
        }

        if ('NANX_TBL_INDEX' == $optype) {
            $this->tb_index_adu($para_array);
        }
    }

    public function data_adu($para) {
        $table = $para['table'];
        $a = $para['a'];
        $d = $para['d'];
        $u = $para['u'];

        $errs = array();
        for ($i = 0; $i < count($a); ++$i) {
            $a_row = (array) $a[$i];
            unset($a_row['id']);
            $this->db->insert($table, $a_row);
            $errmsg = $this->db->_error_message();
            $errno = $this->db->_error_number();
            if ($errno > 0) {
                array_push($errs, $errmsg);
            }
        }

        for ($i = 0; $i < count($d); ++$i) {
            $d_row = (array) $d[$i];
            $this->db->delete($table, array('id' => $d_row[0]));
            $errno = $this->db->_error_number();
            if ($errno > 0) {
                $errmsg = $this->db->_error_message();
                array_push($errs, $errmsg);
            }
        }

        for ($i = 0; $i < count($u); ++$i) {
            $u_row = (array) $u[$i];
            $id = $u_row['id'];
            $this->db->where('id', $id);
            $this->db->update($table, $u_row);
            $errno = $this->db->_error_number();
            if ($errno > 0) {
                $errmsg = $this->db->_error_message();
                array_push($errs, $errmsg);
            }
        }

        $msg = $this->lang->line('success_update_table_data');
        if (count($errs) > 0) {
            $msg = $errs;
        }

        $resp = array('success' => true, 'msg' => $msg);
        echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    }




    public function getAllBiztable() {
        $tables = $this->db->list_tables();
        $fixed = [];
        foreach ($tables as $one) {
            $fixed[] = ['value' => $one, 'text' => $one];
        }
        $ret = ['code' => 200, 'data' => $fixed];
        echo json_encode($ret);
    }

    public function getTableCols() {
        $post = file_get_contents('php://input');
        $para_array = (array) json_decode($post);
        $table = $para_array['table'];

        $fields = $this->getTableColumnNames($table);
        $ret = ['code' => 200, 'data' => $fields];
        echo json_encode($ret);
    }

    public function getAllPlugins() {

        $sql = "select  id,  plugname,plugid,memo  ,para_tpl from   nanx_ufrom_plugin_cfg";
        $ret = ['code' => 200, 'data' => $this->db->query($sql)->result_array()];
        echo json_encode($ret);
    }
}
