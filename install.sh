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
echo -e "${CYAN}Welcome! This will install AlexVPN on your server.${NC}"
echo ""

[[ $EUID -ne 0 ]] && echo -e "${RED}❌ Run as root (sudo ./install.sh)${NC}" && exit 1

read -p "$(echo -e ${YELLOW}1. Enter your domain${NC} [e.g. vpn.example.com]: )" DOMAIN
read -p "$(echo -e ${YELLOW}2. Enter email for SSL${NC}: )" EMAIL
read -p "$(echo -e ${YELLOW}3. Choose admin username${NC} [default: admin]: )" ADMIN_USER
ADMIN_USER=${ADMIN_USER:-admin}
read -p "$(echo -e ${YELLOW}4. Choose admin password${NC} [blank = random]: )" ADMIN_PASS
[ -z "$ADMIN_PASS" ] && ADMIN_PASS=$(openssl rand -base64 12 | tr -d "/+=")

ADMIN_EMAIL="${ADMIN_USER}@${DOMAIN}"

echo -e "\n${BLUE}🚀 Installing AlexVPN for ${CYAN}${DOMAIN}${NC}\n"

# 1. Packages
echo -e "${BLUE}[1/4] Installing packages...${NC}"
apt update -qq && apt install -y -qq nginx php8.1-fpm php8.1-cli php8.1-curl php8.1-mbstring php8.1-xml php8.1-sqlite3 php8.1-zip unzip curl git certbot python3-certbot-nginx 2>/dev/null
echo -e "${GREEN}   ✓ Done${NC}"

# 2. Files
echo -e "${BLUE}[2/4] Downloading AlexVPN...${NC}"
mkdir -p /var/www/alexvpn/data/receipts
cd /tmp && rm -rf alexvpn && git clone -q https://github.com/Alextaylorvhjnf/alexvpn.git
cp -r alexvpn/* /var/www/alexvpn/ && rm -rf alexvpn
echo -e "${GREEN}   ✓ Done${NC}"

# 3. Nginx + SSL
echo -e "${BLUE}[3/4] Nginx & SSL...${NC}"
cat > /etc/nginx/sites-available/alexvpn << NGINX
server {
    listen 80; server_name $DOMAIN; root /var/www/alexvpn; index index.php;
    client_max_body_size 50M;
    location /admin { try_files \$uri \$uri/ /admin.php?\$query_string; }
    location / { try_files \$uri \$uri/ /index.php?\$query_string; }
    location /data/receipts/ { alias /var/www/alexvpn/data/receipts/; try_files \$uri =404; }
    location /data { deny all; }
    location ~ \.php\$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/var/run/php/php8.1-fpm.sock; fastcgi_read_timeout 300; }
    gzip on; gzip_types text/plain text/css application/json application/javascript text/xml image/svg+xml;
}
NGINX
ln -sf /etc/nginx/sites-available/alexvpn /etc/nginx/sites-enabled/ && rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
echo -e "${GREEN}   ✓ Nginx OK${NC}"
certbot --nginx -d $DOMAIN --non-interactive --agree-tos -m $EMAIL 2>/dev/null && echo -e "${GREEN}   ✓ SSL OK${NC}" || echo -e "${YELLOW}   ⚠ SSL skipped${NC}"

# 4. Configure
echo -e "${BLUE}[4/4] Configuring...${NC}"
sed -i "s|define('SITE_URL'.*|define('SITE_URL', 'https://$DOMAIN');|" /var/www/alexvpn/config.php
sed -i "s|define('CALLBACK_URL'.*|define('CALLBACK_URL', 'https://$DOMAIN/thank-you.php');|" /var/www/alexvpn/config.php
php -r "\$a=['email'=>'$ADMIN_EMAIL','password'=>password_hash('$ADMIN_PASS',PASSWORD_BCRYPT),'created_at'=>date('Y-m-d H:i:s')]; file_put_contents('/var/www/alexvpn/data/admin.json', json_encode(\$a, JSON_UNESCAPED_UNICODE));"
chown -R www-data:www-data /var/www/alexvpn && chmod -R 755 /var/www/alexvpn && chmod -R 775 /var/www/alexvpn/data
systemctl restart php8.1-fpm && systemctl reload nginx
echo -e "${GREEN}   ✓ Done${NC}"

echo ""
echo -e "${GREEN}╔══════════════════════════════════════════╗${NC}"
echo -e "${GREEN}║       ✅ Installation Complete!         ║${NC}"
echo -e "${GREEN}╚══════════════════════════════════════════╝${NC}"
echo ""
echo -e "  ${CYAN}🌐 Site:${NC}       ${GREEN}https://$DOMAIN${NC}"
echo -e "  ${CYAN}⚙️  Admin:${NC}      ${GREEN}https://$DOMAIN/admin${NC}"
echo -e "  ${CYAN}👤 Username:${NC}   ${YELLOW}$ADMIN_USER${NC}"
echo -e "  ${CYAN}📧 Email:${NC}      ${YELLOW}$ADMIN_EMAIL${NC}"
echo -e "  ${CYAN}🔑 Password:${NC}   ${YELLOW}$ADMIN_PASS${NC}"
echo ""
echo -e "  ${PURPLE}📝 After login go to Admin > Panel to set:${NC}"
echo -e "      • Panel URL & Token"
echo -e "      • Zarinpal Merchant ID"
echo ""
echo -e "  ${RED}⚠️  Save these credentials!${NC}"
