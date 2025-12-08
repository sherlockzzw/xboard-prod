# Xboard Docker 部署命令速查

> 说明：凡是带 `docker compose exec web ...` 的命令都在 **web 容器** 内执行（无需手动进入容器）；若临时进入容器，可用 `docker compose exec -it web sh`。

## 1. 初始化

```bash
cd /Users/sherlock/code/xboard/xboard
cp .env.example .env
mkdir -p .docker/.data/{redis,mysql} .docker/nginx/{conf.d,certs}
composer install --no-dev --prefer-dist
```

## 2. 启动依赖

```bash
docker compose up -d mysql redis
```

若 3306 被占用，在 `compose.yaml` 设置 `MYSQL_PORT=13306`。

## 3. 运行安装脚本

```bash
chown -R 1001:1001 ./.docker/.data/redis
chmod -R 775 ./.docker/.data/redis

rm .env && touch .env
docker compose exec web php artisan xboard:install   # 在 web 容器执行
```

按提示完成配置并记录后台 URL 与管理员账号。

## 4. 迁移与数据（可选补救）

```bash
docker compose exec web php artisan migrate --seed   # 在 web 容器执行
```

## 5. 启动完整服务

```bash
docker compose up -d
docker compose ps
```

后台地址示例：`http://localhost/<secure_path>`。

## 6. 管理员账号维护

```bash
docker compose exec web php artisan tinker \
  --execute="\App\Models\User::where('is_admin',1)->pluck('email')"   # web 容器

docker compose exec web php artisan reset:password admin@demo.com NewPass123   # web 容器
```

## 7. 常用运维

```bash
docker compose exec web php artisan config:clear      # web 容器
docker compose exec web php artisan cache:clear      # web 容器
docker compose restart web horizon

docker compose pull
docker compose run -it --rm web php artisan xboard:update   # 临时进入 web 容器
docker compose up -d
```

连接数据库：`mysql -h 127.0.0.1 -P ${MYSQL_PORT:-3306} -uxboard -pxboardpass xboard`。

docker compose exec web php artisan tinker --execute="\$path = admin_setting('secure_path', admin_setting('frontend_admin_path', hash('crc32b', config('app.key')))); echo \"后台访问地址: http://xboard.com/\$path\n\";"
