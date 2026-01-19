<?php

namespace Plugin\YijiePay\Library;

use Illuminate\Support\Facades\Http;

// 确保 Signature 类可用（如果自动加载失败）
if (!class_exists('Plugin\\YijiePay\\Library\\Signature', false)) {
    $signatureFile = __DIR__ . DIRECTORY_SEPARATOR . 'Signature.php';
    if (file_exists($signatureFile) && is_readable($signatureFile)) {
        require_once $signatureFile;
    }
}

// 使用完整命名空间引用 Signature，避免 use 语句导致的自动加载问题
use Plugin\YijiePay\Library\Signature;

class Client
{
    public function __construct(
        protected string $baseUrl,
        protected string $merchantId,
        protected string $secretKey,
    ) {
    }

    public function createOrder(array $payload): array
    {
        return Http::timeout(15)
            ->asForm()
            ->acceptJson()
            ->post($this->baseUrl . '/api/pay/create', $payload)
            ->json();
    }

    public function queryOrder(string $orderId): array
    {
        $payload = [
            'merchantId' => $this->merchantId,
            'orderId' => $orderId,
            'timestamp' => time(),
        ];
        $payload['sign'] = Signature::sign($payload, $this->secretKey);

        return Http::timeout(10)
            ->acceptJson()
            ->post($this->baseUrl . '/gateway/queryOrder', $payload)
            ->json();
    }

    public function placeholderCheckoutUrl(array $payload): string
    {
        return $this->baseUrl . '/checkout?' . http_build_query($payload);
    }
}
