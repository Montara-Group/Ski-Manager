<?php

namespace App\Controllers\Api\V1\App;

use CodeIgniter\HTTP\ResponseInterface;

class Buildings extends \App\Controllers\Buildings
{
    use AppResponses;

    /**
     * The web gates each building category by its page (hotels, restaurants, ...) through FeatureGate. App paths
     * are /buildings/..., which FeatureGate doesn't map, so the category's flag is checked here.
     */
    private function categoryOff(string $type): ?ResponseInterface
    {
        $route = $this->getBuildingDefs()[$type]['route'] ?? null;
        $flag = $route ? \App\Filters\FeatureGate::flagForPath('/' . $route) : null;
        if ($flag !== null && !featureEnabled($flag) && !\App\Libraries\Shell::isAdmin()) {
            return $this->fail('This feature is currently unavailable.', 403, ['code' => 'feature_off']);
        }
        return null;
    }

    public function show(string $type = 'hotel'): ResponseInterface
    {
        if ($off = $this->categoryOff($type)) return $off;
        try {
            return $this->ok($this->pageData($type));
        } catch (\CodeIgniter\Exceptions\PageNotFoundException $e) {
            return $this->fail('Building type not found.', 404);
        }
    }

    public function buildAction(): ResponseInterface
    {
        if (empty($this->request->getPost()) && $json = $this->request->getJSON(true)) {
            $this->request->setGlobal('post', $json);
        }
        $type = (string) ($this->request->getPost('type') ?: 'hotel');
        if ($off = $this->categoryOff($type)) return $off;
        return $this->fromAction($this->build(), fn() => $this->pageData($type));
    }

    public function toggleAction(int $id): ResponseInterface
    {
        $building = $this->model->where('user_id', auth()->id())->find($id);
        $type = $building ? $building['building_type'] : 'hotel';
        if ($off = $this->categoryOff($type)) return $off;
        return $this->fromAction($this->toggle($id), fn() => $this->pageData($type));
    }

    public function upgradeAction(int $id): ResponseInterface
    {
        $building = $this->model->where('user_id', auth()->id())->find($id);
        $type = $building ? $building['building_type'] : 'hotel';
        if ($off = $this->categoryOff($type)) return $off;
        return $this->fromAction($this->upgrade($id), fn() => $this->pageData($type));
    }

    public function sellAction(int $id): ResponseInterface
    {
        $building = $this->model->where('user_id', auth()->id())->find($id);
        $type = $building ? $building['building_type'] : 'hotel';
        if ($off = $this->categoryOff($type)) return $off;
        return $this->fromAction($this->sell($id), fn() => $this->pageData($type));
    }
}
