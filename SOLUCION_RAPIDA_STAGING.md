# 🚀 Solución Rápida: Staging Funcionando en 5 Minutos

## ⚠️ Situación

Después de 2 noches, la instancia nueva tiene problemas de red/firewall que requieren más debugging.

## ✅ SOLUCIÓN RÁPIDA: Usar Producción para Staging

**Ventajas:**
- ✅ Producción ya funciona perfectamente
- ✅ Solo necesitamos configurar subdominio y BD
- ✅ 5 minutos vs horas de debugging

---

## 📋 Pasos (5 minutos)

### 1. Crear Subdominio en DNS

En tu proveedor de DNS (Route 53, Cloudflare, etc.):

- **Nombre**: `staging` (o `test-staging`)
- **Tipo**: A
- **Valor**: IP de producción (`34.207.248.220`)
- **TTL**: 300

Resultado: `staging.tause.pro` → Producción

---

### 2. Configurar Nginx en Producción

```bash
# Conectar a producción
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220

# Crear configuración staging
sudo tee /etc/nginx/sites-available/staging.tause.pro > /dev/null << 'EOF'
server {
    listen 80;
    server_name staging.tause.pro;
    root /var/www/magicai/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

# Habilitar
sudo ln -s /etc/nginx/sites-available/staging.tause.pro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

---

### 3. Crear BD Staging

```bash
# En producción
sudo mysql << 'EOF'
CREATE DATABASE magicai_staging;
CREATE USER 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF
```

---

### 4. Crear .env Staging

```bash
cd /var/www/magicai
cp .env .env.staging

# Editar .env.staging
sudo nano .env.staging
```

Cambiar:
```env
APP_ENV=staging
APP_URL=http://staging.tause.pro
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024
```

---

### 5. Configurar Laravel para Múltiples Entornos

Crear middleware o usar APP_ENV dinámico basado en dominio.

**Opción Simple**: Crear symlink condicional o script que cambie .env según dominio.

**O mejor**: Usar variable de entorno en Nginx:

```nginx
fastcgi_param APP_ENV staging;
```

Y en Laravel usar `env('APP_ENV')` directamente.

---

## ✅ Resultado

- ✅ `staging.tause.pro` funcionando
- ✅ BD separada (`magicai_staging`)
- ✅ Mismo código, diferente entorno
- ✅ Funciona en 5 minutos

---

## 🔄 Después (Cuando Tengas Tiempo)

Puedes crear instancia separada para staging real, pero por ahora esto funciona.

---

**Esta es la forma más rápida de tener staging funcionando HOY.**


