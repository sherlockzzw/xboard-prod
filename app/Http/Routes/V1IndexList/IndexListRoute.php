<?php

namespace App\Http\Routes\V1IndexList;

use App\Http\Routes\V1\ClientRoute;
use App\Http\Routes\V1\GuestRoute;
use App\Http\Routes\V1\PassportRoute;
use App\Http\Routes\V1\ServerRoute;
use App\Http\Routes\V1\UserRoute;
use Illuminate\Contracts\Routing\Registrar;

/**
 * 兼容路由前缀：
 * - 原来是 `/api/v1/*`
 * - 新增一套 `/index/list/*`
 *
 * 业务逻辑完全复用现有的 V1 路由文件，不改动控制器/原路由。
 */
class IndexListRoute
{
    public function map(Registrar $router): void
    {
        (new GuestRoute())->map($router);
        (new PassportRoute())->map($router);
        (new UserRoute())->map($router);
        (new ServerRoute())->map($router);
        (new ClientRoute())->map($router);
    }
}


