<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Snowmaking extends \App\Controllers\Snowmaking
{
    use AppResponses;

    /** Locked until its achievement is done, as on the website (checkFeatureUnlock) */
    private function locked(): ?ResponseInterface
    {
        return isFeatureUnlocked('snowmaking') ? null : $this->fail('Locked until its achievement is completed.', 403, ['code' => 'feature_locked']);
    }

    public function show(): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        return $this->ok($this->pageData());
    }

    public function toggleAction(int $id): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        return $this->fromAction($this->toggle($id), fn() => $this->pageData());
    }

    public function toggleAllAction(): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->toggleAll(), fn() => $this->pageData());
    }

    public function repairAction(int $id): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        return $this->fromAction($this->repair($id), fn() => $this->pageData());
    }

    public function assignAction(): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        return $this->fromAction($this->assignCannon(), fn() => $this->pageData());
    }

    public function sellAction(int $id): ResponseInterface
    {
        if ($locked = $this->locked()) return $locked;
        return $this->fromAction($this->sell($id), fn() => $this->pageData());
    }
}
