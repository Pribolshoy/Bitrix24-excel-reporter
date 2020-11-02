<?php


namespace App\Service;


use Symfony\Component\Config\Definition\Exception\Exception;

class XLSXWriterExcelMaker extends ExcelMaker
{
    protected $config;

    public function setConfig($config) {
        $this->config = $config;
    }

    public function run() {

        if (!$this->config) {
            throw new Exception('Конфигурация для формирования xlsx не задана!');
        }

        $header = ['GENERAL','GENERAL','GENERAL','GENERAL','GENERAL'];

        $data = [
            ['Название отчета: Отчет по лидам/сделкам без назначения следующего действия', 'style' => []],
            ['Компания: ' . $this->config['company'], 'style' => []],
            ['Дата: ' . $this->config['date'], 'style' => []],
        ];

        $data[] = []; // пропуск строки
        $data[] = []; // пропуск строки

        if ($this->config['total_leads']) {
            $procent_leads = round(((count($this->config['unprocessed_leads']) * 100) / count($this->config['total_leads'])), 2);
                   } else {
            $procent_leads = 0;
        }

        if ($this->config['total_deals']) {
            $procent_deals = round(((count($this->config['unprocessed_deals']) * 100) / count($this->config['total_deals'])), 2);
        } else {
            $procent_deals = 0;
        }

        // Проценты сделок и лидов
        $data[] = [
            '', 'Лиды', 'Сделки',
        ];
        $data[] = ['Итого (без назначения следующего действия)', count($this->config['unprocessed_leads']), count($this->config['unprocessed_deals'])];
        $data[] = ['% от общего количества', $procent_leads . '%', $procent_deals . '%'];

        $data[] = []; // пропуск строки
        $data[] = []; // пропуск строки

        // Статистика по менеджерам
        $data[] = ['Менеджеры', 'Лиды (без назначения)', 'Всего лидов', 'Сделки (без назначения)', 'Всего сделок'];
        foreach ($this->config['managers'] as $manager) {
            $data[] = [$manager['NAME'], $manager['LEADS_UNPROCESSED_COUNT'], $manager['LEADS_TOTAL_COUNT'], $manager['DEALS_UNPROCESSED_COUNT'], $manager['DEALS_TOTAL_COUNT']];
        }

        $data[] = []; // пропуск строки
        $data[] = []; // пропуск строки

        // Необработаные лиды/сделки
        $data[] = [
            '', '#', 'Название', 'Тип', 'Менеджер',
            'style' => [
                [],
                ['border' => 'left,right,top,bottom', 'border-style' => 'thin'],
                ['border' => 'left,right,top,bottom', 'border-style' => 'thin'],
                ['border' => 'left,right,top,bottom', 'border-style' => 'thin'],
                ['border' => 'left,right,top,bottom', 'border-style' => 'thin']
            ]
        ];

        $leads_and_deals = array_merge($this->config['unprocessed_leads'], $this->config['unprocessed_deals']);

        usort($leads_and_deals, function($a, $b)
            {
                if ($a["DATE_CREATE"] == $b["DATE_CREATE"]) {
                    return 0;
                }
                return (strtotime($a["DATE_CREATE"]) < strtotime($b["DATE_CREATE"])) ? -1 : 1;
            }
        );

        $i = 1;
        foreach ($leads_and_deals as $item) {
            $type = 'Неизвестный';
            $hyperlink = '';

            if (isset($item['STATUS_ID'])) {
                $type = 'Лид';
                $hyperlink = $hyperlink = $this->config['host'] . "/crm/lead/details/{$item['ID']}/";
            }
            if (isset($item['STAGE_ID'])) {
                $type = 'Сделка';
                $hyperlink = $hyperlink = $this->config['host'] . "/crm/deal/details/{$item['ID']}/";
            }

            if (isset($this->config['managers'][$item['ASSIGNED_BY_ID']])) {
                $manager = $this->config['managers'][$item['ASSIGNED_BY_ID']]['NAME'];
            }

            $title_string = '=HYPERLINK("' . $hyperlink . '","' . $item['TITLE'] . '")';

            $data[] = [
                '', $i, $title_string, $type, $manager,
                'style' => [
                        [],
                        ['border' => 'left,right,top,bottom', 'border-style' => 'thin'],
                        ['border' => 'left,right,top,bottom', 'border-style' => 'thin'],
                        ['border' => 'left,right,top,bottom', 'border-style' => 'thin'],
                        ['border' => 'left,right,top,bottom', 'border-style' => 'thin']
                    ]
                ];
            $i++;
        }

        // Запись данных
        $writer = new \XLSXWriter();

        $writer->writeSheetHeader('Sheet1', $header, $col_options = ['widths' => ['42','24','24','24','24']] );

        foreach($data as &$row) {
            $style = [
                'border' => 'left,right,top,bottom',
                'border-style' => 'thin',
            ];

            if (isset($row['style'])) {
                $style = $row['style'];
                unset($row['style']);
            }

            $writer->writeSheetRow('Sheet1', $row, $style);
        }

        $this->checkDir($this->config['files_dir']);
        $writer->writeToFile($this->config['files_dir'] . DIRECTORY_SEPARATOR . $this->config['filename']);
    }

    protected function checkDir($dir) {
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }
    }
}