<?php

namespace App\Services;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;


class WipeService
{

    private $baseUrl = "https://stellerphonewipeapiprod.azurewebsites.net/api/";

    private $usernameKey = "APPSETTING_API_USERNAME_STELLER_PHONE_WIPE_API";

    private $passwordKey = "APPSETTING_API_PASSWORD_STELLER_PHONE_WIPE_API";

    /**
     * @param string $id
     * @param string $type
     * @return Response
     */
    public function auth(string $username, string $password): Response
    {
        $response = Http::withBasicAuth(getenv($this->usernameKey), getenv($this->passwordKey))
            ->post($this->baseUrl . "v1/wipeusercontroller/loginauth?username={$username}&password={$password}");
        return $response;
    }

    /**
     * @param string $auth_token
     * @return Response
     */
    public function findbytoken(string $auth_token): Response
    {
        $response = Http::withBasicAuth(getenv($this->usernameKey), getenv($this->passwordKey))
            ->get($this->baseUrl . "v1/wipeusercontroller/findbytoken?auth_token={$auth_token}");
        return $response;
    }

    /**
     * @param string $id
     * @param int $status
     * @return Response
     */
    public function updateStatus(string $id, int $status): Response
    {
        $response = Http::withBasicAuth(getenv($this->usernameKey), getenv($this->passwordKey))
            ->patch($this->baseUrl . "v1/wipeusercontroller/patch", ['id' => $id, 'status' => $status]);
        return $response;
    }


}
