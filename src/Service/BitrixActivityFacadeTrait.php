<?php


namespace App\Service;


trait BitrixActivityFacadeTrait
{
    use BitrixBaseFacadeTrait;

    protected $activities;

    protected function parseActivities($filter = [],  $start = 0) {
        $this->beforeParse();

        $Activities = new \Bitrix24\CRM\Activity($this->bitrix_app);
        $result = $Activities->getList(
            ["CREATED" => "DESC" ], // Order
            $filter, // Filter
            $select = [ "ID", "OWNER_ID", "OWNER_TYPE_ID", "TYPE_ID", "SUBJECT", "COMPLETED", "PROVIDER_TYPE_ID", "RESPONSIBLE_ID", "STATUS", "CREATED", "DEADLINE", "START_TIME", "END_TIME"], // Select
            $start // Start
        );

        if (isset($result['next']) && $result['next']) {
            if ($result['total'] > 1500) {
                $result['result'] = $this->getBatchList('crm.activity.list', $result['total'], $select, $filter);
            } else {
                $recursive_result = $this->parseActivities($filter, $result['next']);
                $result['result'] = array_merge($result['result'], $recursive_result['result']);
            }
        }

        return $result;
    }

    public function getActivities($filter = [], $update = false) {
        if (!$this->activities || $update) {
            $this->activities = $this->parseActivities($filter);
        }
        return $this->activities;
    }
}