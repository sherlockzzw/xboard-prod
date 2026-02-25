<?php

namespace App\Http\Controllers\V1\Passport;

use App\Helpers\ResponseEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\Passport\AuthForget;
use App\Http\Requests\Passport\AuthLogin;
use App\Http\Requests\Passport\AuthRegister;
use App\Services\Auth\LoginService;
use App\Services\Auth\MailLinkService;
use App\Services\Auth\RegisterService;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    protected MailLinkService $mailLinkService;
    protected RegisterService $registerService;
    protected LoginService $loginService;

    public function __construct(
        MailLinkService $mailLinkService,
        RegisterService $registerService,
        LoginService $loginService
    ) {
        $this->mailLinkService = $mailLinkService;
        $this->registerService = $registerService;
        $this->loginService = $loginService;
    }

    /**
     * 通过邮件链接登录
     */
    public function loginWithMailLink(Request $request)
    {
        $params = $request->validate([
            'email' => 'required|email:strict',
            'redirect' => 'nullable'
        ]);

        [$success, $result] = $this->mailLinkService->handleMailLink(
            $params['email'],
            $request->input('redirect')
        );

        if (!$success) {
            return $this->fail($result);
        }

        return $this->success($result);
    }

    /**
     * 用户注册
     */
    public function register(AuthRegister $request)
    {
        [$success, $result] = $this->registerService->register($request);

        if (!$success) {
            return $this->fail($result);
        }

        $authService = new AuthService($result);
        return $this->success($authService->generateAuthData());
    }

    public function login(AuthLogin $request)
{
    $email = $request->input('email');
    $password = $request->input('password');

    // 获取客户端IP并尝试转换为IPv4格式
    $clientIp = $this->getIPv4FromRequest($request);

    // 日志记录IP信息
    $ips = array_map('trim', Redis::smembers('admin:ip_whitelist'));
    Log::channel('deprecations')->info('Admin login pre-check IP whitelist', [
        'request_ip' => $clientIp,
        'original_ip' => $request->getClientIp(), // 记录原始IP用于调试
        'whitelist' => $ips,
    ]);

    $user = User::where('email', $email)->first();
    
    if ($user && !empty($user->is_admin)) {
        // 检查是否为有效的IPv4地址
        if (!filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            Log::channel('deprecations')->warning('Admin login failed - Invalid IPv4 address', [
                'user_id' => $user->id,
                'email' => $email,
                'client_ip' => $clientIp,
                'original_ip' => $request->getClientIp()
            ]);
            return $this->fail([403, '仅支持IPv4地址访问']);
        }

        // 对IPv4地址进行截断处理
        $clientC = implode('.', array_slice(explode('.', $clientIp), 0, 3));
        
        $whitelistC = array_map(function($ip) {
            // 确保白名单中的IP也是有效的IPv4
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return implode('.', array_slice(explode('.', $ip), 0, 3));
            }
            return null;
        }, $ips);
        
        // 过滤掉null值
        $whitelistC = array_filter($whitelistC);

        // 调试日志
        Log::channel('deprecations')->info('IP whitelist comparison', [
            'client_c' => $clientC,
            'whitelist_c' => $whitelistC,
        ]);

        if (empty($whitelistC) || !in_array($clientC, $whitelistC, true)) {
            Log::channel('deprecations')->warning('Admin login blocked - IP not in whitelist', [
                'user_id' => $user->id,
                'email' => $email,
                'client_ip' => $clientIp,
                'client_c' => $clientC
            ]);
            return $this->fail([403, '管理员登录 IP 不在白名单中']);
        }
    }

    [$success, $result] = $this->loginService->login($email, $password);
    
    Log::channel('deprecations')->info('Login Info', [
        'userId' => $user->id ?? null,
        'userEmail' => $user->email ?? $email,
        'login_success' => $success
    ]);
    
    if (!$success) {
        return $this->fail($result);
    }

    $authService = new AuthService($result);
    return $this->success($authService->generateAuthData());
}

/**
 * 从请求中获取IPv4地址
 * 
 * @param Request $request
 * @return string|null
 */
private function getIPv4FromRequest($request)
{
    // 1. 尝试从 X-Forwarded-For 获取第一个IPv4地址
    $forwardedFor = $request->header('X-Forwarded-For');
    if ($forwardedFor) {
        $ips = explode(',', $forwardedFor);
        foreach ($ips as $ip) {
            $ip = trim($ip);
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                return $ip;
            }
        }
    }
    
    // 2. 尝试从 X-Real-IP 获取
    $realIp = $request->header('X-Real-IP');
    if ($realIp && filter_var($realIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $realIp;
    }
    
    // 3. 获取客户端IP
    $clientIp = $request->getClientIp();
    
    // 4. 检查是否是 IPv4 映射的 IPv6 地址 (::ffff:192.168.1.1)
    if (strpos($clientIp, '::ffff:') === 0) {
        $ipv4 = substr($clientIp, 7);
        if (filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return $ipv4;
        }
    }
    
    // 5. 检查是否是纯IPv6地址
    if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // 记录IPv6访问
        Log::channel('deprecations')->notice('IPv6 access detected', [
            'ipv6' => $clientIp,
            'headers' => [
                'x-forwarded-for' => $request->header('X-Forwarded-For'),
                'x-real-ip' => $request->header('X-Real-IP'),
            ]
        ]);
        
        // 如果无法获取IPv4，返回null
        return null;
    }
    
    // 6. 如果是普通IPv4地址
    if (filter_var($clientIp, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        return $clientIp;
    }
    
    return null;
}

    /**
     * 通过token登录
     */
    public function token2Login(Request $request)
    {
        // 处理直接通过token重定向
        if ($token = $request->input('token')) {
            $redirect = '/#/login?verify=' . $token . '&redirect=' . ($request->input('redirect', 'dashboard'));

            return redirect()->to(
                admin_setting('app_url')
                    ? admin_setting('app_url') . $redirect
                    : url($redirect)
            );
        }

        // 处理通过验证码登录
        if ($verify = $request->input('verify')) {
            $userId = $this->mailLinkService->handleTokenLogin($verify);

            if (!$userId) {
                return response()->json([
                    'message' => __('Token error')
                ], 400);
            }

            $user = \App\Models\User::find($userId);

            if (!$user) {
                return response()->json([
                    'message' => __('User not found')
                ], 400);
            }

            $authService = new AuthService($user);

            return response()->json([
                'data' => $authService->generateAuthData()
            ]);
        }

        return response()->json([
            'message' => __('Invalid request')
        ], 400);
    }

    /**
     * 获取快速登录URL
     */
    public function getQuickLoginUrl(Request $request)
    {
        $authorization = $request->input('auth_data') ?? $request->header('authorization');

        if (!$authorization) {
            return response()->json([
                'message' => ResponseEnum::CLIENT_HTTP_UNAUTHORIZED
            ], 401);
        }

        $user = AuthService::findUserByBearerToken($authorization);

        if (!$user) {
            return response()->json([
                'message' => ResponseEnum::CLIENT_HTTP_UNAUTHORIZED_EXPIRED
            ], 401);
        }

        $url = $this->loginService->generateQuickLoginUrl($user, $request->input('redirect'));
        return $this->success($url);
    }

    /**
     * 忘记密码处理
     */
    public function forget(AuthForget $request)
    {
        [$success, $result] = $this->loginService->resetPassword(
            $request->input('email'),
            $request->input('email_code'),
            $request->input('password')
        );

        if (!$success) {
            return $this->fail($result);
        }

        return $this->success(true);
    }
}
