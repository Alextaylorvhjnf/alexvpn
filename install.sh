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
[[ $EUID -ne 0 ]] && echo -e "${RED}Run as root${NC}" && exit 1

read -p "$(echo -e ${CYAN}Enter domain${NC} [e.g. vpn.example.com]: )" DOMAIN
read -p "$(echo -e ${CYAN}Enter email${NC}: )" EMAIL
read -p "$(echo -e ${CYAN}Enter Sanayi Panel URL${NC}: )" PANEL_URL
read -p "$(echo -e ${CYAN}Enter Sanayi Panel Token${NC}: )" PANEL_TOKEN
read -p "$(echo -e ${CYAN}Enter Zarinpal Merchant ID${NC}: )" MERCHANT_ID

ADMIN_PASS=$(openssl rand -base64 12 | tr -d "/+=")
ADMIN_EMAIL="admin@${DOMAIN}"

echo -e "${BLUE}[1/6] Installing packages...${NC}"
apt update -qq && apt install -y -qq nginx php8.1-fpm php8.1-cli php8.1-curl php8.1-mbstring php8.1-xml php8.1-sqlite3 php8.1-zip unzip curl git certbot python3-certbot-nginx 2>/dev/null

echo -e "${BLUE}[2/6] Creating directories...${NC}"
mkdir -p /var/www/alexvpn/data/receipts

echo -e "${BLUE}[3/6] Copying files...${NC}"
cp -r /tmp/alexvpn-install/* /var/www/alexvpn/ 2>/dev/null || cp -r $(dirname $0)/* /var/www/alexvpn/
rm -f /var/www/alexvpn/install.sh

echo -e "${BLUE}[4/6] Configuring Nginx...${NC}"
cat > /etc/nginx/sites-available/alexvpn << NGINX
server {
    listen 80; server_name $DOMAIN; root /var/www/alexvpn; index index.php;
    client_max_body_size 50M;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location /data/receipts/ { alias /var/www/alexvpn/data/receipts/; try_files \$uri =404; }
    location /data { deny all; }
    location ~ \.php\$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; fastcgi_read_timeout 300; }
    gzip on; gzip_types text/plain text/css application/json application/javascript text/xml image/svg+xml;
}
NGINX
ln -sf /etc/nginx/sites-available/alexvpn /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx

echo -e "${BLUE}[5/6] SSL Certificate...${NC}"
certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $EMAIL 2>/dev/null || echo -e "${YELLOW}SSL skipped${NC}"

echo -e "${BLUE}[6/6] Configuring...${NC}"
CONFIG=/var/www/alexvpn/config.php
sed -i "s|define('SITE_URL'.*|define('SITE_URL', 'https://$DOMAIN');|" $CONFIG
sed -i "s|define('PANEL_URL'.*|define('PANEL_URL', '$PANEL_URL');|" $CONFIG
sed -i "s|define('PANEL_TOKEN'.*|define('PANEL_TOKEN', '$PANEL_TOKEN');|" $CONFIG
sed -i "s|define('MERCHANT_ID'.*|define('MERCHANT_ID', '$MERCHANT_ID');|" $CONFIG
sed -i "s|define('CALLBACK_URL'.*|define('CALLBACK_URL', 'https://$DOMAIN/thank-you.php');|" $CONFIG

php -r "\$a=['email'=>'$ADMIN_EMAIL','password'=>password_hash('$ADMIN_PASS',PASSWORD_BCRYPT),'created_at'=>date('Y-m-d H:i:s')];file_put_contents('/var/www/alexvpn/data/admin.json',json_encode(\$a,JSON_UNESCAPED_UNICODE));"

chown -R www-data:www-data /var/www/alexvpn
chmod -R 755 /var/www/alexvpn
chmod -R 775 /var/www/alexvpn/data
systemctl restart php8.1-fpm && systemctl reload nginx

echo ""
echo -e "${GREEN}╔══════════════════════════════════════╗${NC}"
echo -e "${GREEN}║     ✅ Installation Complete!       ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════╝${NC}"
echo ""
echo -e "  Site: ${CYAN}https://$DOMAIN${NC}"
echo -e "  Admin: ${CYAN}https://$DOMAIN/admin${NC}"
echo -e "  Email: ${YELLOW}$ADMIN_EMAIL${NC}"
echo -e "  Password: ${YELLOW}$ADMIN_PASS${NC}"
echo ""
echo -e "${RED}⚠️  Save credentials!${NC}"
