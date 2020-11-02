<?php


namespace App\Service\Handler;


use App\Service\ArrayHelper;
use App\Service\BitrixFacade;

abstract class BaseHandler
{
    protected $bitrix_app;

    public $config;

    public function __construct(BitrixFacade $BitrixFacade)
    {
        $this->bitrix_app = $BitrixFacade;
    }

    public function setConfig($config) {
        $this->config = $config;
    }

    abstract public function run();

    protected function getActivitiesByOwner($ids) {
        $filter = [
            'OWNER_ID' => $ids,
            'COMPLETED' => 'N' // значит не завершен
        ];
        $activities_leads_data = $this->bitrix_app->getActivities($filter, true);

        return $activities_leads_data;
    }
}