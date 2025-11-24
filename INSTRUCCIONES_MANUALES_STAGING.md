# 🔧 Instrucciones Manuales para Completar Staging

## ⚠️ Problema Actual

- Dominio `test.tause.pro` apunta a `13.218.39.31` pero no carga
- Los scripts automatizados se quedan colgados
- Necesitas ejecutar comandos manualmente

---

## 🚀 Solución Paso a Paso

### Paso 1: Conectar a Staging

**Abre una terminal nueva** y ejecuta:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
```

Si se queda colgado, espera 30 segundos y presiona `Ctrl+C`, luego intenta de nuevo.

---

### Paso 2: Verificar Nginx

Una vez conectado, ejecuta:

```bash
# Ver configuración de Nginx
sudo cat /etc/nginx/sites-available/test.tause.pro

# O ver si existe
ls -la /etc/nginx/sites-available/ | grep test
```

**Si NO existe el archivo**, créalo:

```bash
sudo nano /etc/nginx/sites-available/test.tause.pro
```

Pega esta configuración:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name test.tause.pro;
    root /var/www/magicai-staging/public;
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

Guarda con `Ctrl+O`, `Enter`, `Ctrl+X`

---

### Paso 3: Activar Sitio y Recargar Nginx

```bash
# Crear symlink si no existe
sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/

# Verificar configuración
sudo nginx -t

# Si está OK, recargar
sudo systemctl reload nginx
```

---

### Paso 4: Verificar Security Group

El Security Group debe permitir HTTP (puerto 80) y HTTPS (puerto 443).

**Desde AWS Console:**
1. Ve a EC2 → Security Groups
2. Busca el Security Group de staging
3. Verifica reglas Inbound:
   - Puerto 80 desde `0.0.0.0/0` ✅
   - Puerto 443 desde `0.0.0.0/0` ✅

**O con AWS CLI:**

```bash
# Desde tu máquina local
aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0
```

---

### Paso 5: Verificar que Funciona

**Desde staging (SSH):**

```bash
# Verificar que Nginx está corriendo
sudo systemctl status nginx

# Verificar que PHP-FPM está corriendo
sudo systemctl status php8.2-fpm

# Probar localmente
curl http://localhost

# Ver logs si hay errores
sudo tail -f /var/log/nginx/error.log
```

**Desde tu máquina local:**

```bash
# Probar por IP
curl http://13.218.39.31

# Probar por dominio (si DNS está configurado)
curl http://test.tause.pro
```

---

### Paso 6: Actualizar Cache de Laravel

**En staging (SSH):**

```bash
cd /var/www/magicai-staging

# Limpiar
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Actualizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Verificar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 🔍 Diagnóstico Rápido

Si aún no funciona, ejecuta estos comandos **en staging**:

```bash
# 1. Ver estado de servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# 2. Ver logs de Nginx
sudo tail -20 /var/log/nginx/error.log

# 3. Ver logs de Laravel
tail -20 /var/www/magicai-staging/storage/logs/laravel.log

# 4. Verificar que el archivo index.php existe
ls -la /var/www/magicai-staging/public/index.php

# 5. Verificar permisos
ls -la /var/www/magicai-staging/storage/logs/
```

---

## 💡 Problemas Comunes

### 1. Nginx no está corriendo
```bash
sudo systemctl start nginx
sudo systemctl enable nginx
```

### 2. PHP-FPM no está corriendo
```bash
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm
```

### 3. Permisos incorrectos
```bash
sudo chown -R www-data:www-data /var/www/magicai-staging
sudo chmod -R 775 /var/www/magicai-staging/storage
sudo chmod -R 775 /var/www/magicai-staging/bootstrap/cache
```

### 4. Security Group bloquea
Agregar reglas HTTP/HTTPS desde AWS Console o CLI.

---

## ✅ Checklist Final

- [ ] Nginx configurado para `test.tause.pro`
- [ ] Sitio activado (`sites-enabled`)
- [ ] Nginx recargado
- [ ] Security Group permite puertos 80 y 443
- [ ] Servicios corriendo (Nginx, PHP-FPM)
- [ ] Permisos correctos
- [ ] Cache de Laravel actualizado
- [ ] Sitio accesible por IP: `http://13.218.39.31`
- [ ] Sitio accesible por dominio: `http://test.tause.pro`

---

**Ejecuta estos pasos manualmente. Los scripts automatizados se están quedando colgados.**



