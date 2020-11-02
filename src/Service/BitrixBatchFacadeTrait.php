<?php


namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;

trait BitrixBatchFacadeTrait
{
    use BitrixBaseFacadeTrait;

    protected function getBatchList($api_command, $total, $select, $filter) {
        $step_rows = 50;
        $step_requests = 50;

        $requests = ($total / $step_rows) + 1;
        $requests = preg_replace('#\.[0-9]*#', '', $requests);

        $cycles = ($requests / $step_requests) + 1;
        $cycles = preg_replace('#\.[0-9]*#', '', $cycles);

        $filter = implode('&filter[]=', $filter);
        $select = implode('&select[]=', $select);

        $requests_results = [];
        $all_cmd = [];

        for ($p = 0, $n = 0; $p < $cycles; $p++, $n = $n + 50) {

            $cmd = [];
            $start = $p * ($step_rows * $step_requests);

            for ($i = 0; $i < $step_requests; $i++) {
                $all_cmd['leads_' . ($n + $i)] = $api_command . '?start=' . ($start + ($i * $step_rows)) . $filter . $select;
                $cmd['leads_' . ($n + $i)] = $api_command . '?start=' . ($start + ($i * $step_rows)) . $filter . $select;
            }

            $response = $this->bitrix_app->call(
                'batch',
                array(
                    'halt' => 0,
                    'cmd' => $cmd
                )
            );
            $requests_results = array_merge($requests_results, $response['result']['result']);
        }

        $result = [];
        foreach ($requests_results as $items) {
            $rows = \App\Service\ArrayHelper::index($items, 'ID');
            $result = $result + $rows;
        }

        return $result;
    }
}