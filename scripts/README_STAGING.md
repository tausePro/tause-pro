# 🚀 Guía Rápida: Configuración de Staging

## Inicio Rápido

### Opción 1: Configuración Automática Completa (Recomendado)

```bash
# 1. Configurar variables de entorno
export STAGING_HOST=1.2.3.4  # IP de tu instancia staging
export KEY_FILE=magicai-tause-key.pem

# 2. Ejecutar script maestro
./scripts/setup-staging-complete.sh
```

El script te guiará paso a paso por toda la configuración.

### Opción 2: Configuración Manual Paso a Paso

#### Paso 1: Crear Instancia EC2 (si no existe)

```bash
./scripts/create-staging-ec2.sh
# Anota la IP pública que se muestra
```

#### Paso 2: Configurar Servidor

```bash
export STAGING_HOST=1.2.3.4  # Tu IP de staging
./scripts/setup-staging-server.sh
```

#### Paso 3: Configurar Base de Datos

```bash
./scripts/setup-staging-database.sh
# Te pedirá contraseñas
```

#### Paso 4: Clonar Código desde Producción

```bash
./scripts/clone-code-to-staging.sh
```

#### Paso 5: Clonar Base de Datos desde Producción

```bash
./scripts/clone-production-db-to-staging.sh
# Te pedirá contraseñas de MySQL
```

#### Paso 6: Configurar Nginx

```bash
export DOMAIN=staging.magicai.com  # Tu dominio
./scripts/setup-staging-nginx.sh
```

#### Paso 7: (Opcional) Anonimizar Datos

```bash
./scripts/anonymize-staging-db.sh
```

#### Paso 8: Configurar Aplicación Laravel

```bash
# Conectar a staging
ssh -i magicai-tause-key.pem ubuntu@[STAGING_IP]

# En el servidor
cd /var/www/magicai-staging

# Configurar .env
nano .env
# Asegúrate de configurar:
# - APP_ENV=staging
# - APP_DEBUG=true
# - DB_DATABASE=magicai_staging
# - DB_USERNAME=magicai_staging
# - DB_PASSWORD=[tu contraseña]

# Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build

# Configurar Laravel
php artisan key:generate
php artisan migrate --force

# Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Deployment a Staging

Una vez configurado, para desplegar cambios:

```bash
export STAGING_HOST=1.2.3.4
./scripts/deploy-to-staging.sh
```

Este script:
- ✅ Hace backup automático
- ✅ Actualiza código desde Git
- ✅ Instala dependencias
- ✅ Ejecuta migraciones
- ✅ Optimiza cache
- ✅ Recarga servicios

---

## Variables de Entorno Disponibles

Puedes configurar estas variables antes de ejecutar los scripts:

```bash
# Servidor
export STAGING_HOST=1.2.3.4
export STAGING_USER=ubuntu
export PROD_HOST=34.207.248.220
export PROD_USER=ubuntu

# Archivos
export KEY_FILE=magicai-tause-key.pem
export PROJECT_NAME=magicai-staging

# Base de datos
export DB_NAME=magicai_staging
export DB_USER=magicai_staging
export PROD_DB_NAME=magicai

# Dominio
export DOMAIN=staging.magicai.com

# Git
export GIT_BRANCH=main
```

---

## Troubleshooting

### No puedo conectar por SSH

```bash
# Verificar permisos de la key
chmod 400 magicai-tause-key.pem

# Verificar Security Group permite puerto 22
# Verificar IP pública de la instancia
```

### Error al clonar base de datos

```bash
# Verificar que MySQL está corriendo en producción
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 "sudo systemctl status mysql"

# Verificar credenciales de MySQL
```

### Nginx muestra 502 Bad Gateway

```bash
# En staging
ssh -i magicai-tause-key.pem ubuntu@[STAGING_IP]

# Verificar PHP-FPM
sudo systemctl status php8.2-fpm

# Verificar logs
sudo tail -f /var/log/nginx/staging-error.log
```

### Aplicación muestra errores

```bash
# Ver logs de Laravel
ssh -i magicai-tause-key.pem ubuntu@[STAGING_IP]
tail -f /var/www/magicai-staging/storage/logs/laravel.log

# Limpiar cache
php artisan config:clear
php artisan cache:clear
```

---

## Estructura de Scripts

```
scripts/
├── setup-staging-complete.sh          # Script maestro (guía todo)
├── create-staging-ec2.sh              # Crear instancia EC2
├── setup-staging-server.sh            # Configurar servidor
├── setup-staging-database.sh          # Crear BD y usuario
├── clone-code-to-staging.sh           # Clonar código
├── clone-production-db-to-staging.sh  # Clonar BD
├── setup-staging-nginx.sh             # Configurar Nginx
├── anonymize-staging-db.sh            # Anonimizar datos
└── deploy-to-staging.sh               # Deployment a staging
```

---

## Workflow Recomendado

### Desarrollo Normal

1. **Desarrollar** en local
2. **Probar** en local
3. **Deploy a staging**: `./scripts/deploy-to-staging.sh`
4. **Probar en staging** con datos reales
5. **Deploy a producción**: Usar tu proceso actual

### Sincronización Periódica

```bash
# Cada semana o antes de releases importantes
./scripts/clone-production-db-to-staging.sh
./scripts/anonymize-staging-db.sh  # Opcional pero recomendado
```

---

## Costos Estimados

- EC2 t3.small: ~$15 USD/mes
- Storage 20GB: ~$2 USD/mes
- Transferencia: ~$1-5 USD/mes
- **Total: ~$18-22 USD/mes**

---

## Seguridad

⚠️ **IMPORTANTE:**

1. ✅ Usa IP whitelist en Security Group si contiene datos reales
2. ✅ Anonimiza datos antes de usar staging
3. ✅ Usa claves de API de prueba en staging
4. ✅ NO expongas staging públicamente si tiene datos sensibles
5. ✅ Rota passwords regularmente

---

## Documentación Completa

Para más detalles, consulta: `STAGING_AWS_SETUP.md`

---

**¿Necesitas ayuda?** Revisa los logs y el troubleshooting arriba.



