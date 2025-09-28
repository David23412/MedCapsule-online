# MedCapsule Production Deployment Guide

Beautiful — you're in the captain's seat now. Below is a no-fluff, exact step-by-step, copy/paste-ready playbook to take your Laravel MedCapsule repo → Hetzner CX11 server → live domain with SSL + Redis + MySQL + Supervisor queue worker + simple backups. I'll hold your hand through every command and every file you need. Move fast, follow exactly, and you'll have a real production instance tonight.

## Assumptions

- You have your GitHub repo URL: https://github.com/David23412/MedCapsule-online.git
- You purchased domain medcapsule.xyz (replace with yours).
- For paid hosting: Hetzner CX11 with server IP + root SSH access (when available).
- For free hosting: We'll use Render.com (no credit card required for initial setup).

## Free Hosting Alternative (No Credit Card Required)

Since you're waiting for Hetzner verification, let's use **Render.com** for free initial deployment. Render has a generous free tier: 750 hours/month for web services, free PostgreSQL (512MB) and Redis (Upstash free tier), custom domains included.

### Quick Free Deploy to Render

1. **Sign up**: Go to https://render.com, sign up with GitHub (no card needed initially).

2. **Connect GitHub**: Connect your MedCapsule repo.

3. **Create PostgreSQL Database** (free):
   - From dashboard, New > PostgreSQL.
   - Choose free tier (512MB, nice for demo).
   - Name: medcapsule-db
   - Paste the internal DATABASE_URL when created.

4. **Create Redis** (use Upstash for free, as Render Redis is paid):
   - Go to https://upstash.com, create free Redis DB.
   - Get the URL.

5. **Create Web Service**:
   - New > Web Service.
   - Connect to your GitHub repo.
   - Runtime: Node.js? Wait, for Laravel, choose "Native Runtime" or create Dockerfile.
   - **Better**: Create a Dockerfile for your Laravel app.

6. **Add Dockerfile to your repo root** (if not already):

   ```dockerfile
   FROM php:8.2-apache

   RUN apt-get update && apt-get install -y \
       libzip-dev \
       zip \
       unzip \
       git \
       libonig-dev \
       libxml2-dev \
       libpq-dev \
       libjpeg-dev \
       libpng-dev \
       libfreetype6-dev \
       && docker-php-ext-configure gd --with-freetype --with-jpeg \
       && docker-php-ext-install pdo_pgsql pdo_mysql mbstring exif pcntl bcmath xml zip gd

   COPY . /var/www/html
   WORKDIR /var/www/html

   RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
   RUN composer install --no-dev --optimize-autoloader

   RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

   RUN a2enmod rewrite

   COPY <<EOF /etc/apache2/sites-available/000-default.conf
   <VirtualHost *:80>
       ServerAdmin webmaster@localhost
       DocumentRoot /var/www/html/public

       <Directory /var/www/html/public>
           AllowOverride All
           Require all granted
       </Directory>

       ErrorLog \${APACHE_LOG_DIR}/error.log
       CustomLog \${APACHE_LOG_DIR}/access.log combined
   </VirtualHost>
   EOF

   CMD ["apache2-foreground"]
   ```

7. **Environment Variables** (copy from free DB and Redis):
   - APP_NAME=MedCapsule
   - APP_ENV=production
   - APP_DEBUG=false
   - APP_URL=https://your-app-on-render.com
   - DB_CONNECTION=pgsql
   - DATABASE_URL=[from Postgres DB]
   - CACHE_DRIVER=redis
   - QUEUE_CONNECTION=database
   - SESSION_DRIVER=file
   - REDIS_URL=[from Upstash]
   - LOG_CHANNEL=stack

8. **Deploy**: Hit deploy, wait for build.

9. **Add Custom Domain**: In service settings, add medcapsule.xyz, configure DNS as instructed (CNAME to render-provided subdomain).

10. **SSL**: Automatic on Render.
    - But to use free Redis, need to set REDIS_HOST, PORT, etc. from Upstash URL.

Upstash URL: redis://username:password@host:port/db

So parse that.

For quick start, set QUEUE_CONNECTION=sync to avoid Redis queues initially.

Set CACHE_DRIVER=database or file.

This way, you can be live in minutes with Render free tier.

### Paid Option (when Hetzner ready): Follow the sections below.

## OK LET'S GO — DO THIS IN ORDER. Copy/paste carefully.

### 0) Prep: SSH key + local

Do this from your local machine:

```bash
# (if you don't have an SSH key)
ssh-keygen -t ed25519 -C "your_email@example.com"
cat ~/.ssh/id_ed25519.pub
# Copy the PUBLIC key and add it to Hetzner Console for the server (or add it to root's ~/.ssh/authorized_keys later)
```

### 1) Initial server setup (run as root or via Hetzner console shell)

SSH into server:

```bash
ssh root@YOUR.SERVER.IP
```

Run this entire block (it updates system, creates a deploy user, installs tools, sets swap, basic security):

```bash
# Update OS
apt update && apt upgrade -y

# Basic packages
apt install -y git curl unzip htop jq wget ca-certificates apt-transport-https lsb-release gnupg

# Create deploy user and add to sudoers
adduser --disabled-password --gecos "" deploy
usermod -aG sudo deploy

# Add your SSH public key to deploy's authorized_keys (paste your key)
mkdir -p /home/deploy/.ssh
echo "ssh-ed25519 AAAA... your_public_key_here" > /home/deploy/.ssh/authorized_keys
chown -R deploy:deploy /home/deploy/.ssh
chmod 700 /home/deploy/.ssh
chmod 600 /home/deploy/.ssh/authorized_keys

# Create 2GB swap (important to avoid sudden OOM)
fallocate -l 2G /swapfile
chmod 600 /swapfile
mkswap /swapfile
swapon /swapfile
echo '/swapfile none swap sw 0 0' >> /etc/fstab

# Install Nginx, MariaDB, Redis, PHP and extras
apt install -y nginx mariadb-server redis-server
# Add PHP repo then install PHP 8.2 (or adjust if you use 8.1/8.3)
apt install -y software-properties-common
add-apt-repository ppa:ondrej/php -y
apt update
apt install -y php8.2-fpm php8.2-cli php8.2-mbstring php8.2-xml php8.2-mysql \
php8.2-curl php8.2-zip php8.2-gd php8.2-bcmath php8.2-intl php8.2-opcache

# Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Firewall + Fail2ban (basic)
apt install -y ufw fail2ban
ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

# Basic MariaDB secure install (will prompt for root password)
mysql_secure_installation
```

Notes: During mysql_secure_installation, follow prompts — set a strong root password, remove anonymous users, disallow remote root login, remove test DB.

### 2) Create database & user for Laravel

Run (replace strong_db_password):

```bash
mysql -u root -p
# then inside mysql:
CREATE DATABASE medcapsule CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'meduser'@'localhost' IDENTIFIED BY 'strong_db_password';
GRANT ALL PRIVILEGES ON medcapsule.* TO 'meduser'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 3) Clone repo & install Laravel as deploy user

Switch user and deploy into /var/www/medcapsule:

```bash
# as root:
su - deploy

cd ~
mkdir -p ~/apps
cd ~/apps
git clone https://github.com/David23412/MedCapsule-online.git medcapsule
cd medcapsule

# install composer deps
composer install --no-dev --optimize-autoloader

# copy .env and prepare
cp .env.example .env
```

Now edit .env (use nano or vim):

```bash
nano .env
```

Paste this template (replace placeholders):

```bash
APP_NAME="MedCapsule"
APP_ENV=production
APP_KEY=    # you will set below
APP_DEBUG=false
APP_URL=https://medcapsule.xyz

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcapsule
DB_USERNAME=meduser
DB_PASSWORD=strong_db_password

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@medcapsule.xyz
MAIL_FROM_NAME="${APP_NAME}"
```

Now generate APP_KEY and set permissions:

```bash
php artisan key:generate
# Ensure storage & bootstrap cache are writable
cd /home/deploy/apps/medcapsule
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 4) Run migrations & seed minimal demo data (careful)

If your migration files are ready:

```bash
php artisan migrate --force
php artisan db:seed --force   # only if you have seeders and want demo content
```

If migrations error: fix locally then push and pull again.

### 5) Nginx production config

Create this Nginx file as root: /etc/nginx/sites-available/medcapsule

```nginx
server {
    listen 80;
    server_name medcapsule.xyz www.medcapsule.xyz;
    root /home/deploy/apps/medcapsule/public;
    index index.php;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Enable site and reload nginx:

```bash
ln -s /etc/nginx/sites-available/medcapsule /etc/nginx/sites-enabled/
nginx -t && systemctl reload nginx
```

### 6) SSL — Cloudflare + Origin Certificate (recommended for instant SSL + firewall)

On Cloudflare:

Add site medcapsule.xyz to Cloudflare (free plan).

Cloudflare gives you nameservers — change nameservers at your domain registrar to Cloudflare's.

In DNS on Cloudflare, create an A record:

- Name: @
- IPv4: YOUR.SERVER.IP
- Proxy status: Proxied (orange cloud)

Also add www CNAME to @ and proxy it.

Create Cloudflare Origin Certificate (Cloudflare dashboard → SSL/TLS → Origin Server):

- Generate certificate for medcapsule.xyz + *.medcapsule.xyz if you want.
- Choose 15 years key if offered.
- Copy the certificate and key.

On server:

```bash
mkdir -p /etc/ssl/cloudflare
# create files and paste from Cloudflare
nano /etc/ssl/cloudflare/origin.crt   # paste cert
nano /etc/ssl/cloudflare/origin.key   # paste private key
chmod 600 /etc/ssl/cloudflare/origin.key
```

Update Nginx server block to listen 443 (replace server block or add one):

```nginx
server {
    listen 443 ssl http2;
    server_name medcapsule.xyz www.medcapsule.xyz;
    root /home/deploy/apps/medcapsule/public;
    index index.php;

    ssl_certificate /etc/ssl/cloudflare/origin.crt;
    ssl_certificate_key /etc/ssl/cloudflare/origin.key;
    ssl_client_certificate /etc/ssl/cloudflare/origin.crt;
    ssl_verify_client off;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains; preload" always;

    location / { try_files $uri $uri/ /index.php?$query_string; }
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.2-fpm.sock;
    }
}
# redirect HTTP to HTTPS
server {
    listen 80;
    server_name medcapsule.xyz www.medcapsule.xyz;
    return 301 https://$host$request_uri;
}
```

Reload nginx:

```bash
nginx -t && systemctl reload nginx
```

Cloudflare will terminate TLS at edge and use the origin cert to talk to your server — solid combo.

### 7) PHP-FPM tuning for low-memory stability

Edit /etc/php/8.2/fpm/pool.d/www.conf and set conservative children limits:

```ini
pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3
pm.max_requests = 500
```

Then restart:

```bash
systemctl restart php8.2-fpm
```

This limits concurrent PHP workers so you don't OOM instantly (trade concurrency for stability).

Enable OPcache for performance: edit php.ini (find location: php --ini) and ensure:

```ini
opcache.enable=1
opcache.memory_consumption=128
opcache.interned_strings_buffer=8
opcache.max_accelerated_files=10000
opcache.validate_timestamps=0   # if you deploy carefully and run cache:clear on deploy
```

Restart php-fpm.

### 8) Supervisor — queue worker + restart on crash

Install Supervisor (if not already):

```bash
apt install -y supervisor
```

Create /etc/supervisor/conf.d/laravel-worker.conf:

```ini
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/deploy/apps/medcapsule/artisan queue:work redis --sleep=3 --tries=3 --timeout=60
autostart=true
autorestart=true
user=deploy
numprocs=1
redirect_stderr=true
stdout_logfile=/var/log/laravel-worker.log
stopwaitsecs=3600
```

Load Supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*
```

If you don't use queues yet, set QUEUE_CONNECTION=database or sync in .env to avoid workers.

### 9) Cron for Laravel scheduler & DB backups

Add cron for scheduler and nightly DB dump.

Edit crontab for deploy (or root for backups):

```bash
crontab -e
```

Paste:

```cron
* * * * * cd /home/deploy/apps/medcapsule && php artisan schedule:run >> /dev/null 2>&1
# daily DB dump at 03:10 and keep last 7
10 3 * * * /usr/local/bin/medcapsule_backup.sh
```

Create /usr/local/bin/medcapsule_backup.sh (run as root; make executable):

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/medcapsule"
mkdir -p $BACKUP_DIR
DATE=$(date +%F_%H-%M)
mysqldump -u meduser -p'strong_db_password' medcapsule > $BACKUP_DIR/medcapsule_$DATE.sql
# keep 7 newest
ls -1t $BACKUP_DIR/*.sql | sed -e '1,7d' | xargs -r rm --
```

Make executable:

```bash
chmod +x /usr/local/bin/medcapsule_backup.sh
```

Optional: push backups to remote storage later (Hetzner object storage, rclone to S3, or email).

### 10) Deployment workflow (safe git-based deploy via SSH)

Option A — manual quick deploy (fast):

```bash
cd /home/deploy/apps/medcapsule
git pull origin main
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo chown -R www-data:www-data storage bootstrap/cache
```

Option B — GitHub Actions SSH deploy (recommended for zero manual on server).

Create .github/workflows/deploy.yml:

```yaml
name: Deploy to Hetzner via SSH
on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Copy files to server
        uses: appleboy/scp-action@v0.1.6
        with:
          host: ${{ secrets.SERVER_IP }}
          username: deploy
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          source: "."
          target: "/home/deploy/apps/medcapsule_tmp"
      - name: Remote deploy commands
        uses: appleboy/ssh-action@v0.1.8
        with:
          host: ${{ secrets.SERVER_IP }}
          username: deploy
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            rsync -a --delete /home/deploy/apps/medcapsule_tmp/ /home/deploy/apps/medcapsule/
            cd /home/deploy/apps/medcapsule
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo chown -R www-data:www-data storage bootstrap/cache
```

Set secrets.SERVER_IP and secrets.SSH_PRIVATE_KEY in GitHub repo settings before using.

### 11) Performance & DB indexing: immediate must-dos

On your most queried tables (questions, user_answers, mistakes, leaderboards), ensure these indexes exist:

```sql
-- example indexes
CREATE INDEX idx_questions_course ON questions(course_id);
CREATE INDEX idx_questions_topic ON questions(topic_id);
CREATE INDEX idx_user_answers_user ON user_answers(user_id);
CREATE INDEX idx_user_answers_question ON user_answers(question_id);
CREATE INDEX idx_mistakes_user_question ON mistakes(user_id, question_id);
```

Also avoid SELECT * — select needed columns only. Paginate queries. Use Laravel eager loading (with()) to avoid N+1.

### 12) Monitoring & quick debugging commands

Check memory:

```bash
watch -n 2 free -m
htop
```

Check php-fpm logs:

```bash
journalctl -u php8.2-fpm -f
tail -f /var/log/nginx/error.log
tail -f /var/log/mysql/error.log
tail -f /var/log/laravel-worker.log
```

If OOM occurs:

```bash
Check dmesg | grep -i oom
```

Reduce pm.max_children in php-fpm and reload.

Kill heavy processes or increase swap temporarily.

### 13) Quick checklist before you share the link publicly

- DOMAIN DNS set to Cloudflare and proxied (orange cloud).
- Nginx + origin cert installed and site loads HTTPS.
- .env configured and APP_KEY present.
- Migrations run and at least 100–200 questions seeded so users can test.
- php artisan config:cache + route:cache + view:cache done.
- Supervisor worker running (or set QUEUE_CONNECTION=sync until stable).
- Basic cron for scheduler active.
- Monitor htop while first wave logs in.

### 14) If traffic spikes — emergency quick moves

- Turn on Cloudflare "I'm Under Attack" mode from dashboard.
- In Nginx, rate-limit endpoints if you need: limit_req_zone etc. (advanced).
- Reduce pm.max_children further to stabilize memory.
- Disable heavy features (analytics, live leaderboards, reporting APIs) by toggling feature flags in .env or quickly commenting routes.
- If persistent load, upgrade Hetzner to CX21 (+€3.5) — painless and instant.

### 15) Follow-up maintenance (first 2 weeks)

- Add remote backups to object storage (rclone to Hetzner Object Storage or S3).
- Move long-running jobs to Redis queues and scale worker count gradually.
- Add basic usage metrics (NewRelic/Netdata/Prometheus) if you want deeper telemetry.
- Buy .com when you can and 301 redirect .xyz → .com.

## Files you will most likely paste direct (copy/paste)

### /etc/nginx/sites-available/medcapsule (final)

Already provided in section 5 & 6 — use the HTTPS + redirect variant from Step 6.

### /etc/supervisor/conf.d/laravel-worker.conf

Block from Step 8.

### /usr/local/bin/medcapsule_backup.sh

Block from Step 9; replace DB password.

### .env template

Block from Step 3; replace with real passwords and APP_KEY generated.

## Final motivational reality check (straight talk)

If you follow every step above, you can be live tonight and have a stable site for your first wave.

The Hetzner CX11 + swap + PHP tuning + Cloudflare is the sweet spot for cost vs performance — cheap and serious.

The number-one risk is forgetting to limit PHP-FPM children — that alone causes OOM. Keep pm.max_children low and use Cloudflare to soak traffic.

## Dependencies

[We're generating the exact .env with placeholders filled for you to copy-paste]

```bash
APP_NAME="MedCapsule"
APP_ENV=production
APP_KEY=base64:#REPLACE_WITH_GENERATED_KEY#
APP_DEBUG=false
APP_URL=https://medcapsule.xyz

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=medcapsule
DB_USERNAME=meduser
DB_PASSWORD=#REPLACE_WITH_STRONG_DB_PASSWORD#

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS=hello@medcapsule.xyz
MAIL_FROM_NAME="${APP_NAME}"
```

[And the GitHub Actions deploy file ready to paste into your repo:]

```yaml
name: Deploy to Hetzner via SSH
on:
  push:
    branches: [ main ]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Copy files to server
        uses: appleboy/scp-action@v0.1.6
        with:
          host: ${{ secrets.SERVER_IP }}
          username: deploy
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          source: "."
          target: "/home/deploy/apps/medcapsule_tmp"
      - name: Remote deploy commands
        uses: appleboy/ssh-action@v0.1.8
        with:
          host: ${{ secrets.SERVER_IP }}
          username: deploy
          key: ${{ secrets.SSH_PRIVATE_KEY }}
          script: |
            rsync -a --delete /home/deploy/apps/medcapsule_tmp/ /home/deploy/apps/medcapsule/
            cd /home/deploy/apps/medcapsule
            composer install --no-dev --optimize-autoloader
            php artisan migrate --force
            php artisan config:cache
            php artisan route:cache
            php artisan view:cache
            sudo chown -R www-data:www-data storage bootstrap/cache
