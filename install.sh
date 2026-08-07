#!/bin/bash
set -e

RED='\033[0;31m'; GREEN='\033[0;32m'; BLUE='\033[0;34m'; CYAN='\033[0;36m'; YELLOW='\033[1;33m'; PURPLE='\033[0;35m'; NC='\033[0m'

clear
echo -e "${PURPLE}"
echo "    ╔═══════════════════════════════════════╗"
echo "    ║         🔒 AlexVPN Installer         ║"
echo "    ║     Premium VPN Service Platform     ║"
echo "    ╚═══════════════════════════════════════╝"
echo -e "${NC}"
[[ $EUID -ne 0 ]] && echo -e "${RED}❌ Run as root${NC}" && exit 1

read -p "$(echo -e ${YELLOW}1. Enter domain${NC}: )" DOMAIN
read -p "$(echo -e ${YELLOW}2. Enter email${NC}: )" EMAIL
read -p "$(echo -e ${YELLOW}3. Admin username${NC} [admin]: )" ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}
read -p "$(echo -e ${YELLOW}4. Admin password${NC} [blank=random]: )" ADMIN_PASS
[ -z "$ADMIN_PASS" ] && ADMIN_PASS=$(openssl rand -base64 12 | tr -d "/+=")
ADMIN_EMAIL="${ADMIN_USER}@${DOMAIN}"

echo -e "\n${BLUE}🚀 Installing for ${CYAN}${DOMAIN}${NC}\n"

# 1
echo -e "${BLUE}[1/4] Packages...${NC}"
apt update -qq && apt install -y -qq nginx php8.1-fpm php8.1-cli php8.1-curl php8.1-mbstring php8.1-xml php8.1-sqlite3 php8.1-zip unzip curl git certbot python3-certbot-nginx 2>/dev/null
echo -e "${GREEN}   ✓${NC}"

# 2
echo -e "${BLUE}[2/4] Files...${NC}"
mkdir -p /var/www/alexvpn/data/receipts
cd /tmp && rm -rf alexvpn && git clone -q https://github.com/Alextaylorvhjnf/alexvpn.git
cp -r alexvpn/* /var/www/alexvpn/ && rm -rf alexvpn
echo -e "${GREEN}   ✓${NC}"

# 3 - First HTTP only, then SSL
echo -e "${BLUE}[3/4] Nginx & SSL...${NC}"
PHP_SOCK=$(ls /var/run/php/*.sock 2>/dev/null | head -1)
[ -z "$PHP_SOCK" ] && PHP_SOCK="/var/run/php/php8.1-fpm.sock"

cat > /etc/nginx/sites-available/alexvpn << NGINX
server {
    listen 80;
    server_name $DOMAIN;
    root /var/www/alexvpn; index index.php;
    client_max_body_size 50M;
    location /admin { try_files \$uri \$uri/ /admin.php?\$query_string; }
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location /data/receipts/ { alias /var/www/alexvpn/data/receipts/; try_files \$uri =404; }
    location /data { deny all; }
    location ~ \.php\$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:$PHP_SOCK; fastcgi_read_timeout 300; }
    gzip on; gzip_types text/plain text/css application/json application/javascript text/xml image/svg+xml;
}
NGINX
ln -sf /etc/nginx/sites-available/alexvpn /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl restart nginx
echo -e "${GREEN}   ✓ HTTP OK${NC}"

# SSL
certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $EMAIL 2>/dev/null && echo -e "${GREEN}   ✓ SSL OK${NC}" || echo -e "${YELLOW}   ⚠ SSL skipped${NC}"
systemctl reload nginx 2>/dev/null || true

# 4
echo -e "${BLUE}[4/4] Configuring...${NC}"
sed -i "s|define('SITE_URL'.*|define('SITE_URL', 'https://$DOMAIN');|" /var/www/alexvpn/config.php
sed -i "s|define('CALLBACK_URL'.*|define('CALLBACK_URL', 'https://$DOMAIN/thank-you.php');|" /var/www/alexvpn/config.php
php -r "\$a=['email'=>'$ADMIN_EMAIL','password'=>password_hash('$ADMIN_PASS',PASSWORD_BCRYPT),'created_at'=>date('Y-m-d H:i:s')]; file_put_contents('/var/www/alexvpn/data/admin.json', json_encode(\$a, JSON_UNESCAPED_UNICODE));"
chown -R www-data:www-data /var/www/alexvpn && chmod -R 755 /var/www/alexvpn && chmod -R 775 /var/www/alexvpn/data
systemctl restart php8.1-fpm && systemctl reload nginx 2>/dev/null || true
echo -e "${GREEN}   ✓${NC}"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║       ✅ Installation Complete!         ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  🌐 Site:   ${GREEN}https://$DOMAIN${NC}"
echo -e "  ⚙️  Admin:  ${GREEN}https://$DOMAIN/admin${NC}"
echo -e "  👤 User:   ${YELLOW}$ADMIN_USER${NC}"
echo -e "  📧 Email:  ${YELLOW}$ADMIN_EMAIL${NC}"
echo -e "  🔑 Pass:   ${YELLOW}$ADMIN_PASS${NC}"
echo ""
echo -e "  ${RED}⚠️  Save credentials!${NC}"
