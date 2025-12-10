<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;

class AdminIpWhitelist
{
    use ApiResponse;

    private const REDIS_KEY = 'admin:ip_whitelist';

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
       

        return $next($request);
    }
}


