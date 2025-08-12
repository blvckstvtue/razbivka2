#!/bin/bash

# 🚀 Автоматизиран deployment скрипт за CS:S Demo Archive
# Версия: 2.0 с SSL и Reverse Proxy поддръжка

set -e  # Спира при грешка

# Конфигурация (ПРОМЕНЕТЕ ТЕЗИ СТОЙНОСТИ!)
DOMAIN="demos.yourdomain.com"  # Заменете с вашия домейн
SERVER_MACHINE_IP="192.168.1.100"  # IP на сървър машината
WEB_MACHINE_IP="192.168.1.101"  # IP на уеб машината
DEMO_SITE_DIR="/home/martink1337/hub/demosite"
USER="martink1337"

# Цветове за output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функции за logging
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Проверка дали скрипта се изпълнява като root/sudo
check_permissions() {
    if [[ $EUID -ne 0 ]]; then
        log_error "Този скрипт трябва да се изпълни с sudo права"
        exit 1
    fi
}

# Проверка на IP адреса
check_machine_type() {
    local_ip=$(hostname -I | awk '{print $1}')
    
    if [[ "$local_ip" == "$SERVER_MACHINE_IP" ]]; then
        MACHINE_TYPE="server"
        log_info "Откриване на сървър машина (IP: $local_ip)"
    elif [[ "$local_ip" == "$WEB_MACHINE_IP" ]]; then
        MACHINE_TYPE="web"
        log_info "Откриване на уеб машина (IP: $local_ip)"
    else
        log_warning "Не може да се определи типа машина. Моля изберете:"
        echo "1) Сървър машина (demo файлове)"
        echo "2) Уеб машина (SSL proxy)"
        read -p "Избор (1/2): " choice
        
        case $choice in
            1) MACHINE_TYPE="server" ;;
            2) MACHINE_TYPE="web" ;;
            *) log_error "Невалиден избор"; exit 1 ;;
        esac
    fi
}

# Инсталиране на dependencies
install_dependencies() {
    log_info "Обновяване на пакетите..."
    apt update
    
    if [[ "$MACHINE_TYPE" == "server" ]]; then
        log_info "Инсталиране на PHP и Nginx за сървър машината..."
        apt install -y nginx php8.2-fpm php8.2-cli php8.2-common php8.2-curl php8.2-json
    else
        log_info "Инсталиране на Nginx и Certbot за уеб машината..."
        apt install -y nginx certbot python3-certbot-nginx
    fi
    
    systemctl enable nginx
    systemctl start nginx
    
    if [[ "$MACHINE_TYPE" == "server" ]]; then
        systemctl enable php8.2-fpm
        systemctl start php8.2-fpm
    fi
}

# Deploy на сървър машината
deploy_server() {
    log_info "Настройване на demo сайта на сървър машината..."
    
    # Създаване на директорията
    mkdir -p "$DEMO_SITE_DIR"
    
    # Копиране на файловете
    cp /workspace/*.php "$DEMO_SITE_DIR/"
    cp -r /workspace/style "$DEMO_SITE_DIR/"
    cp -r /workspace/img "$DEMO_SITE_DIR/"
    
    # Настройка на права
    chown -R $USER:www-data "$DEMO_SITE_DIR"
    chmod -R 755 "$DEMO_SITE_DIR"
    chmod 644 "$DEMO_SITE_DIR"/*.php
    
    # Nginx конфигурация
    cp /workspace/nginx-php.conf /etc/nginx/sites-available/demos
    ln -sf /etc/nginx/sites-available/demos /etc/nginx/sites-enabled/
    rm -f /etc/nginx/sites-enabled/default
    
    # Тестване и презареждане
    nginx -t
    systemctl reload nginx
    
    # Firewall настройки
    if command -v ufw >/dev/null 2>&1; then
        log_info "Настройване на firewall..."
        ufw allow from "$WEB_MACHINE_IP" to any port 8084
        ufw deny 8084/tcp
        ufw --force enable
    fi
    
    log_success "Сървър машината е настроена успешно!"
    log_info "Тестване: curl http://localhost:8084/"
}

# Deploy на уеб машината
deploy_web() {
    log_info "Настройване на SSL proxy на уеб машината..."
    
    # Копиране и настройка на конфигурацията
    cp /workspace/nginx-demos-subdomain.conf /etc/nginx/sites-available/demos-ssl
    
    # Заместване на placeholder стойности
    sed -i "s/demos.yourdomain.com/$DOMAIN/g" /etc/nginx/sites-available/demos-ssl
    sed -i "s/SERVER_MACHINE_IP/$SERVER_MACHINE_IP/g" /etc/nginx/sites-available/demos-ssl
    
    # Активиране
    ln -sf /etc/nginx/sites-available/demos-ssl /etc/nginx/sites-enabled/
    
    # Тестване
    nginx -t
    systemctl reload nginx
    
    # SSL сертификат
    log_info "Получаване на SSL сертификат за $DOMAIN..."
    log_warning "Уверете се че DNS записа за $DOMAIN сочи към тази машина!"
    read -p "Натиснете Enter за да продължите..."
    
    certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos --email admin@yourdomain.com
    
    # Firewall настройки
    if command -v ufw >/dev/null 2>&1; then
        log_info "Настройване на firewall..."
        ufw allow 80/tcp
        ufw allow 443/tcp
        ufw --force enable
    fi
    
    log_success "Уеб машината е настроена успешно!"
    log_info "Тестване: curl -I https://$DOMAIN/"
}

# Тестване на връзката
test_connection() {
    log_info "Тестване на връзката между машините..."
    
    if [[ "$MACHINE_TYPE" == "web" ]]; then
        if nc -z "$SERVER_MACHINE_IP" 8084; then
            log_success "Връзката към сървър машината работи"
        else
            log_error "Не може да се свърже със сървър машината на порт 8084"
        fi
    fi
}

# Финални тестове
run_tests() {
    log_info "Изпълняване на финални тестове..."
    
    if [[ "$MACHINE_TYPE" == "server" ]]; then
        # Тест на локалния сайт
        if curl -s http://localhost:8084/ > /dev/null; then
            log_success "Demo сайт работи локално"
        else
            log_error "Demo сайт не отговаря"
        fi
        
        # Тест на API
        if curl -s "http://localhost:8084/api.php?action=getServers" | grep -q "servers"; then
            log_success "API endpoint работи"
        else
            log_error "API endpoint не работи"
        fi
        
    elif [[ "$MACHINE_TYPE" == "web" ]]; then
        # Тест на HTTPS
        if curl -s -I "https://$DOMAIN/" | grep -q "200"; then
            log_success "HTTPS proxy работи"
        else
            log_error "HTTPS proxy не работи"
        fi
        
        # Тест на SSL
        if openssl s_client -connect "$DOMAIN:443" -servername "$DOMAIN" </dev/null 2>/dev/null | grep -q "Verify return code: 0"; then
            log_success "SSL сертификат е валиден"
        else
            log_warning "SSL сертификат може да има проблеми"
        fi
    fi
}

# Главна функция
main() {
    log_info "🚀 Стартиране на CS:S Demo Archive deployment..."
    
    check_permissions
    check_machine_type
    install_dependencies
    
    if [[ "$MACHINE_TYPE" == "server" ]]; then
        deploy_server
    else
        deploy_web
    fi
    
    test_connection
    run_tests
    
    log_success "🎉 Deployment завършен успешно!"
    
    if [[ "$MACHINE_TYPE" == "server" ]]; then
        echo ""
        log_info "📋 Следващи стъпки:"
        echo "   1. Изпълнете този скрипт на уеб машината"
        echo "   2. Настройте DNS записа за $DOMAIN"
        echo "   3. Добавете линк в навбара: https://$DOMAIN"
    else
        echo ""
        log_info "📋 Готово! Demo сайта е достъпен на:"
        echo "   🌐 https://$DOMAIN"
        echo "   📊 SSL Test: https://www.ssllabs.com/ssltest/analyze.html?d=$DOMAIN"
    fi
}

# Изпълнение
main "$@"