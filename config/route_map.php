<?php
return [
    // guest
    // /api/v1/guest/plan/fetch
    'g7Kx9aP2Lm' => 'App\Http\Controllers\V1\Guest\PlanController@fetch',
    // /api/v1/guest/telegram/webhook
    'uT3dF8qWz1' => 'App\Http\Controllers\V1\Guest\TelegramController@webhook',
    // /api/v1/guest/payment/notify
    'pL9xV2cB7n' => 'App\Http\Controllers\V1\Guest\PaymentController@notify',
    // /api/v1/guest/comm/config
    'r5YtH1kQe8' => 'App\Http\Controllers\V1\Guest\CommController@config',
    // /api/v1/guest/comm/getAppConfig
    'mN4bJ7sD2w' => 'App\Http\Controllers\V1\Guest\CommController@getAppConfig',

    // passport
    // /api/v1/passport/auth/register
    'a8Xc3DkL0p' => 'App\Http\Controllers\V1\Passport\AuthController@register',
    // /api/v1/passport/auth/login
    'zQ1nB5vR6t' => 'App\Http\Controllers\V1\Passport\AuthController@login',
    // /api/v1/passport/auth/token2Login
    'oP9sW2xE4y' => 'App\Http\Controllers\V1\Passport\AuthController@token2Login',
    // /api/v1/passport/auth/forget
    'd7FhK3mU1c' => 'App\Http\Controllers\V1\Passport\AuthController@forget',
    // /api/v1/passport/auth/getQuickLoginUrl
    'L0pQ8vT2sX' => 'App\Http\Controllers\V1\Passport\AuthController@getQuickLoginUrl',
    // /api/v1/passport/auth/loginWithMailLink
    'b6M3cN9eR1' => 'App\Http\Controllers\V1\Passport\AuthController@loginWithMailLink',
    // /api/v1/passport/comm/sendEmailVerify
    'kP2xV8zA5q' => 'App\Http\Controllers\V1\Passport\CommController@sendEmailVerify',
    // /api/v1/passport/comm/pv
    'wR4tY7uI3o' => 'App\Http\Controllers\V1\Passport\CommController@pv',

    // user
    // /api/v1/user/user/info
    'U1aX9dK3Lp' => 'App\Http\Controllers\V1\User\UserController@info',
    // /api/v1/user/user/changePassword
    'Q7wE2rT5yU' => 'App\Http\Controllers\V1\User\UserController@changePassword',
    // /api/v1/user/user/update
    'H8jK4mN2bV' => 'App\Http\Controllers\V1\User\UserController@update',
    // /api/v1/user/user/getSubscribe
    'Z3xC7vB1nM' => 'App\Http\Controllers\V1\User\UserController@getSubscribe',
    // /api/v1/user/user/getStat
    'P9oI4uY6tR' => 'App\Http\Controllers\V1\User\UserController@getStat',
    // /api/v1/user/user/checkLogin
    'eW5rT8yU1i' => 'App\Http\Controllers\V1\User\UserController@checkLogin',
    // /api/v1/user/user/transfer
    'S2dF6gH9jK' => 'App\Http\Controllers\V1\User\UserController@transfer',
    // /api/v1/user/user/getQuickLoginUrl
    'L4kJ8hG2fD' => 'App\Http\Controllers\V1\User\UserController@getQuickLoginUrl',
    // /api/v1/user/user/getActiveSession
    'A7sD3fG5hJ' => 'App\Http\Controllers\V1\User\UserController@getActiveSession',
    // /api/v1/user/user/removeActiveSession
    'M9nB2vC6xZ' => 'App\Http\Controllers\V1\User\UserController@removeActiveSession',
    // /api/v1/user/order/save
    'X1cV4bN8mL' => 'App\Http\Controllers\V1\User\OrderController@save',
    // /api/v1/user/order/checkout
    'J7kH3gF2dS' => 'App\Http\Controllers\V1\User\OrderController@checkout',
    // /api/v1/user/order/check
    'T5yU8iO2pP' => 'App\Http\Controllers\V1\User\OrderController@check',
    // /api/v1/user/order/detail
    'N4bV7cX1zA' => 'App\Http\Controllers\V1\User\OrderController@detail',
    // /api/v1/user/order/fetch
    'F2gH6jK9lQ' => 'App\Http\Controllers\V1\User\OrderController@fetch',
    // /api/v1/user/order/getPaymentMethod
    'D8sA3wQ5eR' => 'App\Http\Controllers\V1\User\OrderController@getPaymentMethod',
    // /api/v1/user/order/cancel
    'C6xZ1aS4dF' => 'App\Http\Controllers\V1\User\OrderController@cancel',
    // /api/v1/user/plan/fetch
    'B9nM2kL5jH' => 'App\Http\Controllers\V1\User\PlanController@fetch',
    // /api/v1/user/invite/save
    'V3cX7zA1sD' => 'App\Http\Controllers\V1\User\InviteController@save',
    // /api/v1/user/invite/fetch
    'G5hJ9kL2mN' => 'App\Http\Controllers\V1\User\InviteController@fetch',
    // /api/v1/user/invite/details
    'Y6tR3eW8qP' => 'App\Http\Controllers\V1\User\InviteController@details',
    // /api/v1/user/ticket/reply
    'K1jH4gF7dS' => 'App\Http\Controllers\V1\User\TicketController@reply',
    // /api/v1/user/ticket/close
    'R2eW5qT8yU' => 'App\Http\Controllers\V1\User\TicketController@close',
    // /api/v1/user/ticket/save
    'U8iO3pP6aS' => 'App\Http\Controllers\V1\User\TicketController@save',
    // /api/v1/user/ticket/fetch
    'I4uY7tR1eW' => 'App\Http\Controllers\V1\User\TicketController@fetch',
    // /api/v1/user/ticket/withdraw
    'O2pP5aS9dF' => 'App\Http\Controllers\V1\User\TicketController@withdraw',
    // /api/v1/user/server/fetch
    'KXj23232Ld' => 'App\Http\Controllers\V1\User\ServerController@fetch',
    // /api/v1/user/coupon/check
    'W1qE4rT7yU' => 'App\Http\Controllers\V1\User\CouponController@check',
    // /api/v1/user/giftCard/check
    'E8rT2yU5iO' => 'App\Http\Controllers\V1\User\GiftCardController@check',
    // /api/v1/user/giftCard/redeem
    'R3tY6uI9oP' => 'App\Http\Controllers\V1\User\GiftCardController@redeem',
    // /api/v1/user/giftCard/history
    'T7yU1iO4pP' => 'App\Http\Controllers\V1\User\GiftCardController@history',
    // /api/v1/user/giftCard/detail
    'Y2uI5oP8aS' => 'App\Http\Controllers\V1\User\GiftCardController@detail',
    // /api/v1/user/giftCard/types
    'U6iO9pP3aS' => 'App\Http\Controllers\V1\User\GiftCardController@types',
    // /api/v1/user/telegram/getBotInfo
    'I1oP4aS7dF' => 'App\Http\Controllers\V1\User\TelegramController@getBotInfo',
    // /api/v1/user/comm/config
    'O5pP8aS2dF' => 'App\Http\Controllers\V1\User\CommController@config',
    // /api/v1/user/comm/getStripePublicKey
    'P9aS3dF6gH' => 'App\Http\Controllers\V1\User\CommController@getStripePublicKey',
    // /api/v1/user/knowledge/getCategory
    'A4sD7fG1hJ' => 'App\Http\Controllers\V1\User\KnowledgeController@getCategory',
    // /api/v1/user/stat/getTrafficLog
    'S8dF2gH5jK' => 'App\Http\Controllers\V1\User\StatController@getTrafficLog',
    // /api/v1/user/notice/fetch
    'D3fG6hJ9kL' => 'App\Http\Controllers\V1\User\NoticeController@fetch',
    // /api/v1/user/knowledge/fetch
    'F7gH1jK4lQ' => 'App\Http\Controllers\V1\User\KnowledgeController@fetch',
    // /api/v1/user/user/fetch
    'G2hJ5kL8mN' => 'App\Http\Controllers\V1\User\UserController@fetch',

    // server
    // /api/v1/server/uniproxy/config
    'H6jK9lQ3wE' => 'App\Http\Controllers\V1\Server\UniProxyController@config',
    // /api/v1/server/uniproxy/user
    'J1kL4mN7bV' => 'App\Http\Controllers\V1\Server\UniProxyController@user',
    // /api/v1/server/uniproxy/push
    'K5lQ8wE2rT' => 'App\Http\Controllers\V1\Server\UniProxyController@push',
    // /api/v1/server/uniproxy/alive
    'L9mN3bV6cX' => 'App\Http\Controllers\V1\Server\UniProxyController@alive',
    // /api/v1/server/uniproxy/alivelist
    'M4nB7vC1xZ' => 'App\Http\Controllers\V1\Server\UniProxyController@alivelist',
    // /api/v1/server/uniproxy/status
    'N8bV2cX5zA' => 'App\Http\Controllers\V1\Server\UniProxyController@status',
    // /api/v1/server/shadowsocksTidalab/user
    'B3vC6xZ9aS' => 'App\Http\Controllers\V1\Server\ShadowsocksTidalabController@user',
    // /api/v1/server/shadowsocksTidalab/submit
    'V7cX1zA4sD' => 'App\Http\Controllers\V1\Server\ShadowsocksTidalabController@submit',
    // /api/v1/server/trojanTidalab/config
    'C2xZ5aS8dF' => 'App\Http\Controllers\V1\Server\TrojanTidalabController@config',
    // /api/v1/server/trojanTidalab/user
    'X6zA9sD3fG' => 'App\Http\Controllers\V1\Server\TrojanTidalabController@user',
    // /api/v1/server/trojanTidalab/submit
    'Z1aS4dF7gH' => 'App\Http\Controllers\V1\Server\TrojanTidalabController@submit',

    // client
    // /api/v1/client/client/subscribe
    'cL9xA2pQ8w' => 'App\Http\Controllers\V1\Client\ClientController@subscribe',
    // /api/v1/client/app/getConfig
    'vB7nM3kL5j' => 'App\Http\Controllers\V1\Client\AppController@getConfig',
    // /api/v1/client/app/getVersion
    'nM2kL8jH4g' => 'App\Http\Controllers\V1\Client\AppController@getVersion',
];

