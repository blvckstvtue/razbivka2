# CS Source Demo Archive - PHP версия

Мощен и красив уеб интерфейс за преглеждане и сваляне на демо файлове от CS Source сървъри, създаден с PHP и AJAX технологии.

## 🚀 Функции

### ✨ Основни функции
- **Модерен дизайн** базиран на NiDE.GG
- **Динамично зареждане** с AJAX - без презареждане на страницата
- **Реално време статистики** за всички сървъри
- **Responsive дизайн** за мобилни устройства
- **Сигурност** - защита срещу XSS и path traversal атаки

### 🔧 Технически функции
- **PHP клас архитектура** за лесна поддръжка
- **REST API** за интеграция с други приложения
- **CORS поддръжка** за cross-origin заявки
- **Централизирана конфигурация** на сървърите
- **Error handling** с подробни съобщения
- **Логиране** на грешки и активност

### 📊 Разширени функции
- **Статистики** - преглед на активността по сървъри
- **Сортиране** по дата, име или размер
- **Филтриране** само на .dem файлове
- **Форматиране** на размери и дати
- **Бързо обновяване** на съдържанието

## 📁 Структура на файловете

```
/home/martink1337/hub/demosite/
├── index.php              # Основна страница (без AJAX)
├── index-ajax.php         # Подобрена страница с AJAX
├── api.php               # REST API endpoint
├── style/
│   └── style.css         # CSS стилове
└── img/
    └── demos_archive_2024.png
```

## 🛠 Инсталация

### 1. Системни изисквания

```bash
# PHP 8.0+ с необходимите разширения
sudo apt update
sudo apt install php8.2-fpm php8.2-cli php8.2-common php8.2-curl php8.2-json

# Nginx
sudo apt install nginx

# Стартиране на услугите
sudo systemctl enable php8.2-fpm nginx
sudo systemctl start php8.2-fpm nginx
```

### 2. Създаване на директориите

```bash
# Създаване на уеб директорията
mkdir -p /home/martink1337/hub/demosite

# Копиране на файловете
cp index.php /home/martink1337/hub/demosite/
cp index-ajax.php /home/martink1337/hub/demosite/
cp api.php /home/martink1337/hub/demosite/
cp -r style /home/martink1337/hub/demosite/
cp -r img /home/martink1337/hub/demosite/

# Права за достъп
chown -R martink1337:www-data /home/martink1337/hub/demosite/
chmod -R 755 /home/martink1337/hub/demosite/
chmod -R 644 /home/martink1337/hub/demosite/*.php
```

### 3. Nginx конфигурация

Заменете вашата конфигурация с `nginx-php.conf`:

```bash
# Копиране на конфигурацията
sudo cp nginx-php.conf /etc/nginx/sites-available/demos
sudo ln -sf /etc/nginx/sites-available/demos /etc/nginx/sites-enabled/

# Премахване на default сайта (по желание)
sudo rm -f /etc/nginx/sites-enabled/default

# Тестване и рестартиране
sudo nginx -t
sudo systemctl reload nginx
```

### 4. PHP-FPM настройка

Проверете че PHP-FPM работи правилно:

```bash
# Проверка на статуса
sudo systemctl status php8.2-fpm

# Проверка на socket файла
ls -la /var/run/php/php8.2-fpm.sock

# Ако имате различна версия на PHP, променете в nginx-php.conf:
# fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;  # за PHP 8.1
# fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;  # за PHP 7.4
```

### 5. Тестване

```bash
# Тест на API endpoint
curl "http://localhost:8084/api.php?action=getServers"

# Тест на основната страница
curl -I "http://localhost:8084/"

# Проверка на демо директориите
ls -la /home/martink1337/hub/cssource/zescape/cstrike/demos/
ls -la /home/martink1337/hub/cssource/csgomod/cstrike/demos/
```

## 🔧 Конфигурация

### Добавяне на нови сървъри

Редактирайте файловете `index.php` и `api.php`:

```php
$servers = [
    'zescape' => [
        'name' => 'CS:S ZEscape Server',
        'path' => '/home/martink1337/hub/cssource/zescape/cstrike/demos/'
    ],
    'csgomod' => [
        'name' => 'CS:S CSGOMod Server', 
        'path' => '/home/martink1337/hub/cssource/csgomod/cstrike/demos/'
    ],
    'newserver' => [
        'name' => 'New Server Name',
        'path' => '/path/to/new/server/demos/'
    ]
];
```

### Промяна на настройките за сигурност

В `nginx-php.conf` можете да добавите допълнителни ограничения:

```nginx
# Rate limiting
limit_req_zone $binary_remote_addr zone=api:10m rate=10r/s;

location /api.php {
    limit_req zone=api burst=20 nodelay;
    # ... други настройки
}

# IP whitelist (по желание)
location /api.php {
    allow 192.168.1.0/24;
    allow 127.0.0.1;
    deny all;
    # ... други настройки
}
```

## 📖 API документация

### Endpoint: `/api.php`

#### Получаване на файлове от сървър
```
GET /api.php?action=getFiles&server=zescape
```

**Отговор:**
```json
{
    "server": "CS:S ZEscape Server",
    "serverId": "zescape",
    "count": 15,
    "files": [
        {
            "name": "demo_001.dem",
            "size": 12345678,
            "sizeFormatted": "11.8 MB",
            "mtime": 1703123456,
            "mtimeFormatted": "21.12.2023 14:30",
            "url": "/zescape/demo_001.dem",
            "downloadUrl": "/zescape/demo_001.dem"
        }
    ]
}
```

#### Получаване на списък със сървъри
```
GET /api.php?action=getServers
```

**Отговор:**
```json
{
    "servers": [
        {
            "id": "zescape",
            "name": "CS:S ZEscape Server",
            "count": 15
        }
    ]
}
```

#### Получаване на статистики
```
GET /api.php?action=getStats
```

**Отговор:**
```json
{
    "stats": [
        {
            "serverId": "zescape",
            "serverName": "CS:S ZEscape Server",
            "fileCount": 15,
            "lastModified": "21.12.2023 14:30"
        }
    ]
}
```

## 🔍 Troubleshooting

### Проблем: 502 Bad Gateway
**Причина:** PHP-FPM не работи правилно
**Решение:**
```bash
sudo systemctl status php8.2-fpm
sudo systemctl restart php8.2-fpm
sudo tail -f /var/log/nginx/demos_error.log
```

### Проблем: API връща празен отговор
**Причина:** Грешен път към демо файловете
**Решение:**
```bash
# Проверете пътищата в конфигурацията
ls -la /home/martink1337/hub/cssource/zescape/cstrike/demos/

# Проверете PHP error log
sudo tail -f /var/log/php8.2-fpm.log
```

### Проблем: CORS грешки
**Причина:** Неправилни CORS настройки
**Решение:** Проверете че в `nginx-php.conf` има:
```nginx
add_header Access-Control-Allow-Origin *;
add_header Access-Control-Allow-Methods "GET, POST, OPTIONS";
```

### Проблем: Файловете не се изтеглят
**Причина:** Неправилни Nginx location настройки
**Решение:** Проверете alias директивите в конфигурацията

## 🔒 Сигурност

### Препоръчани мерки:
1. **Редовни обновления** на PHP и Nginx
2. **Firewall правила** за ограничаване на достъпа
3. **SSL сертификат** за HTTPS връзки
4. **Backup** на конфигурациите
5. **Мониторинг** на логовете

### Примерен firewall (UFW):
```bash
sudo ufw allow 22/tcp      # SSH
sudo ufw allow 8084/tcp    # Demo archive
sudo ufw enable
```

## 📈 Performance

### Оптимизации:
- **OPcache** за PHP
- **Gzip компресия** за статични файлове
- **Browser кеширане** на CSS/JS
- **Rate limiting** за API заявки

### PHP OPcache настройка:
```ini
; /etc/php/8.2/fpm/conf.d/10-opcache.ini
opcache.enable=1
opcache.memory_consumption=128
opcache.max_accelerated_files=4000
opcache.revalidate_freq=60
```

## 📦 Версии

- **2.0.0** - Основна PHP версия
- **2.1.0** - AJAX версия с подобрени функции

## 🤝 Поддръжка

За проблеми или въпроси:
1. Проверете логовете: `/var/log/nginx/demos_error.log`
2. Тествайте API: `curl http://localhost:8084/api.php?action=getServers`
3. Проверете PHP грешки: `/var/log/php8.2-fpm.log`

## 📄 Лиценз

Базиран на дизайна на NiDE.GG. Свободен за лично ползване.