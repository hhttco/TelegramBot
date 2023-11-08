1. 安装应用程序
2. 安装依赖
3. 配置文件

安装 nginx 环境
```
cd /var/www
git clone https://github.com/hhttco/TelegramBot.git
cd TelegramBot

composer install
cp .env.example .env
php artisan key:generate
vim .env
```
