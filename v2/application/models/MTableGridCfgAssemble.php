<?php

declare(strict_types=1);

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;


if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MTableGridCfgAssemble extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->model('MTableGridCfgExecutor');
    }

    public function  PipeRunner($cfg) {

        $pipeline = (new Pipeline())

            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->init($config);
            })
            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setGridMeta($config);
            })
            ->pipe(function ($config) {
                // 获取  editor_cfg, display_cfg
                $this->MTableGridCfgExecutor->setTotalColsCfg();
            })

            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->reorderColumns();
            })


            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setColumnHiddenCols();
            })
            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setFormHiddenCols();
            })
            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setTableColumnRender();
            })
            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setFormUsedColumns();
            })
            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setUFormConfig();
            })
            ->pipe(function ($config) {
                $this->MTableGridCfgExecutor->setButtonCfg();
            })
            ->pipe(function () {
                return $this->MTableGridCfgExecutor->getter();
            });

        $salaryResult = $pipeline->process($cfg);
        return $salaryResult;
    }
}
