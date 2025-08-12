# 🎯 Setup за zanedemos.zone.id

## 📋 Конфигурация за вашия специален домейн

Използването на отделен домейн `zanedemos.zone.id` е отлично решение! 

### ⭐ Предимства:
- ✅ **Независим SSL** сертификат  
- ✅ **Няма ограничения** от основния домейн провайдър
- ✅ **По-лесно управление** на DNS
- ✅ **Професионален вид** - специализиран домейн за demos

## 🚀 Стъпки за настройка:

### 1. DNS настройка за zanedemos.zone.id

```
# В DNS управлението на zone.id домейна добавете:
A record: zanedemos.zone.id → IP_НА_УЕБ_МАШИНАТА
```

### 2. Deployment (автоматизиран):

```bash
# Файлът deploy.sh вече е обновен с новия домейн
# Просто изпълнете:

# На сървър машината:
sudo ./deploy.sh

# На уеб машината:  
sudo ./deploy.sh
```

### 3. Ръчна настройка (ако предпочитате):

#### На сървър машината:
```bash
# Копиране на PHP файловете
sudo mkdir -p /home/martink1337/hub/demosite
sudo cp /workspace/*.php /home/martink1337/hub/demosite/
sudo cp -r /workspace/style /home/martink1337/hub/demosite/
sudo cp -r /workspace/img /home/martink1337/hub/demosite/

# Nginx конфигурация (остава същата)
sudo cp /workspace/nginx-php.conf /etc/nginx/sites-available/demos
sudo ln -sf /etc/nginx/sites-available/demos /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx
```

#### На уеб машината:
```bash
# SSL Nginx конфигурация
sudo cp /workspace/nginx-demos-subdomain.conf /etc/nginx/sites-available/zanedemos-ssl
sudo ln -sf /etc/nginx/sites-available/zanedemos-ssl /etc/nginx/sites-enabled/

# Тестване
sudo nginx -t && sudo systemctl reload nginx

# SSL сертификат
sudo certbot --nginx -d zanedemos.zone.id
```

## 🔗 Интеграция с главния сайт:

### В навбара на главния ви сайт:
```html
<a href="https://zanedemos.zone.id" class="nav-link">CS:S Demos</a>
```

### Или с иконка:
```html
<a href="https://zanedemos.zone.id" class="nav-link">
    🎮 Demos
</a>
```

## 🧪 Тестване:

```bash
# Проверка на DNS
nslookup zanedemos.zone.id

# Проверка на HTTP редирект
curl -I http://zanedemos.zone.id/

# Проверка на HTTPS
curl -I https://zanedemos.zone.id/

# SSL тест
openssl s_client -connect zanedemos.zone.id:443 -servername zanedemos.zone.id
```

## 🎉 Крайният резултат:

```
https://zanedemos.zone.id
├── 🏠 Главна страница с красив интерфейс
├── 📊 Статистики за всички сървъри  
├── 📁 ZEscape demos
├── 📁 CSGOMod demos
└── 🔄 AJAX обновявания в реално време
```

**URL за навбара**: `https://zanedemos.zone.id` ✨

Отличен избор на домейн! Звучи професионално и е лесен за запомняне. 🚀