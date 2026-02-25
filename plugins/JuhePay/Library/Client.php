<?php

namespace Plugin\JuhePay\Library;

use Illuminate\Support\Facades\Http;

// 确保 Signature 类可用（如果自动加载失败）
if (!class_exists('Plugin\\JuhePay\\Library\\Signature', false)) {
    $signatureFile = __DIR__ . DIRECTORY_SEPARATOR . 'Signature.php';
    if (file_exists($signatureFile) && is_readable($signatureFile)) {
        require_once $signatureFile;
    }
}

use Plugin\JuhePay\Library\Signature;

class Client
{
    public function __construct(
        protected string $baseUrl,
        protected string $merchantId,
        protected string $secretKey,
    ) {
    }

    /**
     * 调用聚合易支付统一下单接口
     *
     * POST {baseUrl}/api/pay/create
     */
    public function createOrder(array $payload): array
    {
        return Http::timeout(15)
            ->asForm()
            ->acceptJson()
            ->post($this->baseUrl . '/api/pay/create', $payload)
            ->json();
    }

    /**
     * 预留：订单查询接口
     * 具体路径、参数请根据聚合易支付文档自行补充
     */
    public function queryOrder(string $orderId): array
    {
        $payload = [
            'pid'       => $this->merchantId,
            'out_trade_no' => $orderId,
            'timestamp' => time(),
        ];

        $payload['sign_type'] = 'RSA';
        $payload['sign']      = Signature::sign($payload, $this->secretKey);

        // 占位：请根据实际文档修改 URL
        return Http::timeout(10)
            ->acceptJson()
            ->post($this->baseUrl . '/api/pay/query', $payload)
            ->json();
    }
}


