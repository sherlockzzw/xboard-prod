<?php

namespace Plugin\YijiePay;

use App\Contracts\PaymentInterface;
use App\Services\Plugin\AbstractPlugin;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Plugin\YijiePay\Library\Client;
use Plugin\YijiePay\Library\Signature;

class Plugin extends AbstractPlugin implements PaymentInterface
{
    protected Client $client;

    public function boot(): void
    {
        $this->filter('available_payment_methods', function ($methods) {
            if ($this->getConfig('enabled', true)) {
                $methods['YijiePay'] = [
                    'name' => $this->getConfig('display_name', '易捷支付'),
                    'icon' => $this->getConfig('icon', '💳'),
                    'plugin_code' => $this->getPluginCode(),
                    'type' => 'plugin'
                ];
            }
            return $methods;
        });
    }

    public function form(): array
    {
        return [
            'merchant_id' => [
                'label' => '商户号',
                'type' => 'string',
                'required' => true,
                'description' => '易捷支付分配的商户号'
            ],
            'secret_key' => [
                'label' => '商户私钥（RSA）',
                'type' => 'text',
                'required' => true,
                'description' => '用于签名的商户 RSA 私钥（V2接口），请从易捷支付后台 V2 接口配置中复制“商户公钥/私钥”中的私钥'
            ],
            'api_base_url' => [
                'label' => 'API 网关',
                'type' => 'string',
                'default' => 'https://www.yijiepay.cn',
                'description' => '根据环境填写对应网关，如沙箱、生产地址，一般为 https://www.yijiepay.cn'
            ],
            'platform_public_key' => [
                'label' => '平台公钥（可选，用于验签）',
                'type' => 'text',
                'description' => '易捷支付后台 V2 接口配置中的“平台公钥”，用于异步通知和查询结果的验签'
            ],
            'method' => [
                'label' => '接口类型(method)',
                'type' => 'string',
                'default' => 'jump',
                'description' => '接口类型：web / jump / jsapi / app / scan / applet，建议使用 jump 直接返回跳转支付链接'
            ],
            'pay_channel' => [
                'label' => '支付方式(type)',
                'type' => 'string',
                'default' => 'alipay',
                'description' => '支付方式，例如：alipay / wxpay / qqpay 等，参考易捷支付文档的支付方式列表'
            ],
        ];
    }

    public function pay($order): array
    {
        $baseUrl = rtrim($this->getConfig('api_base_url', 'https://www.yijiepay.cn'), '/');
        $this->client = new Client(
            $baseUrl,
            (string) $this->getConfig('merchant_id'),
            (string) $this->getConfig('secret_key')
        );

        $money = sprintf('%.2f', $order['total_amount'] / 100);
        $clientIp = request()?->ip() ?? '127.0.0.1';

        $payload = [
            'pid' => (int) $this->getConfig('merchant_id'),
            'method' => $this->getConfig('method', 'jump'),
            'type' => $this->getConfig('pay_channel', 'alipay'),
            'out_trade_no' => $order['trade_no'],
            'notify_url' => $order['notify_url'],
            'return_url' => $order['return_url'],
            'name' => admin_setting('app_name', 'XBoard') . ' 订单 ' . $order['trade_no'],
            'money' => $money,
            'clientip' => $clientIp,
            'param' => (string) $order['user_id'],
            'timestamp' => (string) time(),
        ];

        $payload['sign_type'] = 'MD5';
        // 使用 V2 接口的 RSA 签名（SHA256WithRSA）
        $payload['sign_type'] = 'RSA';
        $payload['sign'] = Signature::sign($payload, $this->getConfig('secret_key'));

        $response = $this->client->createOrder($payload);

        if (!is_array($response) || ($response['code'] ?? 1) !== 0) {
            $message = $response['msg'] ?? 'YijiePay create order failed';
            throw new \App\Exceptions\ApiException($message);
        }

        $payType = $response['pay_type'] ?? 'jump';
        $payInfo = $response['pay_info'] ?? null;

        if (!$payInfo) {
            throw new \App\Exceptions\ApiException('支付网关未返回 pay_info');
        }

        // 统一走跳转模式，由前端直接跳转 pay_info
        return [
            'type' => 1,
            'data' => $payInfo,
            'meta' => [
                'pay_type' => $payType,
                'trade_no' => $response['trade_no'] ?? null,
            ],
        ];
    }

    public function notify($params): array|bool
    {
        Log::info('YijiePay notify received', $params);
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

        // 如果配置了平台公钥则进行验签（推荐）
        if ($publicKey = (string) $this->getConfig('platform_public_key')) {
            if (!Signature::verify($params, $publicKey)) {
                Log::warning('YijiePay notify signature mismatch', $params);
                return false;
            }
        }

        // 易捷通知成功状态固定为 TRADE_SUCCESS
        if (($params['trade_status'] ?? '') !== 'TRADE_SUCCESS') {
            Log::info('YijiePay notify ignored due to trade_status', ['trade_status' => $params['trade_status'] ?? null]);
            return false;
        }

        return [
            'trade_no' => $params['out_trade_no'],
            'callback_no' => $params['trade_no'] ?? null,
        ];
    }
}
