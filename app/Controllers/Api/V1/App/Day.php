<?php

namespace App\Controllers\Api\V1\App;

use App\Controllers\BaseController;
use App\Controllers\Api\V1\Day as WebDay;
use App\Libraries\Clock;
use CodeIgniter\HTTP\ResponseInterface;

/** The Android app's own-pace endpoints: GET day, POST day/next, POST day/autopilot */
class Day extends BaseController
{
    use AppResponses;

    public function show(): ResponseInterface
    {
        return $this->ok(Clock::state((int) auth()->id()));
    }

    public function next(): ResponseInterface
    {
        [$status, $body] = WebDay::advance((int) auth()->id());
        return $status === 200 ? $this->ok($body['state'] ?? null, $body['message']) : $this->fail($body['message'], $status);
    }

    public function autopilot(): ResponseInterface
    {
        [$status, $body] = WebDay::autopilotFor((int) auth()->id(), $this->request);
        return $status === 200 ? $this->ok($body['state'], $body['message']) : $this->fail($body['message'], $status);
    }
}
