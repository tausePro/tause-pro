# 🚀 Guía para Completar Staging

## 📊 Estado Actual

Según `ESTADO_STAGING.md`, staging está ~80% completo. Faltan:

1. ❌ Base de datos vacía (necesita clonar desde producción)
2. ⚠️ Permisos de storage (fácil de arreglar)
3. ⚠️ Verificar acceso MySQL

---

## 🎯 Opción 1: Script Automático (RECOMENDADO)

### Paso 1: Configurar Variables

```bash
cd /Users/tause/Documents/proyectos/tausepro9.4

# Verificar que tienes la clave
ls -la staging-tausepro-key.pem

# Si no existe, créala desde COMENZAR_STAGING.md
```

### Paso 2: Ejecutar Script de Completar

```bash
# El script resuelve automáticamente:
# - Permisos de storage
# - Verificación de BD
# - Migraciones
# - Configuración .env
# - Optimización Laravel

./scripts/completar-staging.sh
```

El script te guiará paso a paso y te indicará si necesitas clonar la BD.

---

## 🎯 Opción 2: Manual Paso a Paso

### 1. Corregir Permisos (2 min)

```bash
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
cd /var/www/magicai-staging
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
exit
```

### 2. Clonar Base de Datos desde Producción (10-15 min)

**Opción A: Directo desde producción**

```bash
# Desde tu máquina local
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 \
  "sudo mysqldump -u debian-sys-maint -pXBM09DTCfSTl7a6S tause_pro" | \
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31 \
  "sudo mysql -u debian-sys-maint -pXcSG6LVUmLzS5zEW magicai_staging"
```

**Opción B: Con archivo intermedio**

```bash
# 1. Exportar desde producción
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 \
  "sudo mysqldump -u debian-sys-maint -pXBM09DTCfSTl7a6S tause_pro" > /tmp/staging_dump.sql

# 2. Copiar a staging
scp -i staging-tausepro-key.pem /tmp/staging_dump.sql ubuntu@13.218.39.31:/tmp/

# 3. Importar en staging
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31 \
  "sudo mysql -u debian-sys-maint -pXcSG6LVUmLzS5zEW magicai_staging < /tmp/staging_dump.sql"

# 4. Limpiar
rm /tmp/staging_dump.sql
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31 "rm /tmp/staging_dump.sql"
```

### 3. Ejecutar Migraciones (2 min)

```bash
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
cd /var/www/magicai-staging
php artisan migrate --force
php artisan config:cache
php artisan route:cache
exit
```

### 4. Verificar que Funciona (2 min)

```bash
# Verificar HTTP
curl http://13.218.39.31

# O abrir en navegador
# http://13.218.39.31
```

---

## ✅ Checklist Final

Después de completar, verifica:

- [ ] Permisos de storage corregidos
- [ ] Base de datos clonada (o vacía pero funcional)
- [ ] Migraciones ejecutadas
- [ ] Sitio responde en http://13.218.39.31
- [ ] Puedes hacer login
- [ ] Funcionalidades principales funcionan

---

## 🚀 Después de Completar Staging

Una vez staging esté funcionando:

### 1. Desplegar Cambios de Fase 1

```bash
# Configurar variables
export STAGING_HOST=13.218.39.31
export KEY_FILE=staging-tausepro-key.pem

# Desplegar
./scripts/deploy-to-staging.sh
```

### 2. Validar Fase 1 en Staging

1. Activar Sales Agent en un chatbot
2. Verificar que se crea External Agent automáticamente
3. Probar chatbot embebido con mensajes reales
4. Verificar logs: `tail -f storage/logs/laravel.log | grep Agent`

### 3. Si Todo Funciona → Producción

Solo después de validar en staging, desplegar a producción.

---

## 🆘 Problemas Comunes

### No puedo conectar por SSH

```bash
# Verificar permisos de clave
chmod 400 staging-tausepro-key.pem

# Verificar IP
ping 13.218.39.31

# Verificar Security Group en AWS Console
```

### Base de datos no se clona

- Verifica credenciales MySQL
- Verifica que producción permite conexión SSH
- Usa opción B (archivo intermedio) si hay problemas de red

### Sitio muestra error 500

```bash
# Ver logs
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
tail -f /var/www/magicai-staging/storage/logs/laravel.log

# Verificar permisos
sudo chown -R www-data:www-data /var/www/magicai-staging/storage
```

---

## 📝 Notas Importantes

1. **Base de datos**: Si no puedes clonar desde producción, puedes trabajar con BD vacía y crear datos de prueba
2. **Variables de entorno**: Asegúrate de configurar API keys de prueba en `.env` de staging
3. **DNS**: El dominio `test.tause.pro` necesita configurarse en tu proveedor DNS

---

**¿Listo?** Ejecuta `./scripts/completar-staging.sh` para empezar.



