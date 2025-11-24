# ✅ Solución: Arreglar Instancia Actual

## 📋 Situación

- **Instancia**: `i-0bbe91c13a1343538` (44.211.83.213)
- **Estado**: Running
- **Problema**: SSH no conecta (firewall bloqueando)
- **Systems Manager**: No autorizado

---

## 🚀 Opción 1: EC2 Instance Connect (RECOMENDADO - 5 minutos)

### Pasos:

1. **AWS Console**: https://console.aws.amazon.com/ec2/
2. **Instances** → Busca `i-0bbe91c13a1343538`
3. **Selecciona** → **Connect** → **EC2 Instance Connect** → **Connect**

### Comandos (Copia y Pega):

```bash
sudo ufw --force disable
sudo iptables -F
sudo iptables -X
sudo iptables -P INPUT ACCEPT
sudo iptables -P FORWARD ACCEPT
sudo iptables -P OUTPUT ACCEPT
sudo systemctl start ssh && sudo systemctl enable ssh
sudo systemctl start nginx && sudo systemctl enable nginx
sudo systemctl start php8.2-fpm && sudo systemctl enable php8.2-fpm
sudo chown -R www-data:www-data /var/www/magicai/storage /var/www/magicai/bootstrap/cache
sudo chmod -R 775 /var/www/magicai/storage /var/www/magicai/bootstrap/cache
curl http://localhost
sudo ufw allow 22/tcp && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp
sudo ufw --force enable
```

**Después**: Prueba SSH desde tu Mac.

---

## 🔄 Opción 2: Modificar User Data y Reiniciar (10 minutos)

### Pasos:

1. **AWS Console** → Instances → `i-0bbe91c13a1343538`
2. **Actions** → **Instance settings** → **Edit user data**
3. **Copiar contenido completo** de `scripts/user-data-sin-firewall.sh`
4. **Pegar en User Data**
5. **Save**
6. **Reboot instance** (Actions → Instance state → Reboot)
7. ⏳ Esperar 2-3 minutos
8. **Probar SSH**:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

Si conecta, ejecuta:

```bash
# Configurar .env si falta
cd /var/www/magicai
sudo nano .env  # Cambiar APP_ENV=staging, DB_DATABASE=magicai_staging, etc.

# Crear BD si falta
sudo mysql
CREATE DATABASE IF NOT EXISTS magicai_staging;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Configurar Nginx si falta
sudo nano /etc/nginx/sites-available/test.tause.pro
# (Ver GUIA_COMPLETA_CREAR_STAGING_DESDE_AMI.md para configuración)

# Permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Cache Laravel
php artisan config:clear && php artisan cache:clear
php artisan config:cache && php artisan route:cache

# Firewall (si no se configuró en User Data)
sudo ufw allow 22/tcp && sudo ufw allow 80/tcp && sudo ufw allow 443/tcp
sudo ufw enable
```

---

## ✅ Verificación Final

Después de cualquiera de las opciones:

```bash
# SSH debería funcionar
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213

# HTTP debería responder
curl http://test.tause.pro

# O en navegador
open http://test.tause.pro
```

---

## 💡 Recomendación

**Usa Opción 1 (EC2 Instance Connect)** - Es más rápido y no requiere reiniciar la instancia.

---

**¿Cuál prefieres usar?**



