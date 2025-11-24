# 🔧 Solución Definitiva: SSH Completamente Bloqueado

## ⚠️ Problema Crítico

- ❌ SSH no funciona desde Mac
- ❌ EC2 Instance Connect no funciona
- ❌ **SSH está completamente bloqueado**

**Causa**: El User Data está habilitando el firewall ANTES de que SSH esté listo, bloqueando todas las conexiones.

---

## 🚀 Solución: Detener, Modificar User Data, Reiniciar

Como no podemos acceder a la instancia, debemos:

1. **Detener la instancia** (desde AWS Console)
2. **Modificar User Data** para que NO habilite firewall
3. **Iniciar la instancia** de nuevo
4. El nuevo User Data permitirá SSH

---

## 📋 Pasos Detallados

### Paso 1: Detener Instancia

**AWS Console**:
1. Ve a: https://console.aws.amazon.com/ec2/
2. **Instances** → Busca `i-0bbe91c13a1343538`
3. **Selecciona** → **Instance state** → **Stop instance**
4. Confirma
5. ⏳ Espera hasta que estado sea **"Stopped"** (2-3 minutos)

### Paso 2: Modificar User Data

**Mientras la instancia está detenida**:

1. **Selecciona la instancia** (aunque esté detenida)
2. **Actions** → **Instance settings** → **Edit user data**
3. **Borra todo** el contenido actual
4. **Pega este contenido** (User Data que NO habilita firewall):

```bash
#!/bin/bash
set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Configurando staging (SIN firewall inicial)..."
echo "=================================================="

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

# Permisos básicos
chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true
chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache || true

# Verificar servicios
systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH inactivo"
systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"

echo ""
echo "✅ Configuración base completada!"
echo "⚠️  Firewall NO habilitado - configurar manualmente después de conectar"
echo "📝 Para habilitar firewall después de conectar:"
echo "   sudo ufw allow 22/tcp"
echo "   sudo ufw allow 80/tcp"
echo "   sudo ufw allow 443/tcp"
echo "   sudo ufw enable"
```

5. **Save**

### Paso 3: Iniciar Instancia

1. **Selecciona la instancia**
2. **Instance state** → **Start instance**
3. ⏳ Espera hasta que estado sea **"Running"** (1-2 minutos)

### Paso 4: Conectar vía SSH

**Después de que la instancia esté "Running"**:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

**Debería funcionar ahora** porque el User Data NO habilita el firewall.

### Paso 5: Configurar Manualmente

Una vez conectado, ejecuta:

```bash
# Verificar que conectaste
whoami
hostname

# Verificar servicios
sudo systemctl status ssh
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# Verificar .env
cd /var/www/magicai
cat .env | grep APP_ENV
cat .env | grep DB_DATABASE

# Si falta configurar .env
sudo nano .env
# Cambiar: APP_ENV=staging, APP_URL=http://test.tause.pro, DB_DATABASE=magicai_staging, etc.

# Crear BD si falta
sudo mysql
CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Configurar Nginx si falta
sudo nano /etc/nginx/sites-available/test.tause.pro
# (Ver GUIA_COMPLETA_CREAR_STAGING_DESDE_AMI.md para configuración completa)

# Activar sitio Nginx
sudo ln -sf /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Cache Laravel
php artisan config:clear && php artisan cache:clear
php artisan config:cache && php artisan route:cache

# Verificar HTTP funciona
curl http://localhost

# AL FINAL: Configurar firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Verificar firewall
sudo ufw status
```

---

## ✅ Verificación Final

```bash
# SSH debería funcionar
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213

# HTTP debería responder
curl http://test.tause.pro

# O en navegador
open http://test.tause.pro
```

---

## ⚠️ Importante

**El User Data nuevo NO habilita el firewall**, así que:
- ✅ SSH funcionará inmediatamente después de iniciar
- ⚠️ Debes configurar el firewall manualmente DESPUÉS de conectar
- ✅ Una vez configurado, el firewall protegerá la instancia

---

**Esta es la ÚNICA forma de arreglar la instancia sin crear una nueva.**



