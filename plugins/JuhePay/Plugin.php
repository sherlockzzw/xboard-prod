<?php

namespace Plugin\JuhePay;

// 手动加载依赖类文件（解决压缩上传后自动加载失效的问题）
$libraryDir   = __DIR__ . DIRECTORY_SEPARATOR . 'Library';
$signatureFile = $libraryDir . DIRECTORY_SEPARATOR . 'Signature.php';
$clientFile    = $libraryDir . DIRECTORY_SEPARATOR . 'Client.php';

if (!class_exists('Plugin\\JuhePay\\Library\\Signature', false)) {
    if (file_exists($signatureFile) && is_readable($signatureFile)) {
        require_once $signatureFile;
    } else {
        \Illuminate\Support\Facades\Log::error('JuhePay: Signature.php not found or not readable', [
            'file'     => $signatureFile,
            'exists'   => file_exists($signatureFile),
            'readable' => is_readable($signatureFile),
        ]);
    }
}

if (!class_exists('Plugin\\JuhePay\\Library\\Client', false)) {
    if (file_exists($clientFile) && is_readable($clientFile)) {
        require_once $clientFile;
    } else {
        \Illuminate\Support\Facades\Log::error('JuhePay: Client.php not found or not readable', [
            'file'     => $clientFile,
            'exists'   => file_exists($clientFile),
            'readable' => is_readable($clientFile),
        ]);
    }
}

use App\Contracts\PaymentInterface;
use App\Exceptions\ApiException;
use App\Services\Plugin\AbstractPlugin;
use App\Services\Plugin\HookManager;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Plugin\JuhePay\Library\Client;
use Plugin\JuhePay\Library\Signature;

class Plugin extends AbstractPlugin implements PaymentInterface
{
    protected Client $client;

    public function boot(): void
    {
        // 在支付方式列表中注册“聚合易支付”
        $this->filter('available_payment_methods', function ($methods) {
            if ($this->getConfig('enabled', true)) {
                $methods['JuhePay'] = [
                    'name'        => $this->getConfig('display_name', '聚合易支付'),
                    'icon'        => $this->getConfig('icon', '💳'),
                    'plugin_code' => $this->getPluginCode(),
                    'type'        => 'plugin',
                ];
            }
            return $methods;
        });
    }

    public function form(): array
    {
        return [
            'merchant_id' => [
                'label'       => '商户号(pid)',
                'type'        => 'string',
                'required'    => true,
                'description' => '聚合易支付分配的商户ID（pid）',
            ],
            'secret_key' => [
                'label'       => '商户私钥（RSA）',
                'type'        => 'text',
                'required'    => true,
                'description' => '用于 RSA(SHA256) 签名的商户私钥，PEM 格式',
            ],
            'platform_public_key' => [
                'label'       => '平台公钥（用于验签，推荐配置）',
                'type'        => 'text',
                'description' => '聚合易支付平台公钥，用于异步通知验签',
            ],
            'api_base_url' => [
                'label'       => 'API 网关',
                'type'        => 'string',
                'default'     => 'https://api.52luxing.com',
                'description' => '聚合易支付 API 网关，默认 `https://api.52luxing.com`',
            ],
            'method' => [
                'label'       => '接口类型(method)',
                'type'        => 'string',
                'default'     => 'web',
                'description' => '接口类型：web / jump / jsapi / app / scan / applet',
            ],
            'device' => [
                'label'       => '设备类型(device)',
                'type'        => 'string',
                'default'     => 'pc',
                'description' => '设备类型：pc / mobile / qq / wechat / alipay，仅通用网页支付(web)需要传',
            ],
            'pay_channel' => [
                'label'          => '支付方式(type)',
                'type'           => 'select',
                'default'        => 'alipay',
                'select_options' => [
                    ['value' => 'alipay', 'label' => '支付宝'],
                    ['value' => 'wxpay', 'label' => '微信支付'],
                ],
                'description' => '支付方式，参考聚合易支付文档的支付方式列表',
            ],
        ];
    }

    /**
     * 创建聚合易支付订单
     *
     * @param array $order
     * @return array
     * @throws ApiException
     */
    public function pay($order): array
    {
        $baseUrl = rtrim((string) $this->getConfig('api_base_url', 'https://api.52luxing.com'), '/');

        $this->client = new Client(
            $baseUrl,
            (string) $this->getConfig('merchant_id'),
            (string) $this->getConfig('secret_key')
        );

        // 元 -> 字符串，两位小数
        $money    = sprintf('%.2f', $order['total_amount'] / 100);
        $clientIp = request()?->ip() ?? '127.0.0.1';

        $payload = [
            'pid'          => (int) $this->getConfig('merchant_id'),
            'method'       => $this->getConfig('method', 'web'),
            'type'         => $this->getConfig('pay_channel', 'alipay'),
            'out_trade_no' => $order['trade_no'],
            'notify_url'   => $order['notify_url'],
            'return_url'   => $order['return_url'],
            'name'         => admin_setting('app_name', 'XBoard') . ' 订单 ' . $order['trade_no'],
            'money'        => $money,
            'clientip'     => $clientIp,
            'param'        => (string) $order['user_id'], // 业务扩展参数，回调原样返回
            'timestamp'    => (string) time(),
        ];

        // 仅通用网页支付(web)才需要 device，可配置开关
        $device = (string) $this->getConfig('device', 'pc');
        if ($payload['method'] === 'web' && $device) {
            $payload['device'] = $device;
        }

        // 此处只实现最常见场景：web / jump / jsapi / app / scan / applet
        // 被扫支付(auth_code)、JSAPI(sub_openid/sub_appid) 等，如需使用可在此扩展

        // 默认使用 RSA 签名
        $payload['sign_type'] = 'RSA';
        $payload['sign']      = Signature::sign($payload, (string) $this->getConfig('secret_key'));

        // 记录下单请求，便于排查 pay_type / pay_info 等问题（写入 daily 渠道）
        Log::channel('daily')->info('JuhePay createOrder payload', $payload);

        $response = $this->client->createOrder($payload);

        Log::channel('daily')->info(
            'JuhePay createOrder response',
            is_array($response) ? $response : ['raw' => $response]
        );

        if (!is_array($response) || ($response['code'] ?? 1) !== 0) {
            $message = $response['msg'] ?? 'JuhePay create order failed';
            throw new ApiException($message);
        }

        $payType = $response['pay_type'] ?? 'jump';
        $payInfo = $response['pay_info'] ?? null;

        if (!$payInfo) {
            throw new ApiException('聚合易支付未返回 pay_info');
        }

        // 恢复最初行为：统一走跳转模式，由前端直接跳转 pay_info
        return [
            'type' => 1,
            'data' => $payInfo,
            'meta' => [
                'pay_type' => $payType,
                'trade_no' => $response['trade_no'] ?? null,
            ],
        ];
    }

    /**
     * 异步通知处理
     *
     * @param array $params
     * @return array|bool
     */
    public function notify($params): array|bool
    {
        Log::info('JuhePay notify received', $params);

        // 参考易捷文档的通知参数（具体以平台文档为准）
        $params = Arr::only($params, [
            'pid',
            'trade_no',
            'out_trade_no',
            'api_trade_no',
            'type',
            'trade_status',
            'addtime',
            'endtime',
            'name',
            'money',
            'param',
            'buyer',
            'timestamp',
            'sign',
            'sign_type',
        ]);

        // 如配置了平台公钥，则进行验签（推荐）
        if ($publicKey = (string) $this->getConfig('platform_public_key')) {
            if (!Signature::verify($params, $publicKey)) {
                Log::warning('JuhePay notify signature mismatch', $params);
                return false;
            }
        }

        // 约定成功状态为 TRADE_SUCCESS（与易捷保持一致）
        if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
            Log::info('JuhePay notify ignored due to trade_status', [
                'trade_status' => $params['trade_status'] ?? null,
            ]);
            return false;
        }

        return [
            'trade_no'    => $params['out_trade_no'],
            'callback_no' => $params['trade_no'] ?? null,
        ];
    }
}


