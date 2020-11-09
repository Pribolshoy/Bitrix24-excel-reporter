<?php


namespace App\Service\Bitrix;


use Psr\Container\ContainerInterface;

class BitrixFacade
{
    use BitrixLeadFacadeTrait, BitrixDealFacadeTrait, BitrixUserFacadeTrait, BitrixActivityFacadeTrait, BitrixBatchFacadeTrait;

    protected $container;

    protected $bitrix_app;

    protected $access_filename;

    protected $access_data;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->access_filename = $this->container->getParameter('app.files_dir') . DIRECTORY_SEPARATOR . $this->container->getParameter('app.access_filename');

        $this->installAccessData();

        $log = null;

        // Конфигурация
        $this->bitrix_app = new \Bitrix24\Bitrix24(false, $log);
        $this->initBitrix24Config();

        if ($this->bitrix_app->isAccessTokenExpire()) {
            $this->refreshAccessData();
            $this->initBitrix24Config();
        }
    }

    public function getApp() {
        return $this->bitrix_app;
    }

    public function getHost() {
        $result = '';
        if ($this->access_data) {
            $result = 'https://' . $this->parseDomain($this->access_data['client_endpoint']);
        }
        return $result;
    }

    protected function initBitrix24Config() {
        $this->bitrix_app->setApplicationId($this->container->getParameter('bitrix24.app_id'));
        $this->bitrix_app->setApplicationSecret($this->container->getParameter('bitrix24.app_secret'));

        $this->bitrix_app->setApplicationScope([$this->access_data['scope']]);
        $this->bitrix_app->setDomain($this->parseDomain($this->access_data['client_endpoint']));
        $this->bitrix_app->setMemberId($this->access_data['member_id']);
        $this->bitrix_app->setAccessToken($this->access_data['access_token']);
        $this->bitrix_app->setRefreshToken($this->access_data['refresh_token']);
    }

    protected function refreshAccessData() {
        $this->bitrix_app->setRedirectUri('Заглушка');

        $access_data = $this->bitrix_app->getNewAccessToken();
        $access_data = json_encode($access_data);

        file_put_contents($this->access_filename, $access_data);

        $this->installAccessData();
    }

    protected function installAccessData() {
        $access_data = file_get_contents($this->access_filename);
        $this->access_data = (array)json_decode($access_data);
    }

    protected function parseDomain($string) {
        $domain = str_replace('/rest/', '', str_replace('https://', '', $string));
        return $domain;
    }
}