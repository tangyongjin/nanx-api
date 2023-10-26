<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class Codecleaner extends CI_Controller {
    public function __construct() {
        parent::__construct();
        header('Access-Control-Allow-Origin:  *');
        header('Access-Control-Allow-Methods: * ');
        header('Access-Control-Allow-Headers: Origin, X-Requested-With,Content-Type, Accept,authorization');
        header('Access-Control-Allow-Credentials', true);
        if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
            exit();
        }
    }

    public function index() {

        echo 'index';
    }

    public function model_and_methods() {

        $this->load->library('modellist');
        $model_funcs = $this->modellist->getModels();
        $models = array_keys($model_funcs);
        $cms = [];
        foreach ($models as $model) {
            $methods = $model_funcs[$model];
            foreach ($methods as $onemethod) {
                $tmp = $model . '/' . $onemethod;
                $cms[] = $tmp;
            }
        }
        sort($cms);

        echo json_encode($cms, JSON_UNESCAPED_UNICODE);
    }

    public function controll_and_methods() {

        $this->load->library('controllerlist');

        $controllersArray = $this->controllerlist->getControllers();
        $controllers = array_keys($controllersArray);

        // debug($controllersArray);
        // die;

        // $controllers = ['DataGridCfg'];
        // $controllers = ['Curd'];


        $cms = [];
        foreach ($controllers as $controller) {
            $methods = $controllersArray[$controller];

            // debug($methods);
            foreach ($methods as $onemethod) {
                $tmp =   $controller . '/' . $onemethod;
                $cms[] = $tmp;
            }
        }
        sort($cms);
        echo json_encode($cms, JSON_UNESCAPED_UNICODE);
    }

    public function sesrviceused_model() {
        // $sql = "
        // select distinct  serviceurl as url ,'boss_flow_referenceinfo_cfg' as source   
        // from boss_flow_referenceinfo_cfg where reftype='service' 
        // union 
        // select service_url as url,'boss_process_node_handler' as source  from boss_process_node_handler 
        // union
        // select  distinct data_source as url ,'boss_res_item_ui' as source from boss_res_item_ui ";
        // $rows = $this->db->query($sql)->result_array();
        // $rows = array_retrieve($rows, 'url');

        // $fixed = [];
        // foreach ($rows as $one) {
        //     if (!empty($one)) {
        //         // 防止出现 Model/Methond/QueryStr 这种格式.
        //         $tmparr = explode('/', $one);
        //         $ModelAndMethod = $tmparr[0] . "/" . $tmparr[1];

        //         $fixed[] = $ModelAndMethod;
        //     }
        // }
        $fixed = [];
        echo json_encode($fixed, JSON_UNESCAPED_UNICODE);
    }

    public function sesrviceused_controller() {
        $ret = [];
        $sql = 'select distinct  geturl as url  from nanx_activity  ';
        $rows3 = $this->db->query($sql)->result_array();

        $sql = 'select distinct  delurl as url  from nanx_activity  ';
        $rows4 = $this->db->query($sql)->result_array();

        $sql = 'select distinct  addurl as url  from nanx_activity  ';
        $rows5 = $this->db->query($sql)->result_array();
        $sql = 'select distinct  updateurl as url  from nanx_activity  ';
        $rows6 = $this->db->query($sql)->result_array();
        $all = array_merge($rows3, $rows4, $rows5, $rows6);
        $service_urls = array_unique(array_values(array_column($all, 'url')));
        $realurls = [];
        foreach ($service_urls as $key => $one) {
            if ($one != 'x' && $one != 'null' && $one !== null && strpos($one, '@java') === false) {
                $realurls[] = ucfirst($one);
            }
        }

        echo json_encode($realurls, JSON_UNESCAPED_UNICODE);
    }


    public function searchField() {
        $searchStr = $_GET['searchStr'];
        if (empty($searchStr)) {
            echo json_encode(['usage' => [], 'code' => 0]);
            exit;
        }

        $search_tables = [
            'nanx_activity',
            'nanx_activity_a2a_btns',
            'nanx_activity_biz_layout',
            'nanx_activity_column_order',
            'nanx_activity_field_public_display_cfg',
            'nanx_activity_field_special_display_cfg',
            'nanx_activity_forbidden_field',
            'nanx_activity_js_btns',
            'nanx_biz_column_editor_cfg',
            'nanx_biz_column_follow_cfg',
            'nanx_biz_column_trigger_group',
            'boss_flow_referenceinfo_cfg',
            'boss_flow_add_cfg',
            'nanx_code_table',
            'boss_flow_timeline',
            'nanx_portal_button',
            'boss_portal_button_special',
            'boss_process_node_handler',
            'boss_res_item_ui',
            'nanx_portal_menu_list',
        ];

        $usage = [];
        foreach ($search_tables as $tb) {
            $sql = "select * from $tb";
            $rows = $this->db->query($sql)->result_array();
            // $rowsjson = json_encode($rows);

            $rowsjson = serialize($rows);
            // debug($rowsjson);
            // die;
            // debug($rowsjson);
            // die;


            if (strpos($rowsjson, $searchStr) !== FALSE) {
                // $usage['table'] = $tb;
                $ids = [];
                foreach ($rows as $row) {
                    $rowjson = json_encode($row);
                    if (strpos($rowjson, $searchStr) !== FALSE) {

                        $ids[] = $row['id'];
                    }
                }
                $ids_str = array_to_string($ids);
                $sql = " select * from $tb where id in ( $ids_str ) ; ";
                $usage[] = ['searchStr' => $searchStr,  'table' => $tb, 'ids' => $ids, 'sql' => $sql];
            }
        }

        // //增加直接打印sql语句
        // foreach ($result as $key => $value) {
        //     $data .= $search_f . " 出现在 <span style=color:red>{$key}</span> 表,id: <span style=color:red>" . implode(',', $value) . '</span><br/>';
        //     $data .= 'select * from ' . $key . ' where id  in (' . implode(',', $value) . ')';
        // }
        echo json_encode(['usage' => $usage]);
        exit;
    }
}
