<?php

declare(strict_types=1);

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;


if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MServiceGridCfgAssemble extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->model('MServiceGridCfgExecutor');
    }

    public function  PipeRunner($cfg) {

        $pipeline = (new Pipeline())

            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->init($config);
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setGridMeta($config);
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setTotalColsCfg();
            })

            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->reorderColumns();
            })


            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setColumnHiddenCols();
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setFormHiddenCols();
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setTableColumnRender();
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setFormUsedColumns();
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setUFormConfig();
            })
            ->pipe(function ($config) {
                $this->MServiceGridCfgExecutor->setButtonCfg();
            })
            ->pipe(function () {
                return $this->MServiceGridCfgExecutor->getter();
            });

        $salaryResult = $pipeline->process($cfg);
        return $salaryResult;
    }
}
