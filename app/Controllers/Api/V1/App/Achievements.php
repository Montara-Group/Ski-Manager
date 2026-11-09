<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Achievements extends \App\Controllers\Achievements
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok($this->pageData());
    }

    public function claimAction(int $id): ResponseInterface
    {
        return $this->fromAction($this->claim($id), fn() => $this->pageData());
    }

    public function claimAllAction(): ResponseInterface
    {
        return $this->fromAction($this->claimAll(), fn() => $this->pageData());
    }
}
