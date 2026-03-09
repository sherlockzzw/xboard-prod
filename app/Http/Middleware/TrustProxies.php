<?php

namespace App\Http\Middleware;

use Illuminate\Http\Middleware\TrustProxies as Middleware;
use Illuminate\Http\Request;

class TrustProxies extends Middleware
{
    /**
     * 可信代理列表
     *
     * 这里使用 '*' 表示信任所有反向代理（包括你这层 Nginx），
     * 这样 Laravel 会优先从 X-Forwarded-For 中解析真实客户端 IP。
     *
     * @var array<int, string>|string|null
     */
    protected $proxies = '*';

    /**
     * 代理头映射
     * @var int
     */
    protected $headers =
        Request::HEADER_X_FORWARDED_FOR |
        Request::HEADER_X_FORWARDED_HOST |
        Request::HEADER_X_FORWARDED_PORT |
        Request::HEADER_X_FORWARDED_PROTO;
}

