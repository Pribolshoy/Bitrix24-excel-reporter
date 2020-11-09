<?php


namespace App\Service\Excel;


use Symfony\Component\Config\Definition\Exception\Exception;

class PHPExcelMaker extends ExcelMaker
{
    public function run() {

        if (!$this->config) {
            throw new Exception('Конфигурация для формирования xlsx не задана!');
        }

        // очерченые границы внутри и саружи ячеек
        $border = array(
            'borders'=>array(
                'allborders' => array(
                    'style' => \PHPExcel_Style_Border::BORDER_THIN,
                    'color' => array('rgb' => '000000')
                )
            )
        );

        $line = 1;

        $objPHPExcel = new \PHPExcel();

        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $line++, 'Название отчета: Отчет по лидам/сделкам без назначения следующего действия')
            ->setCellValue('A' . $line++, 'Компания: ' . $this->config['company'])
            ->setCellValue('A' . $line++, 'Дата: ' . $this->config['date']);

        $line += 2;

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

        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $line, '')
            ->setCellValue('B' . $line, 'Лиды')
            ->setCellValue('C' . $line, 'Сделки');

        $objPHPExcel->getActiveSheet()->getStyle("B$line:C$line")->applyFromArray($border);

        $line++;

        // Проценты сделок и лидов
        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $line, 'Итого (без назначения следующего действия)')
            ->setCellValue('B' . $line, count($this->config['unprocessed_leads']))
            ->setCellValue('C' . $line, count($this->config['unprocessed_deals']));

        $objPHPExcel->getActiveSheet()->getStyle("A$line:C$line")->applyFromArray($border);

        $line++;

        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $line, '% от общего количества')
            ->setCellValue('B' . $line, $procent_leads . '%')
            ->setCellValue('C' . $line, $procent_deals . '%');

        $objPHPExcel->getActiveSheet()->getStyle("A$line:C$line")->applyFromArray($border);

        $line += 2;

        // Статистика по менеджерам
        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $line, 'Менеджеры')
            ->setCellValue('B' . $line, 'Лиды (без назначения)')
            ->setCellValue('C' . $line, 'Всего лидов за день')
            ->setCellValue('D' . $line, 'Сделки (без назначения)')
            ->setCellValue('E' . $line, 'Всего сделок за день');

        $objPHPExcel->getActiveSheet()->getStyle("A$line:E$line")->applyFromArray($border);

        $line++;

        foreach ($this->config['managers'] as $manager) {

            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $line, $manager['NAME'])
                ->setCellValue('B' . $line, $manager['LEADS_UNPROCESSED_COUNT'])
                ->setCellValue('C' . $line, $manager['LEADS_TOTAL_COUNT'])
                ->setCellValue('D' . $line, $manager['DEALS_UNPROCESSED_COUNT'])
                ->setCellValue('E' . $line, $manager['DEALS_TOTAL_COUNT']);

            $objPHPExcel->getActiveSheet()->getStyle("A$line:E$line")->applyFromArray($border);

            $line++;

            $data[] = [$manager['NAME'], $manager['LEADS_UNPROCESSED_COUNT'], $manager['LEADS_TOTAL_COUNT'], $manager['DEALS_UNPROCESSED_COUNT'], $manager['DEALS_TOTAL_COUNT']];
        }



        $leads_and_deals = array_merge($this->config['unprocessed_leads'], $this->config['unprocessed_deals']);

        usort($leads_and_deals, function($a, $b)
            {
                if ($a["DATE_CREATE"] == $b["DATE_CREATE"]) {
                    return 0;
                }
                return (strtotime($a["DATE_CREATE"]) < strtotime($b["DATE_CREATE"])) ? -1 : 1;
            }
        );

        $line += 2;

        $objPHPExcel->getActiveSheet()
            ->setCellValue('A' . $line, '')
            ->setCellValue('B' . $line, '#')
            ->setCellValue('C' . $line, 'Название')
            ->setCellValue('D' . $line, 'Тип')
            ->setCellValue('E' . $line, 'Менеджер');

        $objPHPExcel->getActiveSheet()->getStyle("B$line:E$line")->applyFromArray($border);

        $line++;

        $i = 1;
        foreach ($leads_and_deals as $item) {
            $type = 'Неизвестный';
            $hyperlink = '';

            if (isset($item['STATUS_ID'])) {
                $type = 'Лид';
                $hyperlink = $this->config['host'] . "/crm/lead/details/{$item['ID']}/";
            }
            if (isset($item['STAGE_ID'])) {
                $type = 'Сделка';
                $hyperlink = $this->config['host'] . "/crm/deal/details/{$item['ID']}/";
            }

            if (isset($this->config['managers'][$item['ASSIGNED_BY_ID']])) {
                $manager = $this->config['managers'][$item['ASSIGNED_BY_ID']]['NAME'];
            }

            $objPHPExcel->getActiveSheet()
                ->setCellValue('A' . $line, '')
                ->setCellValue('B' . $line, $i)
                ->setCellValue('C' . $line, $item['TITLE'])
                ->setCellValue('D' . $line, $type)
                ->setCellValue('E' . $line, $manager);

            $objPHPExcel->getActiveSheet()->getCell('C' . $line)->getHyperlink()->setUrl($hyperlink);

            $objPHPExcel->getActiveSheet()->getStyle("B$line:E$line")->applyFromArray($border);

            $i++;
            $line++;
        }

        $objPHPExcel->getActiveSheet()->getColumnDimension('A')->setWidth(42);
        $objPHPExcel->getActiveSheet()->getColumnDimension('B')->setWidth(24);
        $objPHPExcel->getActiveSheet()->getColumnDimension('C')->setWidth(24);
        $objPHPExcel->getActiveSheet()->getColumnDimension('D')->setWidth(24);
        $objPHPExcel->getActiveSheet()->getColumnDimension('E')->setWidth(24);

        $objPHPExcel->setActiveSheetIndex(0);
        $objWriter = \PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');

        $this->checkDir($this->config['files_dir']);
        $objWriter->save($this->config['files_dir'] . DIRECTORY_SEPARATOR . $this->config['filename']);
    }
}