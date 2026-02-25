<?php

namespace Plugin\JuhePay\Library;

/**
 * 聚合易支付 RSA 签名 / 验签封装
 *
 * 注意：
 * - 这里采用常见规则：对除 sign / sign_type 外的所有参数，按 key 升序，
 *   过滤空值后拼接成 `key=value&key2=value2` 字符串，再使用 RSA(SHA256) 签名。
 * - 具体规则请以聚合易支付官方文档为准，如有差异请按实际要求调整 buildSignContent().
 */
class Signature
{
    /**
     * 生成签名
     *
     * @param array  $params     待签名参数（包含 sign/sign_type 也没关系，会被过滤）
     * @param string $privateKey 商户私钥（PEM 格式）
     */
    public static function sign(array $params, string $privateKey): string
    {
        $data = self::buildSignContent($params);

        $privateKey = trim($privateKey);
        if (!str_starts_with($privateKey, '-----BEGIN')) {
            // 如果不是完整 PEM，可在此自动包裹，也可以要求在后台直接粘贴完整 PEM
            $privateKey = "-----BEGIN PRIVATE KEY-----\n" .
                wordwrap($privateKey, 64, "\n", true) .
                "\n-----END PRIVATE KEY-----";
        }

        $res = openssl_pkey_get_private($privateKey);
        if ($res === false) {
            throw new \RuntimeException('JuhePay: Invalid RSA private key');
        }

        $signature = '';
        if (!openssl_sign($data, $signature, $res, OPENSSL_ALGO_SHA256)) {
            throw new \RuntimeException('JuhePay: Failed to generate signature');
        }
        openssl_free_key($res);

        return base64_encode($signature);
    }

    /**
     * 验证签名
     *
     * @param array  $params    包含 sign / sign_type 的完整参数
     * @param string $publicKey 平台公钥（PEM 格式）
     */
    public static function verify(array $params, string $publicKey): bool
    {
        $sign = $params['sign'] ?? '';
        if ($sign === '') {
            return false;
        }

        unset($params['sign'], $params['sign_type']);

        $data = self::buildSignContent($params);

        $publicKey = trim($publicKey);
        if (!str_starts_with($publicKey, '-----BEGIN')) {
            $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
                wordwrap($publicKey, 64, "\n", true) .
                "\n-----END PUBLIC KEY-----";
        }

        $res = openssl_pkey_get_public($publicKey);
        if ($res === false) {
            return false;
        }

        $result = openssl_verify($data, base64_decode($sign, true) ?: '', $res, OPENSSL_ALGO_SHA256);
        openssl_free_key($res);

        return $result === 1;
    }

    /**
     * 按照常见网关规则构造待签名字符串
     *
     * 1. 去掉 sign / sign_type
     * 2. 过滤空值（null / ''）
     * 3. 按 key 升序
     * 4. 用 `key=value` 形式用 `&` 拼接
     */
    protected static function buildSignContent(array $params): string
    {
        unset($params['sign'], $params['sign_type']);

        // 过滤空值
        $params = array_filter($params, function ($value) {
            return $value !== '' && $value !== null;
        });

        ksort($params);

        $pairs = [];
        foreach ($params as $key => $value) {
            $pairs[] = $key . '=' . $value;
        }

        return implode('&', $pairs);
    }
}


