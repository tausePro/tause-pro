# 🚀 Crear Instancia Limpia y Desplegar desde Local

## 🎯 Objetivo

Crear una instancia EC2 completamente nueva y limpia, y desplegar el código desde tu máquina local.

---

## 📋 Paso 1: Crear Nueva Instancia EC2

### 1.1. Crear Instancia desde Ubuntu AMI

1. **AWS Console** → **EC2** → **Launch instance**
2. **Configurar:**
   - **Name**: `staging-tausepro-clean`
   - **AMI**: **Ubuntu Server 22.04 LTS** (o 20.04)
   - **Instance type**: `t3.small` (suficiente para staging)
   - **Key pair**: 
     - Si tienes `staging-tausepro-key.pem`, selecciónala
     - O crea nueva: **Create new key pair** → Nombre: `staging-clean-key` → Download
   - **Network settings**:
     - **VPC**: Mismo que producción
     - **Subnet**: Cualquier subnet pública
     - **Auto-assign Public IP**: **Enable**
     - **Security group**: **Create new security group**
       - Nombre: `staging-clean-sg`
       - Reglas:
         - SSH (22) desde `0.0.0.0/0`
         - HTTP (80) desde `0.0.0.0/0`
         - HTTPS (443) desde `0.0.0.0/0`
   - **Configure storage**: 20 GB (gp3)
   - **Advanced details**:
     - **User data**: **DEJAR VACÍO** (no agregar nada)
3. **Launch instance**

⏳ **Esperar 2-3 minutos** hasta que esté "Running"

---

### 1.2. Asociar Elastic IP

1. **EC2** → **Elastic IPs** → Selecciona `3.220.198.180`
2. **Actions** → **Disassociate** (de instancia vieja)
3. **Actions** → **Associate Elastic IP address**
4. **Instance**: Selecciona `staging-tausepro-clean`
5. **Associate**

---

## 📋 Paso 2: Conectar y Configurar Servidor Base

### 2.1. Obtener IP Pública

```bash
# Ver IP de la nueva instancia
aws ec2 describe-instances \
    --filters "Name=tag:Name,Values=staging-tausepro-clean" "Name=instance-state-name,Values=running" \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text
```

O usa la Elastic IP: `3.220.198.180`

### 2.2. Conectar vía SSH

```bash
# Con clave existente
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180

# O si creaste nueva clave
ssh -4 -i staging-clean-key.pem ubuntu@3.220.198.180
```

**Debería funcionar ahora** ✅

---

### 2.3. Instalar Servicios Base

Una vez conectado, ejecuta:

```bash
# Actualizar sistema
sudo apt-get update
sudo apt-get upgrade -y

# Instalar Nginx
sudo apt-get install -y nginx

# Instalar PHP 8.2 y extensiones
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y php8.2-fpm php8.2-cli php8.2-common php8.2-mysql php8.2-xml php8.2-curl php8.2-mbstring php8.2-zip php8.2-gd php8.2-bcmath

# Instalar MySQL
sudo apt-get install -y mysql-server

# Instalar Git y herramientas
sudo apt-get install -y git curl unzip

# Iniciar servicios
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm
sudo systemctl start mysql
sudo systemctl enable mysql
```

---

## 📋 Paso 3: Configurar Base de Datos

```bash
# Configurar MySQL
sudo mysql_secure_installation
# Seguir prompts (puedes usar respuestas por defecto)

# Crear BD y usuario staging
sudo mysql << 'EOF'
CREATE DATABASE magicai_staging;
CREATE USER 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF

# Verificar
sudo mysql -e "SHOW DATABASES;"
```

---

## 📋 Paso 4: Crear Directorio y Permisos

```bash
# Crear directorio
sudo mkdir -p /var/www/magicai
sudo chown -R $USER:$USER /var/www/magicai

# Crear directorios necesarios
mkdir -p /var/www/magicai/storage/framework/{cache,sessions,views,testing}
mkdir -p /var/www/magicai/storage/logs
mkdir -p /var/www/magicai/bootstrap/cache

# Dar permisos
sudo chown -R www-data:www-data /var/www/magicai/storage
sudo chown -R www-data:www-data /var/www/magicai/bootstrap/cache
sudo chmod -R 775 /var/www/magicai/storage
sudo chmod -R 775 /var/www/magicai/bootstrap/cache
```

---

## 📋 Paso 5: Subir Código desde Local

### 5.1. Desde tu Mac, crear archivo .env

```bash
cd /Users/tause/Documents/proyectos/tausepro9.4

# Crear .env para staging
cat > .env.staging << 'EOF'
APP_NAME=MagicAI
APP_ENV=staging
APP_KEY=
APP_DEBUG=true
APP_URL=http://test.tause.pro

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
EOF
```

### 5.2. Subir archivos (excluyendo node_modules, vendor, etc.)

```bash
# Desde tu Mac
cd /Users/tause/Documents/proyectos/tausepro9.4

# Crear archivo temporal con archivos a excluir
cat > .rsync-exclude << 'EOF'
node_modules/
vendor/
.git/
.env
.env.*
storage/logs/*
storage/framework/cache/*
storage/framework/sessions/*
storage/framework/views/*
bootstrap/cache/*
*.log
.DS_Store
EOF

# Subir código usando rsync (más eficiente que scp)
rsync -avz --exclude-from=.rsync-exclude \
    -e "ssh -4 -i staging-tausepro-key.pem" \
    ./ ubuntu@3.220.198.180:/var/www/magicai/

# Subir .env.staging como .env
scp -4 -i staging-tausepro-key.pem \
    .env.staging ubuntu@3.220.198.180:/var/www/magicai/.env
```

---

## 📋 Paso 6: Configurar Aplicación en Servidor

```bash
# Conectar al servidor
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180

# Ir al directorio
cd /var/www/magicai

# Instalar dependencias PHP
composer install --no-dev --optimize-autoloader

# Generar APP_KEY
php artisan key:generate

# Ejecutar migraciones
php artisan migrate --force

# Limpiar y cachear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Cachear configuración (opcional, para producción)
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## 📋 Paso 7: Configurar Nginx

```bash
# Crear configuración Nginx
sudo tee /etc/nginx/sites-available/test.tause.pro > /dev/null << 'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name test.tause.pro;
    root /var/www/magicai/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Habilitar sitio
sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/

# Remover default si existe
sudo rm -f /etc/nginx/sites-enabled/default

# Verificar configuración
sudo nginx -t

# Recargar Nginx
sudo systemctl reload nginx
```

---

## 📋 Paso 8: Configurar SSL (Opcional)

```bash
# Instalar Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d test.tause.pro --non-interactive --agree-tos --email admin@tause.pro --redirect
```

---

## ✅ Verificar Todo Funciona

```bash
# Verificar servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
sudo systemctl status mysql

# Verificar HTTP funciona
curl -I http://localhost

# Verificar BD
sudo mysql magicai_staging -e "SHOW TABLES;" | head -10
```

---

## 📋 Checklist Final

- [ ] Instancia creada y Running
- [ ] Elastic IP asociada
- [ ] SSH funciona
- [ ] Servicios instalados (Nginx, PHP, MySQL)
- [ ] BD creada
- [ ] Código subido desde local
- [ ] .env configurado
- [ ] Migraciones ejecutadas
- [ ] Nginx configurado
- [ ] HTTP funciona
- [ ] SSL configurado (opcional)

---

## 🗑️ Limpiar Instancia Vieja (Después de Verificar)

Una vez que la nueva instancia funciona:

1. **EC2** → **Instances**
2. **Selecciona** `i-0bbe91c13a1343538` (instancia vieja)
3. **Instance state** → **Stop instance**
4. Esperar "Stopped"
5. **Instance state** → **Terminate instance**

---

**Este método es más limpio y controlado. Empieza creando la nueva instancia.**


