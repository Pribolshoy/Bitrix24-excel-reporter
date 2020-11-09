<?php


namespace App\Service\Bitrix;


trait BitrixDealFacadeTrait
{
    use BitrixBaseFacadeTrait;

    protected $deals;

    protected function parseDeals($filter = [], $start = 0) {
        $this->beforeParse();

        $Deal = new \Bitrix24\CRM\Deal\Deal($this->bitrix_app);
        $result = $Deal->getList(
            ["ASSIGNED_BY_ID" => "ASC" ], // Order
            $filter, // Filter
            $select = [ "ID", "TITLE", "COMMENTS", "ASSIGNED_BY_ID", "MODIFY_BY_ID", "STAGE_ID", "STAGE_SEMANTIC_ID", "DATE_CREATE", "DATE_MODIFY"], // Select
            $start // Start
        );

        if (isset($result['next']) && $result['next']) {
            if ($result['total'] > 1500) {
                $result['result'] = $this->getBatchList('crm.deal.list', $result['total'], $select, $filter);
            } else {
                $recursive_result = $this->parseDeals($filter, $result['next']);
                $result['result'] = array_merge($result['result'], $recursive_result['result']);
            }
        }

        return $result;
    }

    public function getDeals($filter = [], $update = false) {
        if (!$this->deals || $update) {
            $this->deals = $this->parseDeals($filter);
        }
        return $this->deals;
    }
}