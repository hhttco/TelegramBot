# 1.安装PHP环境
```
apt -y update && apt -y install curl wget git unzip nginx
```

```
apt -y install php7.3-common php7.3-cli php7.3-fpm \
php7.3-gd php7.3-mysql php7.3-mbstring php7.3-curl \
php7.3-xml php7.3-xmlrpc php7.3-zip php7.3-intl \
php7.3-bz2 php7.3-bcmath php-redis php7.3-fileinfo php-gmp
```

## 2.设置启动
```
systemctl enable --now nginx php7.3-fpm
systemctl restart php7.3-fpm
```

## 3.安装依赖
```
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/bin/composer
```

## 4.安装应用
```
cd /var/www
git clone https://github.com/hhttco/TelegramBot.git
cd TelegramBot
chown -R www-data:www-data /var/www/TelegramBot
chmod -R 755 /var/www/TelegramBot
composer install
cp .env.example .env
php artisan key:generate
```

## 5.修改BOT配置文件
```
vim .env
rm /var/www/TelegramBot/config/telegram.php
vim /var/www/TelegramBot/config/telegram.php
```

## 6.修改nginx配置文件
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

## 7.安装证书

## 8.启动BOT
```
curl -X POST https://域名/telegram/set/webhook
```
