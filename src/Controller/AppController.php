<?php

namespace App\Controller;

use App\Service\BitrixFacade;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\ArrayHelper;
use App\Service\Handler\LeadHandler;
use App\Service\Handler\DealHandler;

class AppController extends AbstractController
{
    public $bitrix_app;

    public $mailer;

    public $excel_maker;


    protected $date;

    protected $filename;

    public function __construct(\App\Service\MailSender $Mailer, \App\Service\PHPExcelMaker $ExcelMaker)
    {

        $this->mailer = $Mailer;
        $this->excel_maker = $ExcelMaker;

        $this->date = date('Y-m-d');
        //$this->date = '2020-10-24';
    }

    /**
     * @Route("/app", name="app")
     */
    public function index()
    {
        return $this->render('app/index.html.twig');
    }

    /**
     * @Route("/run", name="run_app")
     */
    public function run(BitrixFacade $BitrixFacade, LeadHandler $LeadHandler, DealHandler $DealHandler)
    {
        $this->bitrix_app = $BitrixFacade;

        $this->filename = $this->getParameter('app.xlsx_name') . '_' . $this->date . '.xlsx';

        $date_start = $this->date . 'T06:00:00';
        $date_end = $this->date . 'T23:00:00';

        $config = [
            'filter' => [
                '>DATE_CREATE' => $this->date . 'T06:00:00',
                '<DATE_CREATE' => $this->date . 'T23:00:00'
            ]
        ];

        // Лиды
        $LeadByDateHandler = clone $LeadHandler;
        $LeadByDateHandler->setConfig($config);
        $LeadByDateHandler->run();
        $LeadHandler->setConfig([]);
        $LeadHandler->run();

        // Сделки
        $DealByDateHandler = clone $DealHandler;
        $DealByDateHandler->setConfig($config);
        $DealHandler->setConfig([]);
        $DealByDateHandler->run();
        $DealHandler->run();

        // Получить всех менеджеров
        $managers_data = $this->bitrix_app->getUsers();
        $managers = ArrayHelper::index($managers_data['result'], 'ID');
        $managers = \App\Service\BitrixHelper::parseManagers($managers);
        $managers = \App\Service\BitrixHelper::collectManagersAndLeads($managers, $LeadByDateHandler->get('total_leads'), $LeadByDateHandler->get('unprocessed_leads'));
        $managers = \App\Service\BitrixHelper::collectManagersAndDeals($managers, $DealByDateHandler->get('total_deals'), $DealByDateHandler->get('unprocessed_deals'));

        // Сформировать эксель и сохранить
        $xlsx_config = [
            'date' => $this->date,
            'company' => 'название компании',
            'host' => $this->bitrix_app->getHost(),
            'total_leads' => $LeadHandler->get('total_leads'),
            'leads' => $LeadHandler->get('active_leads'),
            'unprocessed_leads' => $LeadHandler->get('unprocessed_leads'),
            'total_deals' => $DealHandler->get('total_deals'),
            'deals' => $DealHandler->get('active_deals'),
            'unprocessed_deals' => $DealHandler->get('unprocessed_deals'),
            'managers' => $managers,
            'files_dir' => $this->getParameter('app.files_dir'),
            'filename' => $this->filename,
        ];

        $this->excel_maker->setConfig($xlsx_config);
        $this->excel_maker->run();

        // Отправить на почты
        $this->mailer->setFilename($this->filename);
        $file = $this->mailer->getFileUrl();
//        $this->mailer->run();

        return $this->render('app/run.html.twig', [
            'detailed_info' => false,
            'date' => $this->date,
            'company' => 'название компании',
            'filename' => $this->filename,
            'file' => $file,
            'total_leads' => $LeadByDateHandler->get('total_leads'),
            'active_leads' => $LeadByDateHandler->get('active_leads'),
            'unprocessed_leads_ids' => $LeadByDateHandler->get('unprocessed_leads_ids'),
            'active_deals' => $DealByDateHandler->get('active_deals'),
            'total_deals' => $DealByDateHandler->get('total_deals'),
            'unprocessed_deals_ids' => $DealByDateHandler->get('unprocessed_deals_ids'),
            'managers' => $managers,
        ]);
    }

    public function install() {
        $filename = $this->getParameter('app.files_dir') . DIRECTORY_SEPARATOR . $this->getParameter('app.access_filename');

        $access_data = [];

        if (isset($_REQUEST['auth'])) {

            if (!is_dir($this->getParameter('app.files_dir'))) {
                mkdir($this->getParameter('app.files_dir'), 0777, true);
            }

            $access_data = json_encode($_REQUEST['auth']);
            file_put_contents($filename, $access_data);
        }
        return $this->json($access_data);
    }
}
