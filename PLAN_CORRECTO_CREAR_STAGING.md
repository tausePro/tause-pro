# ✅ Plan Correcto: Crear Staging desde AMI

## 🎯 Estrategia Correcta

**Crear instancia SIN User Data que habilite firewall** → Conectar inmediatamente → Configurar manualmente → Habilitar firewall al final.

---

## 🚀 Pasos Detallados

### Paso 1: Crear Nueva Instancia

1. **AWS Console**: https://console.aws.amazon.com/ec2/
2. **Launch Instance**

3. **Configuración**:
   - **Name**: `staging-magicai-final`
   - **AMI**: `ami-00cee3e99312902ad` ("antes de actualizar agentes")
   - **Instance type**: `t3.small` (NO t3.micro)
   - **Key pair**: `staging-tausepro-key`
   - **Network settings**:
     - VPC: Misma que producción
     - Subnet: Misma que producción
     - Auto-assign Public IP: **Enable**
     - Security Group: Seleccionar `sg-0933986b1aa1f35eb` (o crear nuevo con SSH, HTTP, HTTPS)
   - **Storage**: 30GB
   - **Advanced details** → **User Data**: 
     - **Opción A**: Dejar vacío (recomendado)
     - **Opción B**: Usar script de `scripts/user-data-sin-firewall.sh` (NO habilita firewall)

4. **Launch instance**

5. ⏳ Esperar 1-2 minutos

6. **Anotar nueva IP pública**

### Paso 2: Conectar Inmediatamente

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@NUEVA_IP
```

**Debería funcionar inmediatamente** porque no hay firewall bloqueando.

### Paso 3: Configurar Staging (En el Servidor)

Una vez conectado, ejecuta estos comandos:

```bash
# 1. Verificar servicios
sudo systemctl status ssh
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# 2. Configurar .env
cd /var/www/magicai
sudo cp .env .env.production.backup
sudo nano .env
# Cambiar: APP_ENV=staging, APP_URL=http://test.tause.pro, DB_DATABASE=magicai_staging, etc.

# 3. Crear BD
sudo mysql
CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# 4. Configurar Nginx (ver GUIA_COMPLETA_CREAR_STAGING_DESDE_AMI.md)

# 5. Permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 6. Cache Laravel
php artisan config:clear && php artisan cache:clear
php artisan config:cache && php artisan route:cache

# 7. Verificar que funciona
curl http://localhost

# 8. AL FINAL: Configurar firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

### Paso 4: Desplegar Fase 1

**Desde tu Mac**:

```bash
export STAGING_HOST=NUEVA_IP
./scripts/deploy-fase1-to-staging.sh
```

---

## ✅ Ventajas de Este Enfoque

1. ✅ **SSH funciona inmediatamente** (no hay firewall bloqueando)
2. ✅ **Puedes verificar cada paso** antes de continuar
3. ✅ **Control total** sobre la configuración
4. ✅ **No hay sorpresas** con firewall bloqueando

---

## 📋 Checklist

- [ ] Crear instancia SIN User Data (o con User Data sin firewall)
- [ ] Conectar vía SSH inmediatamente
- [ ] Configurar .env
- [ ] Crear BD
- [ ] Configurar Nginx
- [ ] Permisos
- [ ] Cache Laravel
- [ ] Desplegar Fase 1
- [ ] Verificar que funciona
- [ ] Habilitar firewall al final

---

**Este enfoque garantiza que siempre puedas conectar y configurar correctamente.**



