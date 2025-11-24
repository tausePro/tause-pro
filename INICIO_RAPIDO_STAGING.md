# 🚀 Inicio Rápido: Configurar Staging

Ya tienes una instancia EC2 de staging creada. Sigue estos pasos para configurarla completamente.

## 📋 Información de tu Instancia Staging

- **Instance ID**: `i-06113402909fd6b57`
- **IP Pública**: `13.218.39.31`
- **Tipo**: `t2.micro`
- **Estado**: Running ✅
- **Key File**: `staging-tausepro-key.pem` (ya guardada)

---

## ⚡ Configuración Rápida (4 pasos)

### Paso 0: Guardar la Clave Privada

**IMPORTANTE**: Guarda la clave privada que te proporcionaron:

```bash
cd /Users/tause/Documents/proyectos/tausepro9.4

# Crear archivo con la clave
nano staging-tausepro-key.pem
# Pega la clave completa (incluyendo BEGIN y END)
# Guarda y cierra (Ctrl+X, Y, Enter)

# Configurar permisos seguros
chmod 400 staging-tausepro-key.pem
```

### Paso 1: Cargar Variables de Configuración

```bash
cd /Users/tause/Documents/proyectos/tausepro9.4
source scripts/staging-config.sh
```

### Paso 2: Verificar Conexión

```bash
# Probar conexión SSH
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31 "echo 'Conexión exitosa'"
```

Si funciona, continúa. Si no, verifica:
- Que la instancia esté corriendo
- Que el Security Group permita SSH (puerto 22)
- Que la key tenga permisos correctos: `chmod 400 staging-tausepro-key.pem`

### Paso 3: Ejecutar Configuración Completa

```bash
# Opción A: Script maestro (recomendado)
./scripts/setup-staging-complete.sh

# Opción B: Paso a paso manual (ver abajo)
```

---

## 🔧 Configuración Paso a Paso

### 1. Configurar Servidor (PHP, MySQL, Nginx)

```bash
source scripts/staging-config.sh
./scripts/setup-staging-server.sh
```

**Tiempo estimado**: 5-10 minutos

### 2. Configurar Base de Datos

```bash
source scripts/staging-config.sh
./scripts/setup-staging-database.sh
```

Te pedirá:
- Contraseña de MySQL root (en staging)
- Contraseña para el usuario `magicai_staging`

**Tiempo estimado**: 2 minutos

### 3. Clonar Código desde Producción

```bash
source scripts/staging-config.sh
./scripts/clone-code-to-staging.sh
```

**Tiempo estimado**: 5-10 minutos (depende del tamaño del código)

### 4. Clonar Base de Datos desde Producción

```bash
source scripts/staging-config.sh
./scripts/clone-production-db-to-staging.sh
```

Te pedirá:
- Contraseña MySQL root de producción
- Contraseña MySQL de staging

**Tiempo estimado**: 5-15 minutos (depende del tamaño de la BD)

### 5. Configurar Nginx

```bash
source scripts/staging-config.sh
# Ajusta el dominio si es necesario
export DOMAIN=staging.tausepro.com
./scripts/setup-staging-nginx.sh
```

**Tiempo estimado**: 2 minutos

### 6. (Opcional) Anonimizar Datos

```bash
source scripts/staging-config.sh
./scripts/anonymize-staging-db.sh
```

**Tiempo estimado**: 1 minuto

### 7. Configurar Aplicación Laravel

Conecta al servidor y configura:

```bash
# Conectar
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31

# En el servidor
cd /var/www/magicai-staging

# 1. Configurar .env
nano .env
```

**Configuración mínima del .env:**
```env
APP_NAME="MagicAI Staging"
APP_ENV=staging
APP_DEBUG=true
APP_URL=http://13.218.39.31  # O tu dominio cuando lo configures

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=[la contraseña que configuraste]

# Usar claves de API de prueba/staging
OPENAI_API_KEY=sk-test-...
```

```bash
# 2. Instalar dependencias
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Configurar Laravel
php artisan key:generate
php artisan migrate --force

# 4. Configurar permisos
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# 5. Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## ✅ Verificación

### Verificar que el sitio funciona:

```bash
# Desde tu máquina local
curl http://13.218.39.31
```

Deberías ver la página de Laravel o tu aplicación.

### Verificar logs:

```bash
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
tail -f /var/www/magicai-staging/storage/logs/laravel.log
```

---

## 🚀 Deployment a Staging

Una vez configurado, para desplegar cambios:

```bash
source scripts/staging-config.sh
./scripts/deploy-to-staging.sh
```

---

## 🔐 Seguridad

⚠️ **IMPORTANTE**: 

1. La instancia es `t2.micro` (gratis pero limitada)
2. Asegúrate de que el Security Group solo permita acceso necesario
3. Considera usar IP whitelist para SSH
4. Anonimiza datos antes de usar staging con datos reales

---

## 📝 Configurar DNS (Opcional)

Si quieres usar un dominio:

1. Ve a tu proveedor de DNS
2. Crea registro A: `staging.tausepro.com` → `13.218.39.31`
3. Espera propagación (5-30 minutos)
4. Actualiza `APP_URL` en `.env` con el dominio

---

## 🆘 Troubleshooting

### No puedo conectar por SSH

```bash
# Verificar permisos de la key
chmod 400 staging-tausepro-key.pem

# Verificar que la instancia está corriendo en AWS Console
# Verificar Security Group permite puerto 22
```

### Error "Permission denied"

```bash
# Verificar que la key es correcta
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31

# Si no funciona, verifica en AWS Console que la key es correcta
```

### Nginx muestra 502 Bad Gateway

```bash
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
sudo systemctl status php8.2-fpm
sudo tail -f /var/log/nginx/staging-error.log
```

### Aplicación muestra errores

```bash
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
cd /var/www/magicai-staging
tail -f storage/logs/laravel.log
php artisan config:clear
php artisan cache:clear
```

---

## 📞 Próximos Pasos

1. ✅ Ejecuta `source scripts/staging-config.sh`
2. ✅ Ejecuta `./scripts/setup-staging-complete.sh`
3. ✅ Sigue las instrucciones del script
4. ✅ Configura el `.env` manualmente
5. ✅ Prueba el sitio

---

**¿Listo?** Empieza con:

```bash
source scripts/staging-config.sh
./scripts/setup-staging-complete.sh
```

