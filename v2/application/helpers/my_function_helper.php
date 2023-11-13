<?php

function sysdatetime() {
  $cur = date('Y-m-d H:i:s', time());
  return $cur;
}

function sysdate() {
  $cur = date('Y-m-d', time());
  return $cur;
}

function sysdate_format($format) {
  $cur = date($format, time());
  return $cur;
}

function resize_image($file, $w, $h, $crop = false) {
  list($width, $height) = getimagesize($file);
  $r = $width / $height;
  if ($crop) {
    if ($width > $height) {
      $width = ceil($width - ($width * ($r - $w / $h)));
    } else {
      $height = ceil($height - ($height * ($r - $w / $h)));
    }
    $newwidth = $w;
    $newheight = $h;
  } else {
    if ($w / $h > $r) {
      $newwidth = $h * $r;
      $newheight = $h;
    } else {
      $newheight = $w / $r;
      $newwidth = $w;
    }
  }
  $src = imagecreatefromjpeg($file);
  $dst = imagecreatetruecolor($newwidth, $newheight);
  imagecopyresampled($dst, $src, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

  return $dst;
}

function randstr($length = 10) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; ++$i) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
}

function verifycode($length = 6) {
  $characters = '0123456789';
  $charactersLength = strlen($characters);
  $randomString = '';
  for ($i = 0; $i < $length; ++$i) {
    $randomString .= $characters[rand(0, $charactersLength - 1)];
  }

  return $randomString;
}

function array_to_string($arr, $wrapper = null) {

  if (count($arr) == 0) {
    if ($wrapper) {

      return "-1";
    } else {
      return  -1;
    }
  }
  $str = '';
  foreach ($arr as $item) {

    if ($wrapper) {
      $str .= "'" . $item . "'" . ",";
    } else {
      $str .= $item . ",";
    }
  }
  $str = rtrim($str, ',');
  return $str;
}

/** 数组转字符串，带''的 */
function array_to_strings($arr, $wrapper) {
  $str = '';
  foreach ($arr as $one_item) {
    $str .= "'" . $wrapper . $one_item . $wrapper . "'" . ',';
  }
  $str = rtrim($str, ',');
  if (0 == strlen($str)) {
    $str = '-1';
  }

  return $str;
}


function arraytostring($arr, $wrapper) {
  $str = '';
  foreach ($arr as $one_item) {
    $str .= "'" . $wrapper . $one_item . $wrapper . "'" . ',';
  }
  $str = rtrim($str, ',');
  if (0 == strlen($str)) {
    $str = '-1';
  }
  return $str;
}


function db_exits($table, $col, $val) {
  $ci = &get_instance();

  $row = $ci->db->get_where($table, array($col => $val))->row_array();
  if ($row) {
    return true;
  } else {
    return false;
  }
}

function logtext($para) {
  $CI = &get_instance();
  $log = fopen(helper_getlogname(), 'a+');
  if (is_string($para)) {
    $logtext = $para;
  } else {
    $logtext = var_export($para, true);
  }

  fwrite($log, $logtext);
  fwrite($log, "\n");
  fclose($log);
}

function helper_getlogname() {
  $CI = &get_instance();
  $logdir = config_item('log_path');
  $fname = 'portal-' . date('Y-m-d', time()) . '.log';
  return $logdir . '/' . $fname;
}

function debug($p) {
  echo '<pre>';
  print_r($p);
  echo '</pre>';
}

function debugtime($str) {
  debug(date('Y-m-d H:i:s.') . gettimeofday()['usec'] . '<---' . $str);
}


function toarray($object) {
  $array = json_decode(json_encode($object), true);

  return $array;
}

function array_retrieve($arr, $keys_config) {
  $result = array();
  if (is_array($keys_config)) {
    foreach ($arr as $onearr) {
      $tmp = array();
      foreach ($keys_config as $segment_index) {
        if (is_array($segment_index)) {
          $segment = $segment_index['segment'];
          $index = $segment_index['index'];
          $tmp[$index] = $onearr[$segment][$index];
        } else {
          $tmp[$segment_index] = $onearr[$segment_index];
        }
      }
      $result[] = $tmp;
    }
  } else {
    foreach ($arr as $onearr) {
      $result[] = $onearr[$keys_config];
    }
  }

  return $result;
}

function arrayfilter($arr, $key, $values) {
  if (!is_array($values)) {
    $values = array($values);
  }
  $result = array();
  foreach ($arr as $onearr) {
    foreach ($values as $value) {
      if ($onearr[$key] == $value) {
        $result[] = $onearr;
      }
    }
  }

  return $result;
}

function httprequest($url, $paraArray, $method) {

  if ('post' == strtolower($method)) {
    return httppost($url, $paraArray);
  }

  if ('put' == strtolower($method)) {
    return httpput($url, $paraArray);
  }


  if ('get' == strtolower($method)) {
    return httpget($url, $paraArray);
  }
}



function httpput($url, $paraArray) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paraArray));

  $headers = array();
  $headers[] = 'Accept: */*';
  $headers[] = 'Content-Type: application/json';
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if (curl_errno($ch)) {
    $err = ['state' => 'error'];

    return $err;
  }
  curl_close($ch);
  $res = (array) json_decode($result);

  return $res;
}



function httppost($url, $paraArray) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($paraArray));

  $headers = array();
  $headers[] = 'Accept: */*';
  $headers[] = 'Content-Type: application/json';
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if (curl_errno($ch)) {
    $err = ['state' => 'error'];

    return $err;
  }
  curl_close($ch);
  $res = (array) json_decode($result);

  return $res;
}

function httpget($url, $paraArray) {
  $ch = curl_init();
  $para_values = array_values($paraArray);

  $tail = '';
  foreach ($para_values as $para) {
    $tail = $tail . $para . '&';
  }

  $tail = substr($tail, 0, -1);
  $url = $url . $tail;

  logtext($url);
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 0);
  $headers = array();
  $headers[] = 'Accept: */*';
  $headers[] = 'Content-Type: application/json';
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);
  if (curl_errno($ch)) {
    $err = ['state' => 'error'];

    return $err;
  }
  curl_close($ch);
  $res = (array) json_decode($result);

  return $res;
}


function uuid() {
  return sprintf(
    '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    // 32 bits for "time_low"
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),

    // 16 bits for "time_mid"
    mt_rand(0, 0xffff),

    // 16 bits for "time_hi_and_version",
    // four most significant bits holds version number 4
    mt_rand(0, 0x0fff) | 0x4000,

    // 16 bits, 8 bits for "clk_seq_hi_res",
    // 8 bits for "clk_seq_low",
    // two most significant bits holds zero and one for variant DCE1.1
    mt_rand(0, 0x3fff) | 0x8000,

    // 48 bits for "node"
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff),
    mt_rand(0, 0xffff)
  );
}


function postJson($url, $data) {

  $ch = curl_init($url);
  # Setup request to send json via POST.
  $payload = json_encode($data);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
  curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
  # Return response instead of printing.
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  # Send request.
  $result = curl_exec($ch);
  curl_close($ch);
  # Print response.
  return $result;
}


function response500($msg) {
  $ret = ['code' => 500,  'message' => $msg];
  echo json_encode($ret, JSON_UNESCAPED_UNICODE);
  die;
}

function bcsum(array $numbers) {
  $total = "0";
  foreach ($numbers as $onenumber) {
    $total = bcadd($total, $onenumber, 2);
  }
  return $total;
}
