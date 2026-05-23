<?php

namespace Plugin\ApiPay\Library;

use App\Exceptions\ApiException;
use Illuminate\Support\Facades\Http;

class Client
{
    public function __construct(
        protected string $baseUrl,
        protected string $memberId,
        protected string $key,
    ) {
    }

    /**
     * 调用 API 支付统一下单接口
     *
     * POST {baseUrl}/Pay_Index.html
     */
    public function createOrder(array $payload): array
    {
        $endpoint = $this->resolveCreateOrderEndpoint();
        $response = Http::timeout(15)
            ->asForm()
            ->acceptJson()
            ->post($endpoint, $payload);

        if (!$response->ok()) {
            throw new ApiException('支付网关请求失败');
        }

        return $response->json();
    }

    /**
     * 兼容两种配置：
     * 1) https://domain.com
     * 2) https://domain.com/Pay_Index.html
     */
    private function resolveCreateOrderEndpoint(): string
    {
        $url = trim($this->baseUrl);

        if (preg_match('/\/Pay_Index\.html(?:\?.*)?$/i', $url)) {
            return $url;
        }

        return rtrim($url, '/') . '/Pay_Index.html';
    }
}

