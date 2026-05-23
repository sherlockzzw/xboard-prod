<?php

namespace Plugin\ApiPay\Library;

class Signature
{
    /**
     * 下单签名（按文档指定字段）
     */
    public static function buildOrderSign(array $payload, string $key): string
    {
        $signFields = [
            'pay_amount',
            'pay_applydate',
            'pay_bankcode',
            'pay_callbackurl',
            'pay_memberid',
            'pay_notifyurl',
            'pay_orderid',
        ];

        $signData = self::extractFields($payload, $signFields);
        $base = urldecode(http_build_query($signData)) . '&key=' . $key;

        return strtoupper(md5($base));
    }

    /**
     * 回调签名（按文档指定字段）
     */
    public static function buildNotifySign(array $params, string $key): string
    {
        $signFields = [
            'amount',
            'datetime',
            'memberid',
            'orderid',
            'returncode',
            'transaction_id',
        ];

        $signData = self::extractFields($params, $signFields);
        $base = urldecode(http_build_query($signData)) . '&key=' . $key;

        return strtoupper(md5($base));
    }

    private static function extractFields(array $source, array $fields): array
    {
        $result = [];
        foreach ($fields as $field) {
            $value = $source[$field] ?? '';
            if ($value !== '' && $value !== null) {
                $result[$field] = (string) $value;
            }
        }
        ksort($result, SORT_STRING);

        return $result;
    }
}

