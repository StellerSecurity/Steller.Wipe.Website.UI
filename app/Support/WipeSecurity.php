<?php

namespace App\Support;

use Illuminate\Http\Request;

class WipeSecurity
{
    public static function clientIp(Request $request): string
    {
        $frontDoorId = (string) config('wipe_security.azure_front_door.id', '');
        $requestFrontDoorId = (string) $request->header('X-Azure-FDID', '');

        if ($frontDoorId !== '' && $requestFrontDoorId !== '' && hash_equals($frontDoorId, $requestFrontDoorId)) {
            $socketIp = trim((string) $request->header('X-Azure-SocketIP', ''));

            if (filter_var($socketIp, FILTER_VALIDATE_IP)) {
                return $socketIp;
            }
        }

        $ip = (string) $request->ip();

        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : 'unknown';
    }

    public static function clientKey(Request $request): string
    {
        $ip = self::clientIp($request);

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $packed = inet_pton($ip);

            if ($packed !== false) {
                return 'ipv6-64:'.bin2hex(substr($packed, 0, 8));
            }
        }

        return 'ip:'.$ip;
    }

    public static function credentialKey(Request $request): string
    {
        $method = (string) $request->input('method', 'unknown');

        if ($method === '0') {
            $identifier = mb_strtolower(trim((string) $request->input('username', '')));
            $type = 'username';
        } elseif ($method === '1') {
            $identifier = trim((string) $request->input('token', ''));
            $type = 'token';
        } else {
            $identifier = 'invalid-method';
            $type = 'unknown';
        }

        $pepper = (string) config('app.key', 'wipe-rate-limit');
        $digest = hash_hmac('sha256', $type.'|'.$identifier, $pepper);

        return $type.':'.$digest;
    }
}
