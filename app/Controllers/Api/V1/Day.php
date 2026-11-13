<?php

namespace App\Controllers\Api\V1;

use App\Libraries\Clock;
use CodeIgniter\HTTP\ResponseInterface;

/** POST /api/v1/day/next and /api/v1/day/autopilot: own-pace seasons (App\Libraries\Clock) */
class Day extends ApiController
{
    public function next(): ResponseInterface
    {
        [$status, $body] = self::advance((int) auth()->id());
        return $status === 200 ? $this->ok($body['state'] ?? null, $body['message']) : $this->fail($body['message'], $status);
    }

    public function autopilot(): ResponseInterface
    {
        [$status, $body] = self::autopilotFor((int) auth()->id(), $this->request);
        return $status === 200 ? $this->ok($body['state'], $body['message']) : $this->fail($body['message'], $status);
    }

    /** Shared with the Android API (Api\V1\App\Day): [HTTP status, { message, state? }] */
    public static function advance(int $userId): array
    {
        if (!Clock::enabledFor($userId)) return [403, ['message' => 'Your resort follows the shared season calendar.']];
        if (function_exists('isMaintenanceMode') && isMaintenanceMode()) return [503, ['message' => 'The game is paused for maintenance.']];
        $result = Clock::advance($userId, 'manual');
        return [$result['ok'] ? 200 : 409, $result];
    }

    public static function autopilotFor(int $userId, $request): array
    {
        if (!Clock::enabledFor($userId)) return [403, ['message' => 'Your resort follows the shared season calendar.']];
        try {
            $json = $request->getJSON(true);
        } catch (\Throwable) {
            $json = null;
        }
        $input = is_array($json) ? $json : (array) $request->getPost();
        if (!array_key_exists('on', $input)) return [422, ['message' => 'Say whether autopilot is on or off.']];
        $on = filter_var($input['on'], FILTER_VALIDATE_BOOLEAN);
        Clock::setAutopilot($userId, $on);
        return [200, ['message' => $on ? 'Autopilot is on.' : 'Autopilot is off. Your resort moves only when you press Next day.', 'state' => Clock::state($userId)]];
    }
}
