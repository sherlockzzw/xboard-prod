<?php

namespace App\Http\Middleware;

use App\Helpers\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CustomerRestrict
{
    use ApiResponse;

    public function handle(Request $request, Closure $next): Response
    {
	      // 入口日志，便于线上确认是否进入中间件
        Log::info('CustomerRestrict start', [
            'path' => $request->path(),
            'auth_header' => $request->header('authorization'),
        ]);
	    /** @var \App\Models\User|null $user */
	   
        $user = Auth::guard('sanctum')->user();
        if (!$user) {
            return $next($request);
        }

        $ids = array_filter(array_map('intval', explode(',', (string) env('CUSTOMER_IDS', ''))));
      	 
        if (empty($ids) || !in_array((int) $user->id, $ids, true)) {
            return $next($request);
        }

        Log::info('CustomerRestrict hit', [
            'user_id' => $user->id,
            'path' => $request->path(),
            'customer_ids' => $ids,
        ]);

        $whitelist = [
            'ticket/fetch',
            'plan/fetch',
            'server/group/fetch',
            'user/fetch',
            'user/generate',
            'user/update',
            'stat/getOrder',
            'stat/getTrafficRank',
            'stat/getStats',
            'system/getSystemStatus',
            'system/getQueueStats'
        ];

        $parts = explode('/', trim($request->path(), '/'));
        $fullPath = $request->path();
        if (count($parts) > 3) {
            $fullPath = implode('/', array_slice($parts, 3));
        }
       
        if (in_array($fullPath, $whitelist, true)) {
            Log::info('CustomerRestrict whitelist pass', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'fullPath' => $fullPath,
            ]);
            return $next($request);
        }

        Log::warning('CustomerRestrict deny', [
            'user_id' => $user->id,
            'path' => $request->path(),
            'fullPath' => $fullPath,
        ]);

        $response = $this->fail([403001, '无权限访问此功能']);
        return response()->json($response->getData(true), 200);
    }
}

