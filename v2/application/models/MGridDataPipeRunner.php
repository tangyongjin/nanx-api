<?php

declare(strict_types=1);

use League\Pipeline\Pipeline;
use League\Pipeline\StageInterface;

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

class MGridDataPipeRunner extends CI_Model {
    public function __construct() {
        parent::__construct();
        $this->load->model('MGridDataPipe');
    }

    public function  GridDataHandler($cfg) {
        $pipeline = (new Pipeline())
            ->pipe(function ($config) {
                $this->MGridDataPipe->init($config);
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setQueryCfg();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setBasefields();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setCommboFields();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setTransformeredFields();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setSqlTransformered();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setWhereString();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setSqlWithQueryCfg();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setSqlWithAuthor();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setSqlQuick();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setAllRows();
            })
            ->pipe(function ($config) {
                $this->MGridDataPipe->setRealRows();
            })
            ->pipe(function () {
                return $this->MGridDataPipe->getter();
            });

        $data = $pipeline->process($cfg);
        return $data;
    }
}
