<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class WipeService
{
    public function auth(string $username, string $password): Response
    {
        return $this->client()->post($this->url('v1/wipeusercontroller/loginauth'), [
            'username' => $username,
            'password' => $password,
        ]);
    }

    public function findByToken(string $authToken): Response
    {
        $url = $this->url('v1/wipeusercontroller/findbytoken');

        if (config('services.wipe_api.token_lookup_method') === 'post') {
            return $this->client()->post($url, [
                'auth_token' => $authToken,
            ]);
        }

        return $this->client()->get($url, [
            'auth_token' => $authToken,
        ]);
    }

    public function updateStatus(string $id, int $status, int $wipedBy): Response
    {
        return $this->client()->patch($this->url('v1/wipeusercontroller/patch'), [
            'id' => $id,
            'status' => $status,
            'wiped_by' => $wipedBy,
        ]);
    }

    private function client(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout(3)
            ->timeout(8)
            ->withBasicAuth(
                (string) config('services.wipe_api.username'),
                (string) config('services.wipe_api.password')
            );
    }

    private function url(string $path): string
    {
        $baseUrl = rtrim((string) config('services.wipe_api.base_url'), '/');

        return $baseUrl.'/'.ltrim($path, '/');
    }
}
