# 🔒 SSL Setup Ръководство за Demo Сайт

## 📋 Обща стратегия

### Препоръчвана архитектура:
```
Потребител → HTTPS (УЕБ МАШИНА) → HTTP (СЪРВЪР МАШИНА:8084)
             ↓
         SSL Termination
         Reverse Proxy
```

**Предимства:**
- ✅ Централизирано SSL управление 
- ✅ Лесна интеграция с главния сайт
- ✅ По-добра сигурност
- ✅ Автоматично SSL обновяване

---

## 🚀 Стъпка 1: Подготовка на сървър машината

### На сървър машината (където се намират demo файловете):

```bash
# 1. Копиране на demo сайта
cd /home/martink1337/hub/
mkdir -p demosite
cd demosite

# Копиране на файловете от workspace
cp /workspace/*.php .
cp -r /workspace/style .
cp -r /workspace/img .
cp /workspace/nginx-php.conf .

# 2. Настройка на права
chown -R martink1337:www-data /home/martink1337/hub/demosite/
chmod -R 755 /home/martink1337/hub/demosite/
chmod 644 /home/martink1337/hub/demosite/*.php

# 3. Активиране на конфигурацията
sudo cp nginx-php.conf /etc/nginx/sites-available/demos
sudo ln -sf /etc/nginx/sites-available/demos /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default  # по желание
sudo nginx -t
sudo systemctl reload nginx

# 4. Проверка че работи
curl -I http://localhost:8084/
curl "http://localhost:8084/api.php?action=getServers"
```

---

## 🌐 Стъпка 2A: Поддомейн подход (ПРЕПОРЪЧВАМ)

### На уеб машината:

```bash
# 1. Добавяне на DNS запис
# Добавете A record: demos.yourdomain.com → IP_НА_УЕБ_МАШИНАТА

# 2. Инсталиране на certbot (ако няма)
sudo apt update
sudo apt install certbot python3-certbot-nginx

# 3. Копиране на конфигурацията
sudo cp /workspace/nginx-demos-subdomain.conf /etc/nginx/sites-available/demos-ssl

# 4. ВАЖНО: Редактиране на конфигурацията
sudo nano /etc/nginx/sites-available/demos-ssl
# Заменете:
# - demos.yourdomain.com → вашия реален домейн
# - SERVER_MACHINE_IP → IP адреса на сървър машината

# 5. Активиране
sudo ln -sf /etc/nginx/sites-available/demos-ssl /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# 6. Получаване на SSL сертификат
sudo certbot --nginx -d demos.yourdomain.com

# 7. Тестване
curl -I https://demos.yourdomain.com/
```

### Настройка на навбара:
```html
<!-- В главния ви сайт -->
<a href="https://demos.yourdomain.com" class="nav-link">Demos</a>
```

---

## 🌐 Стъпка 2B: Подпапка подход

### На уеб машината:

```bash
# 1. Добавяне в съществуващата конфигурация
sudo nano /etc/nginx/sites-available/your-main-site

# 2. Добавете location блоковете от nginx-demos-subfolder.conf
# ВАЖНО: Заменете SERVER_MACHINE_IP с реалния IP

# 3. Тестване и презареждане
sudo nginx -t
sudo systemctl reload nginx

# 4. Тестване
curl -I https://yourdomain.com/demos/
```

### Настройка на навбара:
```html
<!-- В главния ви сайт -->
<a href="/demos/" class="nav-link">Demos</a>
```

---

## 🔧 Стъпка 3: Оптимизации и сигурност

### Firewall настройки:

```bash
# На сървър машината - затваряме външен достъп до порт 8084
sudo ufw deny 8084/tcp
sudo ufw allow from УЕБ_МАШИНА_IP to any port 8084

# На уеб машината - отваряме HTTPS
sudo ufw allow 443/tcp
sudo ufw allow 80/tcp
```

### Мониторинг:

```bash
# Логове на уеб машината
sudo tail -f /var/log/nginx/demos_ssl_access.log
sudo tail -f /var/log/nginx/demos_ssl_error.log

# Логове на сървър машината  
sudo tail -f /var/log/nginx/demos_access.log
sudo tail -f /var/log/nginx/demos_error.log
```

---

## 🧪 Стъпка 4: Тестване

### Основни тестове:

```bash
# 1. SSL сертификат
openssl s_client -connect demos.yourdomain.com:443 -servername demos.yourdomain.com

# 2. HTTP редирект
curl -I http://demos.yourdomain.com/

# 3. Demo страница
curl -I https://demos.yourdomain.com/

# 4. API endpoint
curl "https://demos.yourdomain.com/api.php?action=getServers"

# 5. File download
curl -I "https://demos.yourdomain.com/zescape/some_demo.dem"
```

### SSL качество тест:
- Отидете на: https://www.ssllabs.com/ssltest/
- Въведете: demos.yourdomain.com
- Целете за A+ рейтинг

---

## 🔄 Стъпка 5: Автоматично SSL обновяване

```bash
# Certbot автоматично добавя cron job, но можете да тествате:
sudo certbot renew --dry-run

# Ръчно обновяване (при нужда):
sudo certbot renew
sudo systemctl reload nginx
```

---

## 🐛 Troubleshooting

### Проблем: 502 Bad Gateway
```bash
# Проверка на connectivity
telnet SERVER_MACHINE_IP 8084

# Проверка на nginx конфигурация
sudo nginx -t

# Логове
sudo tail -f /var/log/nginx/error.log
```

### Проблем: SSL сертификат не се създава
```bash
# Проверка на DNS
nslookup demos.yourdomain.com

# Проверка на порт 80
sudo netstat -tlnp | grep :80

# Ръчно тестване
sudo certbot certonly --webroot -w /var/www/html -d demos.yourdomain.com
```

### Проблем: Файловете не се изтеглят
```bash
# Проверка на proxy headers
curl -H "X-Forwarded-Proto: https" http://SERVER_MACHINE_IP:8084/zescape/

# Проверка на demo директорията
ls -la /home/martink1337/hub/cssource/zescape/cstrike/demos/
```

---

## 📋 Финална проверка

- [ ] Demo сайт работи на HTTP (сървър машина)
- [ ] SSL сертификат е създаден
- [ ] HTTPS редирект работи
- [ ] Demos се показват правилно
- [ ] Файловете се изтеглят
- [ ] Навбар линк работи
- [ ] SSL рейтинг A/A+