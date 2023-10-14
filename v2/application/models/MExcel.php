<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\IOFactory;

class MExcel extends CI_Model {

  function __construct() {
    parent::__construct();
    $dir =  dirname(__DIR__, 2);
    include $dir .  '/excel/Classes/PHPExcel.php'; //引入文件
    include $dir .  '/excel/Classes/PHPExcel/Writer/Excel2007.php';
  }

  function  exportExcel($fname, $cols, $records) {
    $header = [];
    $total = [];

    foreach ($cols as $col) {
      $colname = $col['key'];
      $header[$colname] = $col['title'];
    }

    $total[] = $header;
    foreach ($records as $index => $record) {
      $tmp = [];
      foreach ($cols as $col) {
        $colname = $col['key'];
        $tmp[$colname] = $record[$colname];
      }
      $total[] = $tmp;
    }




    $objPHPExcel = new PHPExcel();
    $objPHPExcel->setActiveSheetIndex(0);

    //横向单元格标识
    $cellNames = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z', 'AA', 'AB', 'AC', 'AD', 'AE', 'AF', 'AG', 'AH', 'AI', 'AJ', 'AK', 'AL', 'AM', 'AN', 'AO', 'AP', 'AQ', 'AR', 'AS', 'AT', 'AU', 'AV', 'AW', 'AX', 'AY', 'AZ');

    // 使用header 作为 cols,
    $cols = array_keys($header);

    foreach ($total as $index => $record) {
      $index++;
      foreach ($cols as $cellindex => $col) {
        $cellname = $cellNames[$cellindex];
        if (array_key_exists($col, $record)) {
          $cellvalue = $record[$col];
        } else {
          $cellvalue = '';
        }
        if (is_numeric($cellvalue)) {
          $objPHPExcel->getActiveSheet()->setCellValueExplicit($cellname . $index, $cellvalue, PHPExcel_Cell_DataType::TYPE_NUMERIC);
        } else {
          $objPHPExcel->getActiveSheet()->setCellValueExplicit($cellname . $index, $cellvalue, PHPExcel_Cell_DataType::TYPE_STRING);
        }
      }
    }

    $filename = $fname . date("Y-m-d", time()) . ".xls";
    $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
    $objWriter->save("/var/www/html/download/$filename"); //保存文件
    $excel_url = 'http://' . $_SERVER['HTTP_HOST'] . '/download/';
    $ret = array("code" => 200, "data" => array("url" => $excel_url . $filename, "name" => $filename));
    echo json_encode($ret);
  }



  function  read_all_sheet_data($filename, $sheet_index) {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $file_type = IOFactory::identify($filename);
    $reader = IOFactory::createReader($file_type);
    $spreadsheet = $reader->load($filename);
    if (is_int($sheet_index)) {
      $spreadsheet->setActiveSheetIndex($sheet_index);
    } else {
      $spreadsheet->setActiveSheetIndexByName($sheet_index);
    }
    $all_sheetData = $spreadsheet->getActiveSheet()->toArray(null, true,  false, false);
    return $all_sheetData;
  }
}
