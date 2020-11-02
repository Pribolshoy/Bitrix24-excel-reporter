<?php


namespace App\Service\Handler;


use App\Service\ArrayHelper;

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
        $active_leads_activities_data = ArrayHelper::index($active_leads_activities_data['result'], 'OWNER_ID');

        // Массив ID обработанных лидов
        $processed_leads_ids = ArrayHelper::getKeyFromArray($active_leads_activities_data, 'OWNER_ID');

        // Массив не обработанных лидов
        $unprocessed_leads = array_diff_key($active_leads, $processed_leads_ids);

        // Массив ID не обработанных лидов
        $unprocessed_leads_ids = array_diff($active_leads_ids, $processed_leads_ids);

        // Массив обработанных лидов
        $processed_leads = array_diff_key($total_leads, $unprocessed_leads_ids);

        $this->config['total_leads'] = $total_leads;
        $this->config['active_leads'] = $active_leads;
        $this->config['activities_leads_data'] = $active_leads_activities_data;
        $this->config['processed_leads'] = $processed_leads;
        $this->config['processed_leads_ids'] = $processed_leads_ids;
        $this->config['unprocessed_leads_ids'] = $unprocessed_leads_ids;
        $this->config['unprocessed_leads'] = $unprocessed_leads;
    }

    public function get($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        }
    }
}