# 🎯 Plan Correcto: Crear Staging para Testear Fase 1

## 📊 Situación Real

- ✅ **Producción**: Funciona, pero **NO tiene cambios de Fase 1**
- ✅ **Local**: Tiene cambios de Fase 1 (AgentOrchestratorService, etc.)
- 🎯 **Objetivo**: Crear staging para testear cambios de Fase 1

---

## 🚀 Plan Correcto

### Paso 1: Crear Nueva Instancia Staging desde Imagen de Producción

**Usar imagen de producción actual** (que funciona):
- **AMI**: `ami-00cee3e99312902ad` ("antes de actualizar agentes")
- **Instance type**: `t3.small` (igual que producción)
- **Storage**: 30GB
- **User Data**: Script para configurar firewall y servicios

**Razón**: Queremos una instancia que funcione primero, luego desplegamos cambios.

### Paso 2: Configurar Staging Básico

Una vez creada la instancia:
1. Configurar Nginx para `test.tause.pro`
2. Clonar BD desde producción (si es necesario)
3. Configurar `.env` para staging
4. Verificar que funciona básicamente

### Paso 3: Desplegar Cambios de Fase 1

Una vez que staging funciona básicamente:
1. Copiar archivos de Fase 1 desde local
2. Actualizar cache
3. Verificar que funciona con cambios

---

## 📋 Pasos Detallados

### Paso 1: Crear Nueva Instancia

1. EC2 → Launch Instance
2. **AMI**: `ami-00cee3e99312902ad` ("antes de actualizar agentes")
3. **Instance type**: `t3.small`
4. **Key pair**: `staging-tausepro-key`
5. **Network**: Misma VPC/subnet que producción
6. **Security Group**: `sg-0933986b1aa1f35eb`
7. **Storage**: 30GB
8. **Advanced details** → **User data**:

```bash
#!/bin/bash
set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Configurando staging..."

# Deshabilitar firewall
ufw --force disable || true
iptables -F || true
iptables -P INPUT ACCEPT || true

# Iniciar servicios
systemctl start ssh || true
systemctl enable ssh || true
systemctl start nginx || true
systemctl enable nginx || true
systemctl start php8.2-fpm || true
systemctl enable php8.2-fpm || true

# Permisos
chown -R www-data:www-data /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true
chmod -R 775 /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true

# Configurar firewall
ufw --force reset || true
ufw default deny incoming || true
ufw default allow outgoing || true
ufw allow 22/tcp || true
ufw allow 80/tcp || true
ufw allow 443/tcp || true
ufw --force enable || true

echo "✅ Configuración completada!"
```

9. Launch instance
10. Anotar nueva IP

### Paso 2: Configurar Staging Básico

```bash
# Conectar (debería funcionar ahora)
ssh -4 -i staging-tausepro-key.pem ubuntu@NUEVA_IP

# Configurar .env para staging
cd /var/www/magicai-staging
nano .env

# Cambiar:
# APP_ENV=staging
# APP_DEBUG=true
# APP_URL=http://test.tause.pro
# DB_DATABASE=magicai_staging
# DB_USERNAME=magicai_staging
# DB_PASSWORD=staging_password_2024

# Clonar BD desde producción (si es necesario)
# O crear BD vacía y ejecutar migraciones

# Configurar Nginx para test.tause.pro
sudo nano /etc/nginx/sites-available/test.tause.pro
# (Pegar configuración de INSTRUCCIONES_MANUALES_STAGING.md)

sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx

# Verificar que funciona
curl http://localhost
```

### Paso 3: Desplegar Cambios de Fase 1

**Desde tu máquina local:**

```bash
# Usar script de deploy
export STAGING_HOST=NUEVA_IP
./scripts/deploy-fase1-to-staging.sh
```

**O manualmente:**

```bash
# Copiar archivos uno por uno
scp -4 -i staging-tausepro-key.pem \
  app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php \
  ubuntu@NUEVA_IP:/tmp/

scp -4 -i staging-tausepro-key.pem \
  app/Extensions/Chatbot/System/Services/AgentIntelligenceService.php \
  ubuntu@NUEVA_IP:/tmp/

# En staging, mover archivos
ssh -4 -i staging-tausepro-key.pem ubuntu@NUEVA_IP
sudo mv /tmp/AgentOrchestratorService.php /var/www/magicai-staging/app/Extensions/Chatbot/System/Services/
sudo mv /tmp/AgentIntelligenceService.php /var/www/magicai-staging/app/Extensions/Chatbot/System/Services/

# Actualizar cache
cd /var/www/magicai-staging
php artisan config:clear
php artisan cache:clear
php artisan config:cache
php artisan route:cache
```

---

## ✅ Checklist

- [ ] Crear nueva instancia desde imagen de producción (`ami-00cee3e99312902ad`)
- [ ] Configurar con User Data
- [ ] Conectar vía SSH
- [ ] Configurar `.env` para staging
- [ ] Configurar Nginx para `test.tause.pro`
- [ ] Clonar BD o crear BD vacía
- [ ] Verificar que staging funciona básicamente
- [ ] Desplegar cambios de Fase 1 desde local
- [ ] Verificar que cambios funcionan
- [ ] Actualizar DNS para `test.tause.pro`

---

## 💡 Resumen

1. **NO crear imagen de producción** (no tiene cambios de Fase 1)
2. **Crear instancia staging** desde imagen de producción actual
3. **Configurar staging básico** (Nginx, BD, .env)
4. **Desplegar cambios de Fase 1** desde local a staging
5. **Testear** cambios en staging antes de llevar a producción

---

**¿Procedemos a crear la nueva instancia staging desde la imagen de producción actual?**



