<?php


namespace App\Service;


trait BitrixLeadFacadeTrait
{
    use BitrixBaseFacadeTrait;

    protected $leads;

    protected function parseLeads($filter = [], $start = 0) {
        $this->beforeParse();

        $Lead = new \Bitrix24\CRM\Lead($this->bitrix_app);
        $result = $Lead->getList(
            ["ASSIGNED_BY_ID" => "ASC" ], // Order
            $filter, // Filter
            $select = [ "ID", "TITLE", "PHONE", "COMMENTS", "ASSIGNED_BY_ID", "MODIFY_BY_ID", "STATUS_ID", "STATUS_SEMANTIC_ID", "DATE_CREATE", "DATE_MODIFY"], // Select
            $start // Start
        );

        if (isset($result['next']) && $result['next']) {
            if ($result['total'] > 1500) {
                $result['result'] = $this->getBatchList('crm.lead.list', $result['total'], $select, $filter);
            } else {
                $recursive_result = $this->parseLeads($filter, $result['next']);
                $result['result'] = array_merge($result['result'], $recursive_result['result']);
            }
        }

        return $result;
    }

    public function getLeads($filter = [], $update = false) {
        if (!$this->leads || $update) {
            $this->leads = $this->parseLeads($filter);
        }
        return $this->leads;
    }
}