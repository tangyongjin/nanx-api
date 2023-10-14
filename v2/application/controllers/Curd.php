<?php




if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Curd extends MY_Controller {
    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin: * ');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With,Content-Type, Accept,authorization');
        header('Access-Control-Allow-Credentials', true);
        if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
            exit();
        }
    }




    public function listData() {

        $ret = [];
        $post = file_get_contents('php://input');
        $para = (array) json_decode($post);
        $user = $this->getUser();
        $this->load->model('MCurd');
        $base_table = $this->MCurd->getBasetable($para['DataGridCode']);
        $para['table'] = $base_table;
        $this->load->model('MCurd');
        $result = $this->MCurd->getActivityData($user, $para);
        $records = $result['rows'];
        $ret = [];
        $ret['code'] = 200;
        $ret['data'] = $records;
        $ret['total'] = (int) $result['total'];
        $ret['sql'] = $result['sql'];
        echo json_encode($ret);
    }




    public function addData() {
        $post = file_get_contents('php://input');
        $para = (array) json_decode($post);
        $actcode = $para['DataGridCode'];
        $act_table = $this->MCurd->getBasetable($actcode);

        //检查是否是流程相关的maintable.

        $user = $this->getUser();
        $flowInitMeta = [];
        $para['table'] = $act_table;
        $base_table = $para['table'];
        $rawData = (array) $para['rawdata'];
        $rawData = array_merge($rawData, $flowInitMeta);




        //前台送来的数据,把null改成''了.这里要重新转换下,用rdbms的not null 来约束数据
        $rawData_after_null_fix = $this->MRdbms->fixNull($base_table, $rawData);
        $this->db->insert($base_table, $rawData_after_null_fix);
        $new_inserted_row_id = $this->db->insert_id();
        $err = $this->db->error();
        $errno = $err['code'];
        $err_msg = $err['message'];


        if (0 == $errno) {
            $resp = array(
                'success' => true,
                'code'    => 200,
                'dbcode' => 0,
                'message'     => '数据添加成功'
            );
        } else {
            $resp = array(
                'success' => false,
                'code'    => 500,
                'dbcode' => $errno,
                'message'     =>  $errno . ' :' . $err_msg,
            );
        }

        $tranferd_resp = $this->transErrorMsg($base_table, $resp);
        $para['rawdata']->new_inserted_row_id = $new_inserted_row_id;
        echo json_encode($tranferd_resp);
    }

    public function updateData() {
        $post = file_get_contents('php://input');
        $para = (array) json_decode($post);
        $actcode = $para['DataGridCode'];
        $para['rawdata'] = $this->fix_ghost((array) $para['rawdata']);
        $para['table'] = $this->MCurd->getBasetable($actcode);
        $actcode = $para['DataGridCode'];
        $base_table = $para['table'];
        $rawData =  $para['rawdata'];
        $id = $rawData['id'];
        unset($rawData['id']);
        $this->db->where('id', $id);
        $row_to_update = $this->db->get($base_table)->result_array();

        if (1 == count($row_to_update)) {
            $row_to_update = $row_to_update[0];
        } else {
            $row_to_update = '';
        }

        $rawData_after_null_fix = $this->MRdbms->fixNull($base_table, $rawData);








        $this->db->where('id', $id);
        $sql_mode = " SET SQL_MODE='STRICT_ALL_TABLES' ";
        $this->db->query($sql_mode);
        $this->db->update($base_table, $rawData_after_null_fix);


        $err = $this->db->error();
        $errno = $err['code'];
        $err_msg = $err['message'];

        if (0 == $errno) {
            $resp = array(
                'success' => true,
                'code'    => 200,
                'dbcode' => $errno,
                'message'     =>  '修改成功'
            );
        } else {
            $resp = array(
                'success' => false,
                'code'    => 500,
                'dbcode'    => $errno,
                'message'     => $errno . $err_msg,
            );
        }

        $tranferd_resp = $this->transErrorMsg($base_table, $resp);
        $this->write_session_log('update', $para, $row_to_update);
        echo json_encode($tranferd_resp);
    }

    public function fix_ghost($arr) {
        $fixed = [];
        $keys = array_keys($arr);
        $ghosts = [];  //所有的ghost字段.

        //先拆分 arr,包含gohsot与不包含的.
        foreach ($keys as $key) {
            if (strpos($key, 'ghost_')   !== false) {
                $ghosts[$key] = $arr[$key];   //所有的ghosts
            } else {
                $fixed[$key] = $arr[$key];  // 不包含的
            }
        }
        return $fixed;
    }



    //翻译 字段不能为空的错误信息
    public function transErrorMsg($table, $resp) {


        //字段不能为空的错误
        if (1048 == $resp['dbcode']) {
            $words = explode("'", $resp['message']);
            $field = $words[1];
            $this->load->model('MFieldcfg');
            $col_cfg = $this->MFieldcfg->getDisplayCfg($table, $field, true);
            $field_c = $col_cfg['field_c']; //中文字段
            $tranferd_resp = $resp;
            $tranferd_resp['message'] = '字段[' . $field_c . ']不能为空';
            return $tranferd_resp;
        }

        if (1062 == $resp['dbcode']) {

            $words = explode("'", $resp['message']);
            $field = $words[1];
            $this->load->model('MFieldcfg');
            $col_cfg = $this->MFieldcfg->getDisplayCfg($table, $field, true);
            $field_c = $col_cfg['field_c']; //中文字段
            $tranferd_resp = $resp;
            $tranferd_resp['message'] =   $resp['message'];
            return $tranferd_resp;
        }


        return $resp;
    }




    public function deleteData() {
        $post = file_get_contents('php://input');
        $p = (array) json_decode($post);
        $para_for_hooks = array();
        $DataGridCode = $p['DataGridCode'];
        $base_table = $this->MCurd->getBasetable($DataGridCode);
        $p['table'] = $base_table;

        $para_for_hooks['table'] = $base_table;
        $ids = $p['selectedRowKeys']; // id like '1,23,4,9'

        $total_error = 0;
        $rows_deleted = array();
        foreach ($ids as $id) {
            $where = array(
                'id' => $id,
            );

            $this->db->where($where);
            $row_to_del_query = $this->db->get($base_table);
            $row_to_del = $row_to_del_query->result_array();

            if (1 == count($row_to_del)) {
                array_push($rows_deleted, $row_to_del[0]);
            }


            $this->db->delete($base_table, $where); //删除数据
            $err = $this->db->error();
            $errno = $err['code'];
            $total_error = $total_error + $errno;
            $para_for_hooks['id_to_del'] = $id;
            $para_for_hooks['row'] = $row_to_del[0];
        }

        if (0 == $total_error) {
            $resp = array(
                'code' => 200,
                'success' => true,
                'message'     => '删除成功'
            );
        } else {
            $resp = array(
                'code' => 500,
                'success' => false,
                'message'     => '删除失败'
            );
        }

        $this->write_session_log('delete', $p, $rows_deleted);
        echo json_encode($resp);
    }


    public function write_session_log($type, $para, $old_data) {
        if (!array_key_exists('rawdata', $para)) {
            $para['rawdata'] = $object = new stdClass();
        }
        $operator = $this->getUser();
        $user = $this->getUser();
        $log_data = array(
            'user'       => $operator . '[' . $user . ']',
            'action_cmd' => $type,
            'ts' => date("Y-m-d H:i:s", time()),
            'datagrid_code'   => $para['DataGridCode'],
            'table'      => $para['table'],
            'rawdata'    => json_encode((array) $para['rawdata']),
        );

        switch ($type) {
            case 'add':
                $log_data['ids'] = '';

                break;
            case 'delete':
                $log_data['ids'] = json_encode($para['selectedRowKeys']);
                $log_data['old_data'] = json_encode($old_data);
                break;

            case 'update':
                $_tmp = (array) $para['rawdata'];
                $log_data['ids'] = $_tmp['id'];
                $log_data['old_data'] = json_encode($old_data);
                break;
            case 'batchUpdate':
                $log_data['ids'] = json_encode($para['batch_ids']);
                break;
        }
        $this->db->insert('nanx_session_log', $log_data);
    }





    public function getTableData() {

        $post = file_get_contents('php://input');
        $para = (array) json_decode($post);
        $basetable = $para['basetable'];
        if ($basetable == "nanx_activity") {
            $sql = "select * from nanx_activity order by datagrid_code ";
            $rows = $this->db->query($sql)->result_array();
            $ret = [];
            $ret['data'] = $rows;
            $ret['code'] = 200;
            echo json_encode($ret);
            die;
        }
        $filter_field = $para['filter_field'];
        if (1 == $para['level']) {
            if ($basetable == "nanx_code_table") {
                $this->db->where('category', $para['codetable_category_value']);
            }
        } else {
            $filter_value = $para['value_field'];
            $this->db->where($filter_field, $filter_value);
        }

        $rows = $this->db->get($basetable)->result_array();
        $ret['data'] = $rows;
        $ret['code'] = 200;
        echo json_encode($ret);
    }
}
