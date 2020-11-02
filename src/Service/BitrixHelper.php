<?php


namespace App\Service;


class BitrixHelper
{
    public static function parseManagers($managers) {
        $result = [];

        if ($managers) {
            foreach ($managers as $key => $item) {
                $result[$key] =[
                    'ID' => $item['ID'],
                    'EMAIL' => $item['EMAIL'],
                    'NAME' => $item['LAST_NAME'] . ' ' . $item['NAME'],
                ];
            }
        }

        return $result;
    }

    public static function collectManagersAndLeads($managers, $leads = [], $unprocessed_leads = []) {

        if ($managers) {
            foreach ($managers as $id => &$item) {
                $item['LEADS_TOTAL'] = [];

                if ($leads) {
                    foreach ($leads as $lead) {
                        if ($id == $lead['ASSIGNED_BY_ID']) {
                            $item['LEADS_TOTAL'][$lead['ID']] = $lead;
                        }
                    }
                }

                $item['LEADS_TOTAL_COUNT'] = count($item['LEADS_TOTAL']);

                $item['LEADS_UNPROCESSED'] = [];

                if ($unprocessed_leads) {
                    foreach ($unprocessed_leads as $lead) {
                        if ($id == $lead['ASSIGNED_BY_ID']) {
                            $item['LEADS_UNPROCESSED'][$lead['ID']] = $lead;
                        }
                    }
                }

                $item['LEADS_UNPROCESSED_COUNT'] = count($item['LEADS_UNPROCESSED']);

            }
        }

        return $managers;
    }

    public static function collectManagersAndDeals($managers, $deals = [], $unprocessed_deals = []) {

        if ($managers) {
            foreach ($managers as $id => &$item) {
                $item['DEALS_TOTAL'] = [];

                if ($deals) {
                    foreach ($deals as $deal) {
                        if ($id == $deal['ASSIGNED_BY_ID']) {
                            $item['DEALS_TOTAL'][$deal['ID']] = $deal;
                        }
                    }
                }

                $item['DEALS_TOTAL_COUNT'] = count($item['DEALS_TOTAL']);

                $item['DEALS_UNPROCESSED'] = [];

                if ($unprocessed_deals) {
                    foreach ($unprocessed_deals as $deal) {
                        if ($id == $deal['ASSIGNED_BY_ID']) {
                            $item['DEALS_UNPROCESSED'][$deal['ID']] = $deal;
                        }
                    }
                }

                $item['DEALS_UNPROCESSED_COUNT'] = count($item['DEALS_UNPROCESSED']);
            }
        }

        return $managers;
    }
}