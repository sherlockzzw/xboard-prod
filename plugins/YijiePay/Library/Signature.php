<?php

namespace Plugin\YijiePay\Library;

class Signature
{
    /**
     * 生成签名（RSA SHA256）
     *
     * @param array $params   待签名参数（包含业务字段，不含 sign/sign_type）
     * @param string $privateKey 商户私钥（PKCS8 / PKCS1 字符串）
     */
    public static function sign(array $params, string $privateKey): string
    {
        // 1. 过滤空值、数组、字节流，并移除 sign / sign_type
        unset($params['sign'], $params['sign_type']);
        $filtered = [];
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null || is_array($v)) {
                continue;
            }
            $filtered[$k] = $v;
        }

        // 2. ASCII 字典序排序
        ksort($filtered);

        // 3. 组装待签名字符串：key=value 以 & 连接
        $signingString = urldecode(http_build_query($filtered));

        // 4. 使用商户私钥进行 RSA-SHA256 签名，结果做 base64 编码
        $key = openssl_pkey_get_private(self::normalizePrivateKey($privateKey));
        if ($key === false) {
            throw new \RuntimeException('Invalid YijiePay private key');
        }

        $signature = '';
        $ok = openssl_sign($signingString, $signature, $key, OPENSSL_ALGO_SHA256);
        if (is_resource($key)) {
            openssl_pkey_free($key);
        }

        if (!$ok) {
            throw new \RuntimeException('YijiePay sign failed');
        }

        return base64_encode($signature);
    }

    /**
     * 验签（使用平台公钥）
     *
     * @param array $params 返回的参数（包含 sign / sign_type）
     * @param string $publicKey 平台公钥
     */
    public static function verify(array $params, string $publicKey): bool
    {
        $sign = $params['sign'] ?? '';
        if ($sign === '') {
            return false;
        }

        unset($params['sign'], $params['sign_type']);

        $filtered = [];
        foreach ($params as $k => $v) {
            if ($v === '' || $v === null || is_array($v)) {
                continue;
            }
            $filtered[$k] = $v;
        }
        ksort($filtered);
        $signingString = urldecode(http_build_query($filtered));

        $key = openssl_pkey_get_public(self::normalizePublicKey($publicKey));
        if ($key === false) {
            return false;
        }

        $ok = openssl_verify($signingString, base64_decode($sign), $key, OPENSSL_ALGO_SHA256);
        if (is_resource($key)) {
            openssl_pkey_free($key);
        }

        return $ok === 1;
    }

    /**
     * 规范化私钥为 PEM 格式
     */
    protected static function normalizePrivateKey(string $key): string
    {
        $key = trim($key);
        if (str_starts_with($key, '-----BEGIN') && str_ends_with($key, '-----END PRIVATE KEY-----')) {
            return $key;
        }

        // 只给了一行 base64 内容的情况
        $key = str_replace(["\r", "\n", ' '], '', $key);
        $wrapped = chunk_split($key, 64, "\n");
        return "-----BEGIN PRIVATE KEY-----\n{$wrapped}-----END PRIVATE KEY-----";
    }

    /**
     * 规范化公钥为 PEM 格式
     */
    protected static function normalizePublicKey(string $key): string
    {
        $key = trim($key);
        if (str_starts_with($key, '-----BEGIN') && str_ends_with($key, '-----END PUBLIC KEY-----')) {
            return $key;
        }

        $key = str_replace(["\r", "\n", ' '], '', $key);
        $wrapped = chunk_split($key, 64, "\n");
        return "-----BEGIN PUBLIC KEY-----\n{$wrapped}-----END PUBLIC KEY-----";
    }
}
