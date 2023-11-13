<?php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Log extends CI_Controller {
    function index() {



        $base_url = str_replace('log', '', $_SERVER['REQUEST_SCHEME']   . '://' . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI']);
        $base_url = str_replace('Log', '',  $base_url);


        ini_set('display_errors', 1);
        ini_set('display_startup_errors', 1);
        error_reporting(E_ALL);
        $this->load->helper('file');
        $jstr = '<script src="' .  $base_url . 'js/jquery/jquery-1.7.1.min.js" type="text/javascript" charset="utf-8"></script>';
        $jstr .= '<script src="' . $base_url . 'js/log.js" type="text/javascript" charset="utf-8"></script>';
        $css     = $base_url . "css/log.css";
        $css_str = "<link rel='stylesheet' href=$css>";

        $fontcss = $base_url . "css/font-awesome-4.7.0/css/font-awesome.min.css";
        $fontcss_str = "<link rel='stylesheet' href=$fontcss>";

        echo '<html><head><meta http-equiv="content-type" content="text/html;charset=utf-8">' . $jstr . $css_str . $fontcss_str . '<title>API日志</title></head>';
        echo "<body><div>";
        echo "<input onclick=clear_log() type=button value=Clear_log name=Hide_Input>";
        echo "</div>";

        $logfile = helper_getlogname();
        if (file_exists($logfile)) {
            $string = read_file(helper_getlogname());
        } else {
            $string = '';
        }

        $php_errmsg = '<h2>PHP_error_logfile: (/tmp/php_errors.log)</h2>' . read_file('/tmp/php_errors.log');
        echo "<pre>" . $php_errmsg . "</pre>";
        echo "<h2>app_log_file: $logfile </h2>";
        echo "<br/>";
        echo "<pre>" . $string . "</pre>";
        echo '
        <div id="menu">
                    <ul>
                        <li>
                        <a href="javascript:gotop();"><i class="fa fa-arrow-circle-up"></i></a>
                        </li>
                        <li>
                        <a href="javascript:gobottom();"><i class="fa fa-arrow-circle-down"></i></a>
                         </li>
                    </ul>
                </div>
               ';
        echo "</body></html>";
    }


    public function clearlog() {
        file_put_contents(helper_getlogname(), '');
    }
}
