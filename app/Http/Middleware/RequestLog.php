<?php

namespace App\Http\Middleware;

use App\Models\AdminRequestLog;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\Auth;

class RequestLog
{
    /**
     * Handle an incoming request.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $startAt = microtime(true);

        $response = $next($request);

        try {
            $adminId = 0;

            /** @var User|null $adminUser */
            $adminUser = Auth::guard('sanctum')->user();
            if ($adminUser && $adminUser->is_admin) {
                $adminId = (int) $adminUser->id;
            }

            // 兜底：如果有地方把 ID 注入到 request attributes，也支持
            if ($adminId <= 0) {
                if ($request->attributes->has('admin_id')) {
                    $adminId = (int) $request->attributes->get('admin_id');
                } elseif ($request->attributes->has('user_id')) {
                    $adminId = (int) $request->attributes->get('user_id');
                }
            }

            $contentType = (string) $request->header('Content-Type', '');
            $isJson = stripos($contentType, 'application/json') !== false;

            $body = null;
            if ($isJson) {
                // 仅记录 JSON 请求体；失败时不影响主流程
                $body = $request->all();
            }

            $durationMs = (int) round((microtime(true) - $startAt) * 1000);

            AdminRequestLog::create([
                'admin_id' => $adminId,
                'method' => $request->method(),
                'path' => $request->path(),
                'status_code' => method_exists($response, 'getStatusCode') ? (int) $response->getStatusCode() : 0,
                'duration_ms' => $durationMs,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'body' => $body,
                'created_at' => time(),
            ]);
        } catch (\Throwable $e) {
            // 日志写入失败不影响接口正常返回
        }

        return $response;
    }
}

