<?php

namespace Plugin\ApiPay;

// 手动加载依赖类文件（解决压缩上传后自动加载失效的问题）
$libraryDir = __DIR__ . DIRECTORY_SEPARATOR . 'Library';
$signatureFile = $libraryDir . DIRECTORY_SEPARATOR . 'Signature.php';
$clientFile = $libraryDir . DIRECTORY_SEPARATOR . 'Client.php';

if (!class_exists('Plugin\\ApiPay\\Library\\Signature', false)) {
    if (file_exists($signatureFile) && is_readable($signatureFile)) {
        require_once $signatureFile;
    } else {
        \Illuminate\Support\Facades\Log::error('ApiPay: Signature.php not found or not readable', [
            'file' => $signatureFile,
            'exists' => file_exists($signatureFile),
            'readable' => is_readable($signatureFile)
        ]);
    }
}

if (!class_exists('Plugin\\ApiPay\\Library\\Client', false)) {
    if (file_exists($clientFile) && is_readable($clientFile)) {
        require_once $clientFile;
    } else {
        \Illuminate\Support\Facades\Log::error('ApiPay: Client.php not found or not readable', [
            'file' => $clientFile,
            'exists' => file_exists($clientFile),
            'readable' => is_readable($clientFile)
        ]);
    }
}

use App\Contracts\PaymentInterface;
use App\Exceptions\ApiException;
use App\Services\Plugin\AbstractPlugin;
use Plugin\ApiPay\Library\Client;
use Plugin\ApiPay\Library\Signature;

class Plugin extends AbstractPlugin implements PaymentInterface
{
    protected Client $client;

    public function boot(): void
    {
        $this->filter('available_payment_methods', function ($methods) {
            if ($this->getConfig('enabled', true)) {
                $methods['ApiPay'] = [
                    'name' => $this->getConfig('display_name', 'API支付'),
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
            'gateway_url' => [
                'label' => '网关地址',
                'type' => 'string',
                'required' => true,
                'description' => '支付网关地址，例如：https://example.com（程序会自动拼接 /Pay_Index.html）'
            ],
            'member_id' => [
                'label' => '商户号',
                'type' => 'string',
                'required' => true
            ],
            'key' => [
                'label' => '商户密钥',
                'type' => 'string',
                'required' => true
            ],
            'bank_code' => [
                'label' => '支付通道编码',
                'type' => 'string',
                'required' => true,
                'description' => '按支付平台后台配置填写，如 alipay / wxpay / 具体银行编码'
            ],
            'product_name' => [
                'label' => '商品名称',
                'type' => 'string',
                'default' => 'XBoard 订阅',
                'description' => '将提交给支付网关的商品名称'
            ],
        ];
    }

    public function pay($order): array
    {
        $gatewayBase = rtrim((string) $this->getConfig('gateway_url'), '/');
        if ($gatewayBase === '') {
            throw new ApiException('网关地址未配置');
        }

        $this->client = new Client(
            $gatewayBase,
            (string) $this->getConfig('member_id'),
            (string) $this->getConfig('key')
        );

        $applyDate = date('Y-m-d H:i:s');
        $amount = sprintf('%.2f', $order['total_amount'] / 100);
        $clientIp = request()?->ip() ?? '127.0.0.1';

        $payload = [
            'pay_memberid' => (string) $this->getConfig('member_id'),
            'pay_orderid' => (string) $order['trade_no'],
            'pay_applydate' => $applyDate,
            'pay_bankcode' => (string) $this->getConfig('bank_code'),
            'pay_notifyurl' => (string) $order['notify_url'],
            'pay_callbackurl' => (string) $order['return_url'],
            'pay_amount' => $amount,
            'pay_productname' => '白羊加速订单',
            'pay_ip' => $clientIp,
            'pay_attach' => (string) $order['user_id'],
        ];

        $payload['pay_md5sign'] = Signature::buildOrderSign($payload, (string) $this->getConfig('key'));
        $data = $this->client->createOrder($payload);
        if (!is_array($data)) {
            throw new ApiException('支付网关返回格式错误');
        }

        if ((string) ($data['status'] ?? '') !== '1') {
            $message = (string) ($data['msg'] ?? '下单失败');
            throw new ApiException($message);
        }

        $payUrl = $data['h5_url'] ?? $data['pay_url'] ?? null;
        if (!$payUrl) {
            throw new ApiException('支付网关未返回支付链接');
        }

        return [
            'type' => 1,
            'data' => $payUrl
        ];
    }

    public function notify($params): array|bool
    {
        $expectedSign = Signature::buildNotifySign($params, (string) $this->getConfig('key'));
        $receivedSign = strtoupper((string) ($params['sign'] ?? ''));

        if ($receivedSign === '' || $expectedSign !== $receivedSign) {
            return false;
        }

        if ((string) ($params['returncode'] ?? '') !== '00') {
            return false;
        }

        return [
            'trade_no' => (string) ($params['orderid'] ?? ''),
            'callback_no' => (string) ($params['transaction_id'] ?? ''),
            'custom_result' => 'OK',
        ];
    }
}

