<?php


namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;

abstract class ExcelMaker
{
    protected $config;

    public function setConfig($config) {
        $this->config = $config;
    }

    abstract public function run();

    protected function checkDir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}