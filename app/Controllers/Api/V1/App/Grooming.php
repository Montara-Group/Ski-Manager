<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Grooming extends \App\Controllers\Grooming
{
    use AppResponses;

    /** Locked until its achievement is done, as on the website (checkFeatureUnlock) */
    private function locked(): ?ResponseInterface
    {
        return isFeatureUnlocked('grooming') ? null : $this->fail('Locked until its achievement is completed.', 403, ['code' => 'feature_locked']);
    }

    public function show(): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        return $this->ok($this->pageData());
    }

    public function groomSingleAction(): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->groomSingle(), fn() => $this->pageData());
    }

    public function groomAllAction(): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->groomAll(), fn() => $this->pageData());
    }
}
