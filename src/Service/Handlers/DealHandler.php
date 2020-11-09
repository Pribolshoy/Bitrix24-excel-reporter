<?php


namespace App\Service\Handlers;


use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\BitrixHelper;

class DealHandler extends BaseHandler
{
    public function run() {

        $filter = [];

        if (isset($this->config['filter'])) {
            $filter = $this->config['filter'];
        }

        $total_deals = $this->bitrix_app->getDeals($filter, true);
        $total_deals = ArrayHelper::index($total_deals['result'], 'ID');

        $active_deals = $this->bitrix_app->getDeals($filter + ['STAGE_SEMANTIC_ID' => 'P'], true);
        $active_deals = ArrayHelper::index($active_deals['result'], 'ID');

        // Массив ID активных сделок
        $active_deals_ids = ArrayHelper::getKeyFromArray($active_deals, 'ID');

        // Получить незавершенные дела
        $active_deals_activities_data = $this->getActivitiesByOwner($active_deals_ids);

        // Индексовать по ID сделки (если у одной сделки больше 1 дела, то они перекрываются)
        $indexed_active_deals_activities_data = ArrayHelper::index($active_deals_activities_data['result'], 'OWNER_ID');

        // Массив ID обработанных сделок
        $processed_deals_ids = ArrayHelper::getKeyFromArray($indexed_active_deals_activities_data, 'OWNER_ID');

        // Массив не обработанных сделок
        $unprocessed_deals = array_diff_key($active_deals, $processed_deals_ids);

        // Массив ID не обработанных активных сделок
        $unprocessed_deals_ids = array_diff($active_deals_ids, $processed_deals_ids);

        // Массив ID активных лидов с незавершенными просроченными на данную дату делами
        $expired_activity_deals_ids = BitrixHelper::getExpiredActivityOwnerIds($active_deals_activities_data['result'], $this->get('actual_date'));

        // Массив не обработанных лидов с просроченым делом
        $expired_activity_deals = array_intersect_key($active_deals, $expired_activity_deals_ids);

        // Массив обработанных сделок
        $processed_deals = array_diff_key($total_deals, $unprocessed_deals_ids);

        $this->config['total_deals'] = $total_deals;
        $this->config['active_deals'] = $active_deals;
        $this->config['activities_deals_data'] = $indexed_active_deals_activities_data;
        $this->config['processed_deals'] = $processed_deals;
        $this->config['processed_deals_ids'] = $processed_deals_ids;
        $this->config['unprocessed_deals'] = $unprocessed_deals;
        $this->config['unprocessed_deals_ids'] = $unprocessed_deals_ids;
        $this->config['expired_activity_deals'] = $expired_activity_deals;
        $this->config['expired_activity_deals_ids'] = $expired_activity_deals_ids;

    }

    protected function collectData() {

    }
}