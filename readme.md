1. 安装应用程序
2. 安装依赖
3. 配置文件

安装 php7.3 nginx 环境
```
vim /etc/nginx/conf.d/tgbot.conf
server {
    server_name 域名;
    root        /var/www/TelegramBot/public;
    index       index.php;
    client_max_body_size 0;

    location /downloads {
    }

    location / {
        try_files $uri $uri/ /index.php$is_args$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/var/run/php/php7.3-fpm.sock;
    }
}
```

```
cd /var/www
git clone https://github.com/hhttco/TelegramBot.git
cd TelegramBot

composer install
cp .env.example .env
php artisan key:generate
vim .env

创建BOT
curl -X POST https://域名/telegram/set/webhook
```
