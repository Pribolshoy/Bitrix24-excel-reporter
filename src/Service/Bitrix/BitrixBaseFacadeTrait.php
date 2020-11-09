<?php


namespace App\Service\Bitrix;


use Symfony\Component\Config\Definition\Exception\Exception;

trait BitrixBaseFacadeTrait
{
    protected $bitrix_app;

    protected function beforeParse() {
        if ($this->bitrix_app === NULL) {
            throw new Exception('Экземпляр класса приложения Битрикс24 не сконфигурирован!');
        }
    }
}