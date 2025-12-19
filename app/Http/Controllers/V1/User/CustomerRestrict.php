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
            Log::info('CustomerRestrict skip: no user', [
                'path' => $request->path(),
                'headers_auth' => $request->header('authorization'),
            ]);
            return $next($request);
        }

        $customerIds = array_filter(array_map('intval', explode(',', (string) env('CUSTOMER_IDS', ''))));
        $dashboardOnlyIds = array_filter(array_map('intval', explode(',', (string) env('DASHBOARD_ONLY_IDS', ''))));
        
        $isCustomerRestricted = !empty($customerIds) && in_array((int) $user->id, $customerIds, true);
        $isDashboardOnly = !empty($dashboardOnlyIds) && in_array((int) $user->id, $dashboardOnlyIds, true);

        // 如果用户不在任何限制列表中，直接放行
        if (!$isCustomerRestricted && !$isDashboardOnly) {
            Log::info('CustomerRestrict skip: user not in env list', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'customer_ids' => $customerIds,
                'dashboard_only_ids' => $dashboardOnlyIds,
            ]);
            return $next($request);
        }

        // 仪表板专用用户：只允许访问仪表板相关接口
        if ($isDashboardOnly) {
            Log::info('CustomerRestrict dashboard-only hit', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'dashboard_only_ids' => $dashboardOnlyIds,
            ]);

            $dashboardWhitelist = [
                'stat/getOverride',
                'stat/getStats',
                'stat/getServerLastRank',
                'stat/getServerYesterdayRank',
                'stat/getOrder',
                'stat/getStatUser',
                'stat/getRanking',
                'stat/getStatRecord',
                'stat/getTrafficRank',
                'system/getSystemStatus',
                'system/getQueueStats',
                'system/getQueueWorkload',
                'system/getQueueMasters',
                'system/getLogClearStats',
                'traffic-reset/stats',
            ];

            $parts = explode('/', trim($request->path(), '/'));
            $fullPath = $request->path();
            if (count($parts) > 3) {
                $fullPath = implode('/', array_slice($parts, 3));
            }

            if (in_array($fullPath, $dashboardWhitelist, true)) {
                Log::info('CustomerRestrict dashboard whitelist pass', [
                    'user_id' => $user->id,
                    'path' => $request->path(),
                    'fullPath' => $fullPath,
                ]);
                return $next($request);
            }

            // 特殊处理：user/fetch 接口返回空数据格式，避免前端报错
            $path = trim($request->path(), '/');
            if ($fullPath === 'user/fetch' || str_ends_with($path, 'user/fetch')) {
                Log::info('CustomerRestrict dashboard-only: user/fetch return empty data', [
                    'user_id' => $user->id,
                    'path' => $request->path(),
                    'fullPath' => $fullPath,
                    'trimmed_path' => $path,
                ]);
                return response()->json([
                    'total' => 0,
                    'current_page' => (int) $request->input('current', 1),
                    'per_page' => (int) $request->input('pageSize', 10),
                    'last_page' => 1,
                    'data' => []
                ], 200);
            }

            // 特殊处理：server/group/fetch 接口返回空数据格式，避免前端报错
            $path = trim($request->path(), '/');
            if ($fullPath === 'server/group/fetch' || str_ends_with($path, 'server/group/fetch')) {
                Log::info('CustomerRestrict dashboard-only: server/group/fetch return empty data', [
                    'user_id' => $user->id,
                    'path' => $request->path(),
                    'fullPath' => $fullPath,
                    'trimmed_path' => $path,
                ]);
                return $this->success([]);
            }

            Log::warning('CustomerRestrict dashboard-only deny', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'fullPath' => $fullPath,
            ]);

            $response = $this->fail([403001, '无权限访问此功能']);
            return response()->json($response->getData(true), 200);
        }

        // 原有的 CUSTOMER_IDS 限制逻辑
        Log::info('CustomerRestrict hit', [
            'user_id' => $user->id,
            'path' => $request->path(),
            'customer_ids' => $customerIds,
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

        // 特殊处理：user/fetch 接口返回空数据格式，避免前端报错
        $path = trim($request->path(), '/');
        if ($fullPath === 'user/fetch' || str_ends_with($path, 'user/fetch')) {
            Log::info('CustomerRestrict: user/fetch return empty data', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'fullPath' => $fullPath,
                'trimmed_path' => $path,
            ]);
            return response()->json([
                'total' => 0,
                'current_page' => (int) $request->input('current', 1),
                'per_page' => (int) $request->input('pageSize', 10),
                'last_page' => 1,
                'data' => []
            ], 200);
        }

        // 特殊处理：server/group/fetch 接口返回空数据格式，避免前端报错
        $path = trim($request->path(), '/');
        if ($fullPath === 'server/group/fetch' || str_ends_with($path, 'server/group/fetch')) {
            Log::info('CustomerRestrict: server/group/fetch return empty data', [
                'user_id' => $user->id,
                'path' => $request->path(),
                'fullPath' => $fullPath,
                'trimmed_path' => $path,
            ]);
            return $this->success([]);
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

