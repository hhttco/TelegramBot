1. 安装应用程序
2. 安装依赖
3. 配置文件

安装 php7.3 nginx 环境
```
apt -y update && apt -y install curl wget git unzip nginx mariadb-server vim \
php7.3-common php7.3-cli php7.3-fpm \
php7.3-gd php7.3-mysql php7.3-mbstring php7.3-curl \
php7.3-xml php7.3-xmlrpc php7.3-zip php7.3-intl \
php7.3-bz2 php7.3-bcmath php7.3-fileinfo php-gmp

systemctl enable --now nginx mariadb php7.3-fpm
systemctl restart php7.3-fpm
systemctl restart mariadb

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

配置BOT
vim config/telegram.php

创建BOT
curl -X POST https://域名/telegram/set/webhook
```
