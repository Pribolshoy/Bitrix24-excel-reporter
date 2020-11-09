<?php


namespace App\Service\Helpers;

use App\Service\Helpers\ArrayHelper;
use Symfony\Component\Config\Definition\Exception\Exception;

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
            foreach ($managers as $id => &$manager) {
                $manager['LEADS_TOTAL'] = [];

                if ($leads) {
                    foreach ($leads as $lead) {
                        if ($id == $lead['ASSIGNED_BY_ID']) {
                            $manager['LEADS_TOTAL'][$lead['ID']] = $lead;
                        }
                    }
                }

                $manager['LEADS_TOTAL_COUNT'] = count($manager['LEADS_TOTAL']);

                $manager['LEADS_UNPROCESSED'] = [];

                if ($unprocessed_leads) {
                    foreach ($unprocessed_leads as $lead) {
                        if ($id == $lead['ASSIGNED_BY_ID']) {
                            $manager['LEADS_UNPROCESSED'][$lead['ID']] = $lead;
                        }
                    }
                }

                $manager['LEADS_UNPROCESSED_COUNT'] = count($manager['LEADS_UNPROCESSED']);

            }
        }

        return $managers;
    }

    public static function collectManagersAndDeals($managers, $deals = [], $unprocessed_deals = []) {

        if ($managers) {
            foreach ($managers as $id => &$manager) {
                $manager['DEALS_TOTAL'] = [];

                if ($deals) {
                    foreach ($deals as $deal) {
                        if ($id == $deal['ASSIGNED_BY_ID']) {
                            $manager['DEALS_TOTAL'][$deal['ID']] = $deal;
                        }
                    }
                }

                $manager['DEALS_TOTAL_COUNT'] = count($manager['DEALS_TOTAL']);

                $manager['DEALS_UNPROCESSED'] = [];

                if ($unprocessed_deals) {
                    foreach ($unprocessed_deals as $deal) {
                        if ($id == $deal['ASSIGNED_BY_ID']) {
                            $manager['DEALS_UNPROCESSED'][$deal['ID']] = $deal;
                        }
                    }
                }

                $manager['DEALS_UNPROCESSED_COUNT'] = count($manager['DEALS_UNPROCESSED']);
            }
        }

        return $managers;
    }

    public static function getExpiredActivityOwnerIds($activities, $datetime) {
        
        if (empty($datetime)) {
            throw new Exception('Пустое значение даты ($datetime)!');
        }
        
        $indexed_activities = ArrayHelper::index($activities, 'OWNER_ID');
        $all_owner_ids = ArrayHelper::getKeyFromArray($indexed_activities, 'OWNER_ID');

        $unexpired_owner_ids = [];

        foreach ($activities as $activity) {
            if ($activity['END_TIME'] > $datetime) {
                $unexpired_owner_ids[$activity['OWNER_ID']] = $activity['OWNER_ID'];
            }
        }

        $expired_owner_ids = array_diff_key($all_owner_ids, $unexpired_owner_ids);

        return $expired_owner_ids;
    }
}