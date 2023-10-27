<?php

defined('BASEPATH') or exit('No direct script access allowed');

class User extends MY_Controller {


    public function __construct() {
        parent::__construct();
    }



    public function logout() {
        http_response_code(401);
        return false;
        die();
    }
}
