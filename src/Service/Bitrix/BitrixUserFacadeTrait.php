<?php


namespace App\Service\Bitrix;


trait BitrixUserFacadeTrait
{
    use BitrixBaseFacadeTrait;

    protected $users;

    protected function parseUsers($filter = []) {
        $this->beforeParse();

        $User = new \Bitrix24\User\User($this->bitrix_app);
        $result = $User->get(
            ["ID" ], // Sort
            ["DESC" ], // Order
            $filter, // Filter
        );

        $this->users = $result;
    }

    public function getUsers($filter = [], $update = false) {
        if (!$this->users || $update) {
            $this->parseUsers($filter);
        }
        return $this->users;
    }
}