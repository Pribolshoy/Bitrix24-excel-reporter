<?php


namespace App\Service\Handlers;


use App\Service\Helpers\ArrayHelper;
use App\Service\Helpers\BitrixHelper;

class LeadHandler extends BaseHandler
{
    public function run() {

        $filter = [];

        if (isset($this->config['filter'])) {
            $filter = $this->config['filter'];
        }

        $total_leads = $this->bitrix_app->getLeads($filter, true);
        $total_leads = ArrayHelper::index($total_leads['result'], 'ID');

        $active_leads = $this->bitrix_app->getLeads($filter + ['!STATUS_ID' => ['CONVERTED', 'JUNK']], true);
        $active_leads = ArrayHelper::index($active_leads['result'], 'ID');

        // Массив ID активных лидов
        $active_leads_ids = ArrayHelper::getKeyFromArray($active_leads, 'ID');

        // Получить незавершенные дела
        $active_leads_activities_data = $this->getActivitiesByOwner($active_leads_ids);

        // Индексовать по ID лида (если у одного лида больше 1 дела, то они перекрываются)
        $indexed_active_leads_activities_data = ArrayHelper::index($active_leads_activities_data['result'], 'OWNER_ID');

        // Массив ID обработанных лидов
        $processed_leads_ids = ArrayHelper::getKeyFromArray($indexed_active_leads_activities_data, 'OWNER_ID');

        // Массив не обработанных лидов
        $unprocessed_leads = array_diff_key($active_leads, $processed_leads_ids);

        // Массив ID не обработанных лидов
        $unprocessed_leads_ids = array_diff($active_leads_ids, $processed_leads_ids);

        // Массив ID активных лидов с незавершенными просроченными на данную дату делами
        $expired_activity_leads_ids = BitrixHelper::getExpiredActivityOwnerIds($active_leads_activities_data['result'], $this->get('actual_date'));

        // Массив не обработанных лидов с просроченым делом
        $expired_activity_leads = array_intersect_key($active_leads, $expired_activity_leads_ids);

        // Массив обработанных лидов
        $processed_leads = array_diff_key($total_leads, $unprocessed_leads_ids);

        $this->config['total_leads'] = $total_leads;
        $this->config['active_leads'] = $active_leads;
        $this->config['activities_leads_data'] = $indexed_active_leads_activities_data;
        $this->config['processed_leads'] = $processed_leads;
        $this->config['processed_leads_ids'] = $processed_leads_ids;
        $this->config['unprocessed_leads'] = $unprocessed_leads;
        $this->config['unprocessed_leads_ids'] = $unprocessed_leads_ids;
        $this->config['expired_activity_leads'] = $expired_activity_leads;
        $this->config['expired_activity_leads_ids'] = $expired_activity_leads_ids;

    }

    protected function collectData() {

    }
}