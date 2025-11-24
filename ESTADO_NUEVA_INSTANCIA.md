# ✅ Estado Nueva Instancia Staging

## 📊 Información

- **IP**: `44.211.83.213`
- **Instance ID**: `i-0bbe91c13a1343538`
- **Estado**: ✅ Running
- **SSH**: ✅ Funciona
- **Nginx**: ✅ Activo
- **PHP-FPM**: ✅ Activo
- **Firewall**: ✅ Configurado

---

## ✅ Lo que Funciona

1. ✅ **Conexión SSH**: Funciona correctamente
2. ✅ **Servicios**: SSH, Nginx, PHP-FPM activos
3. ✅ **Firewall**: Configurado con reglas SSH, HTTP, HTTPS
4. ✅ **Instancia**: Corriendo y estable

---

## 📋 Próximos Pasos

### Paso 1: Verificar Directorio del Proyecto

La instancia tiene `/var/www/magicai` (parece ser producción).

**Necesitas:**
- Verificar si es producción o staging
- O crear directorio `/var/www/magicai-staging`
- O renombrar y configurar para staging

### Paso 2: Configurar para Staging

```bash
# Conectar
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213

# Verificar directorio
ls -la /var/www/

# Si es producción, crear staging
sudo mkdir -p /var/www/magicai-staging
# O configurar el existente para staging
```

### Paso 3: Configurar .env para Staging

```bash
cd /var/www/magicai-staging  # o /var/www/magicai
nano .env

# Cambiar:
# APP_ENV=staging
# APP_DEBUG=true
# APP_URL=http://test.tause.pro
# DB_DATABASE=magicai_staging
# DB_USERNAME=magicai_staging
# DB_PASSWORD=staging_password_2024
```

### Paso 4: Configurar Nginx para test.tause.pro

```bash
sudo nano /etc/nginx/sites-available/test.tause.pro
# (Pegar configuración)

sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Paso 5: Configurar Base de Datos

```bash
# Crear BD y usuario
sudo mysql
CREATE DATABASE magicai_staging;
CREATE USER 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# O clonar desde producción
```

### Paso 6: Desplegar Cambios de Fase 1

```bash
# Desde tu máquina local
export STAGING_HOST=44.211.83.213
./scripts/deploy-fase1-to-staging.sh
```

---

## ✅ Checklist

- [x] Instancia creada y corriendo
- [x] SSH funciona
- [x] Servicios activos
- [x] Firewall configurado
- [ ] Verificar directorio del proyecto
- [ ] Configurar .env para staging
- [ ] Configurar Nginx para test.tause.pro
- [ ] Configurar base de datos
- [ ] Desplegar cambios de Fase 1
- [ ] Actualizar DNS

---

**La instancia está funcionando correctamente. Ahora necesitas configurarla para staging.**



