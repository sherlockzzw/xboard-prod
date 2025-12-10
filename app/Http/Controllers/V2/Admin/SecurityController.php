<?php

namespace App\Http\Controllers\V2\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class SecurityController extends Controller
{
    private const REDIS_KEY = 'admin:ip_whitelist';

    public function fetchIpWhitelist()
    {
        $ips = Redis::smembers(self::REDIS_KEY);
        sort($ips);

        return $this->success($ips);
    }

    public function saveIpWhitelist(Request $request)
    {
        $request->validate([
            'ips' => 'required|array|min:1',
            'ips.*' => 'string',
        ]);

        // 去掉空行、去重、保持原顺序
        $ips = [];
        foreach ($request->input('ips') as $ip) {
            $ip = trim((string) $ip);
            if ($ip === '') {
                continue;
            }
            if (!in_array($ip, $ips, true)) {
                $ips[] = $ip;
            }
        }

        Redis::del(self::REDIS_KEY);
        if (!empty($ips)) {
            Redis::sadd(self::REDIS_KEY, ...$ips);
        }

        return $this->success(true);
    }
}

