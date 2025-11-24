# 🚀 Comandos para Ejecutar EN STAGING

## ⚠️ IMPORTANTE

**Ejecuta estos comandos DESPUÉS de conectarte a staging:**

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
```

---

## Opción 1: Usar el Script (Más Fácil)

Una vez conectado a staging:

```bash
# Copiar script a staging
# (Desde tu máquina local, en otra terminal)
scp -4 -i staging-tausepro-key.pem scripts/configurar-nginx-staging.sh ubuntu@13.218.39.31:/tmp/

# Luego en staging:
chmod +x /tmp/configurar-nginx-staging.sh
sudo /tmp/configurar-nginx-staging.sh
```

---

## Opción 2: Comandos Manuales (Paso a Paso)

### 1. Crear Configuración de Nginx

```bash
sudo nano /etc/nginx/sites-available/test.tause.pro
```

Pega esto:

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

Guarda: `Ctrl+O`, `Enter`, `Ctrl+X`

### 2. Activar Sitio

```bash
sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/
```

### 3. Verificar y Recargar

```bash
sudo nginx -t
sudo systemctl reload nginx
```

### 4. Verificar que Funciona

```bash
curl http://localhost
curl http://test.tause.pro
```

---

## 🔍 Si Hay Problemas

### Ver Logs de Nginx

```bash
sudo tail -f /var/log/nginx/error.log
```

### Ver Logs de Laravel

```bash
tail -f /var/www/magicai-staging/storage/logs/laravel.log
```

### Verificar Permisos

```bash
ls -la /var/www/magicai-staging/public/index.php
sudo chown -R www-data:www-data /var/www/magicai-staging
sudo chmod -R 775 /var/www/magicai-staging/storage
```

### Verificar Servicios

```bash
sudo systemctl status nginx
sudo systemctl status php8.2-fpm
```

---

## ✅ Checklist

- [ ] Conectado a staging vía SSH
- [ ] Configuración de Nginx creada
- [ ] Sitio activado (symlink creado)
- [ ] Nginx verificado (`nginx -t`)
- [ ] Nginx recargado
- [ ] Sitio responde (`curl http://localhost`)
- [ ] Dominio funciona (`curl http://test.tause.pro`)

---

**Ejecuta estos comandos manualmente. Los scripts automatizados se están quedando colgados.**



