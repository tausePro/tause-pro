# 🚀 Plan de Configuración de Staging en AWS

## 📋 Resumen Ejecutivo

Este documento describe el proceso completo para crear un entorno de **staging** en AWS basado en el entorno de producción actual, permitiendo probar nuevas funcionalidades antes de desplegarlas a producción.

---

## 🎯 Objetivos

1. ✅ Crear servidor EC2 separado para staging
2. ✅ Replicar configuración de producción
3. ✅ Clonar base de datos de producción (con datos anonimizados opcionales)
4. ✅ Configurar dominio/subdominio para staging
5. ✅ Establecer workflow de deployment a staging
6. ✅ Mantener staging sincronizado con producción periódicamente

---

## 🏗️ Arquitectura Propuesta

```
┌─────────────────────────────────────────────────────────┐
│                    PRODUCCIÓN                           │
│  EC2: 34.207.248.220                                    │
│  Path: /var/www/magicai                                 │
│  Domain: magicai.com (o tu dominio actual)              │
│  DB: magicai_prod                                        │
└─────────────────────────────────────────────────────────┘
                        │
                        │ (Sincronización periódica)
                        ▼
┌─────────────────────────────────────────────────────────┐
│                    STAGING                              │
│  EC2: [NUEVA IP]                                        │
│  Path: /var/www/magicai-staging                         │
│  Domain: staging.magicai.com (o staging.tu-dominio.com) │
│  DB: magicai_staging                                     │
└─────────────────────────────────────────────────────────┘
```

---

## 📦 Requisitos Previos

### 1. Acceso AWS
- ✅ Cuenta AWS activa
- ✅ Permisos para crear EC2 instances
- ✅ Permisos para crear Security Groups
- ✅ Acceso a RDS (si usas RDS) o MySQL en EC2

### 2. Información de Producción
- ✅ IP de producción: `34.207.248.220`
- ✅ Usuario SSH: `ubuntu`
- ✅ Key file: `magicai-tause-key.pem`
- ✅ Path producción: `/var/www/magicai`
- ✅ Base de datos: `magicai` (o nombre actual)

### 3. Recursos Necesarios
- ✅ Nueva instancia EC2 (t3.small o similar)
- ✅ Elastic IP para staging (opcional pero recomendado)
- ✅ Security Group para staging
- ✅ Dominio/subdominio para staging

---

## 🚀 FASE 1: Crear Servidor EC2 para Staging

### Paso 1.1: Crear Instancia EC2

**Opción A: Desde AWS Console**
1. Ir a EC2 → Launch Instance
2. Seleccionar AMI: Ubuntu Server 24.04 LTS (mismo que producción)
3. Instance Type: `t3.small` (igual que producción)
4. Key Pair: Usar la misma key (`magicai-tause-key.pem`)
5. Network Settings:
   - VPC: Misma que producción
   - Subnet: Misma que producción
   - Auto-assign Public IP: Enable
   - Security Group: Crear nuevo (ver Paso 1.2)
6. Storage: 20 GB gp3 (igual que producción)
7. Launch Instance

**Opción B: Desde CLI (más rápido)**
```bash
# Ver script: scripts/create-staging-ec2.sh
./scripts/create-staging-ec2.sh
```

### Paso 1.2: Configurar Security Group

Crear Security Group `staging-sg` con reglas:

| Tipo | Puerto | Origen | Descripción |
|------|--------|--------|-------------|
| SSH | 22 | Tu IP / 0.0.0.0/0 | Acceso SSH |
| HTTP | 80 | 0.0.0.0/0 | HTTP |
| HTTPS | 443 | 0.0.0.0/0 | HTTPS |
| MySQL | 3306 | IP de producción | Sincronización BD (opcional) |

**Comando AWS CLI:**
```bash
aws ec2 create-security-group \
  --group-name staging-sg \
  --description "Security group for staging environment" \
  --vpc-id vpc-xxxxx

aws ec2 authorize-security-group-ingress \
  --group-id sg-xxxxx \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
  --group-id sg-xxxxx \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
  --group-id sg-xxxxx \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0
```

### Paso 1.3: Asignar Elastic IP (Opcional pero Recomendado)

```bash
# Crear Elastic IP
aws ec2 allocate-address --domain vpc

# Asociar a instancia staging
aws ec2 associate-address \
  --instance-id i-xxxxx \
  --allocation-id eipalloc-xxxxx
```

**Anotar la IP pública** para usar en los scripts.

---

## 🔧 FASE 2: Configurar Servidor Staging

### Paso 2.1: Ejecutar Script de Configuración Inicial

```bash
# Desde tu máquina local
cd /Users/tause/Documents/proyectos/tausepro9.4
./scripts/setup-staging-server.sh
```

Este script:
- ✅ Actualiza el sistema
- ✅ Instala PHP 8.2, Composer, MySQL, Nginx
- ✅ Configura usuario y permisos
- ✅ Clona el repositorio (o prepara estructura)
- ✅ Configura variables de entorno básicas

### Paso 2.2: Verificar Configuración

```bash
# Conectar a staging
ssh -i magicai-tause-key.pem ubuntu@[STAGING_IP]

# Verificar PHP
php -v

# Verificar Composer
composer --version

# Verificar MySQL
mysql --version

# Verificar Nginx
nginx -v
```

---

## 🗄️ FASE 3: Configurar Base de Datos

### Paso 3.1: Crear Base de Datos Staging

```bash
# En servidor staging
mysql -u root -p

CREATE DATABASE magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'magicai_staging'@'localhost' IDENTIFIED BY '[PASSWORD_SEGURO]';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### Paso 3.2: Clonar Base de Datos desde Producción

**Opción A: Usar script automatizado (Recomendado)**
```bash
# Desde tu máquina local
./scripts/clone-production-db-to-staging.sh
```

**Opción B: Manual**
```bash
# 1. Exportar desde producción
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 \
  "mysqldump -u magicai_user -p magicai > /tmp/magicai_prod.sql"

# 2. Descargar dump
scp -i magicai-tause-key.pem \
  ubuntu@34.207.248.220:/tmp/magicai_prod.sql \
  ./magicai_prod.sql

# 3. Importar en staging
scp -i magicai-tause-key.pem \
  ./magicai_prod.sql \
  ubuntu@[STAGING_IP]:/tmp/

ssh -i magicai-tause-key.pem ubuntu@[STAGING_IP] \
  "mysql -u magicai_staging -p magicai_staging < /tmp/magicai_prod.sql"
```

### Paso 3.3: Anonimizar Datos Sensibles (Opcional pero Recomendado)

```bash
# Ejecutar script de anonimización
./scripts/anonymize-staging-db.sh
```

Este script:
- ✅ Anonimiza emails de usuarios
- ✅ Anonimiza números de teléfono
- ✅ Limpia tokens de API
- ✅ Resetea passwords a valores de prueba

---

## 🌐 FASE 4: Configurar Nginx y Dominio

### Paso 4.1: Crear Configuración Nginx para Staging

```bash
# En servidor staging
sudo nano /etc/nginx/sites-available/magicai-staging
```

**Contenido:**
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name staging.magicai.com;  # O tu subdominio

    root /var/www/magicai-staging/public;
    index index.php index.html;

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

    # Logs
    access_log /var/log/nginx/staging-access.log;
    error_log /var/log/nginx/staging-error.log;
}
```

### Paso 4.2: Activar Sitio

```bash
sudo ln -s /etc/nginx/sites-available/magicai-staging /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### Paso 4.3: Configurar DNS

En tu proveedor de DNS, agregar registro A:
```
staging.magicai.com → [STAGING_IP]
```

O si prefieres usar IP directamente, puedes acceder por IP temporalmente.

---

## 📝 FASE 5: Configurar Aplicación Laravel

### Paso 5.1: Clonar Código

```bash
# En servidor staging
cd /var/www
sudo git clone [TU_REPO_GIT] magicai-staging
# O si no usas Git, copiar desde producción:
# sudo rsync -avz --exclude 'vendor' --exclude 'node_modules' \
#   ubuntu@34.207.248.220:/var/www/magicai/ \
#   /var/www/magicai-staging/
```

### Paso 5.2: Configurar Variables de Entorno

```bash
cd /var/www/magicai-staging
cp .env.example .env
nano .env
```

**Configuración mínima:**
```env
APP_NAME="MagicAI Staging"
APP_ENV=staging
APP_KEY=base64:...  # Generar con: php artisan key:generate
APP_DEBUG=true
APP_URL=https://staging.magicai.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=[PASSWORD_SEGURO]

# Usar claves de API de prueba/staging
OPENAI_API_KEY=sk-test-...
WHATSAPP_API_KEY=...
```

### Paso 5.3: Instalar Dependencias y Configurar

```bash
cd /var/www/magicai-staging

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Configurar Laravel
php artisan key:generate
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ejecutar migraciones (si hay nuevas)
php artisan migrate --force

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

---

## 🚀 FASE 6: Establecer Workflow de Deployment

### Paso 6.1: Crear Script de Deployment a Staging

```bash
# Desde tu máquina local
./scripts/deploy-to-staging.sh
```

Este script:
- ✅ Hace backup de staging
- ✅ Actualiza código desde Git
- ✅ Instala dependencias
- ✅ Ejecuta migraciones
- ✅ Limpia y optimiza cache
- ✅ Verifica que todo funciona

### Paso 6.2: Crear Script de Sincronización de BD

```bash
# Sincronizar BD de producción a staging periódicamente
./scripts/sync-production-to-staging-db.sh
```

**⚠️ ADVERTENCIA:** Este script sobrescribe la BD de staging con datos de producción.

---

## ✅ CHECKLIST COMPLETO

### Infraestructura
- [ ] Instancia EC2 creada
- [ ] Security Group configurado
- [ ] Elastic IP asignado (opcional)
- [ ] DNS configurado (staging.magicai.com)

### Servidor
- [ ] PHP 8.2 instalado
- [ ] Composer instalado
- [ ] MySQL instalado y configurado
- [ ] Nginx instalado y configurado
- [ ] Permisos de archivos correctos

### Base de Datos
- [ ] Base de datos `magicai_staging` creada
- [ ] Usuario de BD creado
- [ ] Datos clonados desde producción
- [ ] Datos anonimizados (opcional)

### Aplicación
- [ ] Código clonado/copiado
- [ ] `.env` configurado
- [ ] Dependencias instaladas
- [ ] Migraciones ejecutadas
- [ ] Cache optimizado

### Verificación
- [ ] Sitio accesible vía HTTP
- [ ] Login funciona
- [ ] Base de datos conecta
- [ ] Funcionalidades principales funcionan
- [ ] Logs sin errores críticos

---

## 🔄 Workflow Recomendado

### Desarrollo Normal
1. **Desarrollar** en local
2. **Probar** en local
3. **Deploy a staging**: `./scripts/deploy-to-staging.sh`
4. **Probar en staging** con datos reales
5. **Deploy a producción**: `./scripts/deploy-to-production.sh`

### Sincronización Periódica
```bash
# Cada semana o antes de releases importantes
./scripts/sync-production-to-staging-db.sh
```

---

## 🆘 Troubleshooting

### Problema: No puedo conectar por SSH
- Verificar Security Group permite puerto 22
- Verificar que la key tiene permisos correctos: `chmod 400 magicai-tause-key.pem`
- Verificar IP pública de la instancia

### Problema: Base de datos no conecta
- Verificar que MySQL está corriendo: `sudo systemctl status mysql`
- Verificar credenciales en `.env`
- Verificar que el usuario tiene permisos: `SHOW GRANTS FOR 'magicai_staging'@'localhost';`

### Problema: Nginx muestra 502 Bad Gateway
- Verificar PHP-FPM está corriendo: `sudo systemctl status php8.2-fpm`
- Verificar permisos de archivos: `sudo chown -R www-data:www-data /var/www/magicai-staging`
- Verificar logs: `sudo tail -f /var/log/nginx/staging-error.log`

### Problema: Aplicación muestra errores
- Verificar logs Laravel: `tail -f storage/logs/laravel.log`
- Verificar que APP_KEY está configurado
- Limpiar cache: `php artisan config:clear && php artisan cache:clear`

---

## 📊 Costos Estimados

| Recurso | Costo Mensual (aprox.) |
|---------|------------------------|
| EC2 t3.small | ~$15 USD |
| Elastic IP | $0 (si asociado a instancia) |
| Storage 20GB | ~$2 USD |
| Transferencia de datos | ~$1-5 USD |
| **TOTAL** | **~$18-22 USD/mes** |

---

## 🔐 Seguridad

### Recomendaciones
1. ✅ **NO exponer staging públicamente** si contiene datos reales
2. ✅ **Usar IP whitelist** en Security Group para acceso
3. ✅ **Anonimizar datos** antes de copiar a staging
4. ✅ **Usar claves de API de prueba** en staging
5. ✅ **Rotar passwords** regularmente
6. ✅ **Monitorear logs** de acceso

---

## 📝 Próximos Pasos

1. ✅ Revisar este documento
2. ✅ Ejecutar Fase 1 (Crear EC2)
3. ✅ Ejecutar Fase 2 (Configurar servidor)
4. ✅ Ejecutar Fase 3 (Configurar BD)
5. ✅ Ejecutar Fase 4 (Configurar Nginx)
6. ✅ Ejecutar Fase 5 (Configurar Laravel)
7. ✅ Ejecutar Fase 6 (Establecer workflow)

---

**¿Listo para empezar?** Ejecuta los scripts en orden y sigue las instrucciones.

**Fecha de creación:** $(date +%Y-%m-%d)
**Versión:** 1.0



