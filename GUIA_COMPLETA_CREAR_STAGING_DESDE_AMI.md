# 🚀 Guía Completa: Crear Staging desde AMI (Mejores Prácticas)

## 📋 Análisis del Problema

### Lo que Descubrimos

1. **Instancia actual**: `t3.micro` (no t3.small como pensábamos)
2. **User Data**: Se ejecuta pero puede estar bloqueando SSH
3. **Problema real**: El orden de ejecución del User Data puede estar bloqueando conexiones

---

## 🎯 Mejores Prácticas para Crear Staging desde AMI

### Principio Fundamental

**NO habilitar firewall en User Data hasta que todo esté configurado y verificado.**

---

## 📋 Proceso Correcto Paso a Paso

### Paso 1: Preparar AMI

**Usar**: `ami-00cee3e99312902ad` ("antes de actualizar agentes")
- ✅ Funciona en producción
- ✅ Tiene toda la configuración base
- ✅ 30GB (completo)

### Paso 2: Crear Instancia SIN User Data Inicialmente

**Razón**: Conectarse primero, luego configurar.

1. **AWS Console** → EC2 → Launch Instance

2. **Configuración básica**:
   - **Name**: `staging-magicai-final`
   - **AMI**: `ami-00cee3e99312902ad`
   - **Instance type**: `t3.small` (igual que producción)
   - **Key pair**: `staging-tausepro-key`
   - **Network**: Misma VPC/subnet que producción
   - **Security Group**: `sg-0933986b1aa1f35eb` (o crear nuevo con SSH, HTTP, HTTPS)
   - **Storage**: 30GB

3. **Advanced details**:
   - **User Data**: **DEJAR VACÍO** inicialmente
   - O usar User Data que NO habilite firewall

4. **Launch instance**

5. ⏳ Esperar 1-2 minutos a que inicie

6. **Conectar inmediatamente**:
   ```bash
   ssh -4 -i staging-tausepro-key.pem ubuntu@NUEVA_IP
   ```

### Paso 3: Configurar Manualmente (Una Vez Conectado)

```bash
# 1. Verificar que conectaste
whoami
hostname

# 2. Verificar servicios base
sudo systemctl status ssh
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# 3. Verificar firewall (debería estar deshabilitado por defecto)
sudo ufw status

# 4. Configurar .env para staging
cd /var/www/magicai
sudo cp .env .env.production.backup
sudo nano .env

# Cambiar:
# APP_ENV=staging
# APP_DEBUG=true
# APP_URL=http://test.tause.pro
# DB_DATABASE=magicai_staging
# DB_USERNAME=magicai_staging
# DB_PASSWORD=staging_password_2024

# 5. Crear base de datos
sudo mysql
CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 6. Configurar Nginx para test.tause.pro
sudo nano /etc/nginx/sites-available/test.tause.pro
```

Pegar configuración:

```nginx
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
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

```bash
# Activar sitio
sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# 7. Configurar permisos
sudo chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache
sudo chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache

# 8. Actualizar cache Laravel
cd /var/www/magicai
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Configurar firewall (AL FINAL, cuando todo funciona)
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# 10. Verificar que todo funciona
curl http://localhost
sudo systemctl status ssh
sudo systemctl status nginx
```

### Paso 4: Desplegar Cambios de Fase 1

**Desde tu máquina local:**

```bash
export STAGING_HOST=NUEVA_IP
./scripts/deploy-fase1-to-staging.sh
```

O copiar archivos manualmente.

---

## 🔧 User Data Correcto (Para Futuro)

Si quieres usar User Data, usa este que NO bloquea SSH:

```bash
#!/bin/bash
set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Configurando staging..."

# Deshabilitar firewall COMPLETAMENTE
ufw --force disable || true
iptables -F || true
iptables -X || true
iptables -P INPUT ACCEPT || true
iptables -P FORWARD ACCEPT || true
iptables -P OUTPUT ACCEPT || true

# Iniciar servicios
systemctl start ssh || true
systemctl enable ssh || true
systemctl start nginx || true
systemctl enable nginx || true
systemctl start php8.2-fpm || true
systemctl enable php8.2-fpm || true

# Permisos
chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true
chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true

# NO HABILITAR FIREWALL AQUÍ - Se hará manualmente después de verificar
# ufw enable  <-- NO HACER ESTO EN USER DATA

echo "✅ Configuración base completada!"
echo "⚠️  Firewall NO habilitado - hacerlo manualmente después de verificar"
```

---

## ✅ Checklist Completo

- [ ] Crear instancia desde AMI sin User Data (o con User Data que NO habilite firewall)
- [ ] Conectar vía SSH inmediatamente
- [ ] Configurar .env para staging
- [ ] Crear base de datos staging
- [ ] Configurar Nginx para test.tause.pro
- [ ] Configurar permisos
- [ ] Actualizar cache Laravel
- [ ] Desplegar cambios Fase 1
- [ ] Verificar que todo funciona
- [ ] **AL FINAL**: Configurar firewall

---

## 💡 Errores Comunes a Evitar

1. ❌ **Habilitar firewall en User Data**: Bloquea SSH antes de poder conectar
2. ❌ **Usar t3.micro**: Insuficiente para Laravel + MySQL
3. ❌ **No verificar servicios antes de habilitar firewall**
4. ❌ **Configurar firewall antes de que servicios estén listos**

---

## 🎯 Recomendación Final

**Crear nueva instancia SIN User Data**, conectar inmediatamente, configurar manualmente paso a paso, y solo al final habilitar firewall.

**Esto garantiza que siempre puedas conectar y verificar cada paso.**

---

**¿Quieres que te guíe para crear la nueva instancia correctamente ahora?**



