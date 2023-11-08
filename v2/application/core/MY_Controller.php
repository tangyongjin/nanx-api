<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(-1);
defined('BASEPATH') or exit('No direct script access allowed');

register_shutdown_function('my_shutdownHandler');
set_error_handler('my_errorHandler');
set_exception_handler('my_exceptionHandler');


function my_errorHandler($errno, $errstr, $errfile, $errline) {
  if (strlen($errstr) > 0) {
    logtext("错误代码:" . $errno . ",错误信息:" . $errstr . " in $errfile line $errline <br/>");
    response500("错误代码:" . $errno . ",错误信息:" . $errfile . ' ' . $errline . ' ' . $errstr);
  }
  return true;
}

function  my_exceptionHandler(Throwable $exception) {
  my_errorHandler('Exception', $exception->getMessage(),  $exception->getFile(), $exception->getLine());
};


function  my_shutdownHandler() {
  $last_error = error_get_last();
  my_errorHandler($last_error['type'], $last_error['message'], $last_error['file'], $last_error['line']);
};

class MY_Controller extends CI_Controller {
  private $user;
  private $roles;
  private $args = null;
  private $custid;
  private $user_mobile;


  public function __construct() {
    header('Access-Control-Allow-Origin:  *');
    header('Access-Control-Allow-Methods: * ');
    header('Access-Control-Max-Age: 1728000');
    header("Access-Control-Allow-Headers: Origin,Cache-Control,Access-Control-Allow-Origin,X-Requested-With,Content-Type, Accept,authorization");
    header('Access-Control-Allow-Credentials', true);
    error_reporting(E_ALL);
    parent::__construct();
    include_once 'application/controllers/Global_vars.php';
    $this->load->helper('my_jwt_helper');

    if ('OPTIONS' == $_SERVER['REQUEST_METHOD']) {
      exit();
    }

    $allheaders = getallheaders();
    $controller = $this->router->fetch_class();
    $method = $this->router->fetch_method();


    $post = file_get_contents('php://input');
    $http_type = $_SERVER['REQUEST_METHOD'];
    $this->write_access_log($controller, $method, $allheaders, $_REQUEST, $post);


    $this->setArgs($http_type, $_REQUEST, $post);
    $checkSession = $this->checkAuthToken($allheaders, $controller, $method);

    if (!$checkSession) {
      $this->logout();
    }
  }


  private function setArgs($http_type, $req, $post) {
    if ('POST' == $http_type) {
      $this->args = array_merge($req, (array) json_decode($post));
    } else {
      $this->args = $_GET;
    }
  }


  protected function getArgs() {
    return toarray($this->args);
  }

  private function write_access_log($controller, $method, $allheaders, $_request, $post) {

    if ($controller == 'log' || $controller == 'Log') {
      return;
    }

    $time = strftime('%Y-%m-%d %H:%M:%S', time());
    logtext('<hr/>');
    logtext('<div><span class =functionname>' . $time . '  ' . $controller . '/' . $method . '</span></div>');

    if (!empty($_request)) {
      logtext('参数:$_REQUEST');
      logtext(json_encode($_request));
    }


    if (!empty($post)) {
      $para = (array) json_decode($post);
      logtext('参数:php//input');
      logtext(json_encode($para));
    }
  }

  private function setUser($user) {
    $this->user = $user;
  }

  public function getUser() {
    return $this->user;
  }



  public function getRoles() {
    return $this->roles;
  }

  public function checkAuthToken($allheaders, $controller, $method) {
    $skip = array(
      'log/index',
      'log/clearlog',
      'log/bpm',
      'qrcoder/img',
      'Auth/loginMobile',
      'Auth/JWT_login',
      'tree/systemSummary',
      'tree/index',
      'App/*',
      'Network/*',
      'dbdocu/gethelp',
    );

    if (in_array($controller . '/' . $method, $skip)) {
      return true;
    }

    if (in_array($controller . '/*', $skip)) {
      return true;
    }

    if (!array_key_exists('Authorization', $allheaders)) {
      logtext(" $controller/$method : No Authorization key ");
      return false;
    }

    // 如果 client_token 为空
    $client_token = $allheaders['Authorization'];
    if (empty($client_token)) {
      logtext('client_token empty');
      return false;
    }

    try {
      $token_decoded = JWT::decode($client_token, 'cnix_key_login_2342342324');
    } catch (Exception $e) {
      logtext('JWT-decode-failure:' . $e->getMessage());
      return false;
    }

    $token_array = (array) $token_decoded;


    if ($token_array['exp'] < time()) {
      //超时了,强制退出
      logtext('session 超时:');
      return false;
    }

    $this->setCustid($token_decoded->id);
    $this->setMobile($token_decoded->mobile);
    $this->setUser($token_array['user']);
    return true;
  }

  public function logout() {
    ini_set('display_errors', true);
    ob_start();
    $this->session->sess_destroy();
    $loginurl = $this->config->item('login_url');
    logtext("logout...with redirect... $loginurl ");
    header('HTTP/1.1 401 Unauthorized');
    ob_end_flush();
    exit;
  }

  public function getCustid() {
    return $this->custid;
  }

  public function getMobile() {
    return $this->user_mobile;
  }

  public function setCustid($custid) {
    $this->custid = $custid;
  }

  public function setMobile($mobile) {
    $this->user_mobile = $mobile;
  }
}
