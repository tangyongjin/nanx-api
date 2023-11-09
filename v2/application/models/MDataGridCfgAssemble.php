<?php

declare(strict_types=1);

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;


if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MDataGridCfgAssemble extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->model('MDataGridCfgExecutor');
    }

    public function  PipeRunner($cfg) {

        $pipeline = (new Pipeline())

            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->init($config);
            })
            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setGridMeta($config);
            })
            ->pipe(function ($config) {
                // 获取  editor_cfg, display_cfg
                $this->MDataGridCfgExecutor->setTotalColsCfg();
            })

            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setColumnHiddenCols();
            })
            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setFormHiddenCols();
            })
            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setTableColumnConfig();
            })
            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setFormUsedColumns();
            })
            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setUFormConfig();
            })
            ->pipe(function ($config) {
                $this->MDataGridCfgExecutor->setButtonCfg();
            })
            ->pipe(function () {
                return $this->MDataGridCfgExecutor->getter();
            });

        $salaryResult = $pipeline->process($cfg);
        return $salaryResult;
    }
}
