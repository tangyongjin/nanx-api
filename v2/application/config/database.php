<?php

defined('BASEPATH') or exit('No direct script access allowed');
$active_group = 'default';
$query_builder = true;

// $db['default'] = array(
//   'dsn' => '',
//   'hostname'     => '172.16.19.242',
//   'username'     => 'root',
//   'password'     => '123456',
//   'database'     => 'jinwang_salary',
//   'dbdriver' => 'mysqli',
//   'dbprefix' => '',
//   'pconnect' => false,
//   'db_debug' => true,
//   'cache_on' => false,
//   'cachedir' => '',
//   'char_set' => 'utf8',
//   'dbcollat' => 'utf8_general_ci',
//   'swap_pre' => '',
//   'encrypt' => false,
//   'compress' => false,
//   'stricton' => false,
//   'failover' => array(),
//   'save_queries' => true,
// );


$db['default'] = [
  'dsn' => '',
  'hostname' => '172.16.21.91',
  'username' => 'root',
  'password' => '123456',
  'database' => 'nanx',
  'dbdriver' => 'mysqli',
  'dbprefix' => '',
  'pconnect' => false,
  'db_debug' => false,
  'cache_on' => false,
  'cachedir' => '',
  'char_set' => 'utf8',
  'dbcollat' => 'utf8_general_ci',
  'swap_pre' => '',
  'encrypt' => false,
  'compress' => false,
  'stricton' => false,
  'failover' => [],
  'save_queries' => true,
];
