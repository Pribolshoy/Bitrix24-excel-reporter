<?php


namespace App\Service\Handlers;


use App\Service\Helpers\ArrayHelper;
use App\Service\Bitrix\BitrixFacade;

abstract class BaseHandler
{
    protected $bitrix_app;

    public $config;

    public function __construct(BitrixFacade $BitrixFacade)
    {
        $this->bitrix_app = $BitrixFacade;
        $this->config = [];
    }

    public function setConfig($config) {
        $this->config = $config;
    }

    public function get($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
    }

    abstract public function run();

    protected function getActivitiesByOwner($ids, $adding_filter = []) {
        $filter = [
            'OWNER_ID' => $ids, // id лидов и сделок
            'COMPLETED' => 'N' // значит не завершен
        ];

//        $filter = array_merge($filter, $adding_filter);

        $activities_leads_data = $this->bitrix_app->getActivities($filter, true);

        return $activities_leads_data;
    }
}