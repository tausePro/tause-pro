# 🔧 Arreglar Base de Datos Staging

## ⚠️ Problema

- ✅ Sitio carga
- ✅ Wizard de instalación aparece
- ❌ Error al hacer clic en "Iniciar": **Table 'magicai_staging.referers' doesn't exist**

**Causa**: La base de datos existe pero no tiene las tablas (migraciones no ejecutadas).

---

## 🚀 Solución: Ejecutar Migraciones

### Paso 1: Conectar al Servidor

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180
```

### Paso 2: Verificar Base de Datos Existe

```bash
sudo mysql -e "SHOW DATABASES LIKE 'magicai_staging';"
```

**Debe mostrar:**
```
+----------------------+
| Database (magicai_staging) |
+----------------------+
| magicai_staging      |
+----------------------+
```

### Paso 3: Verificar Migraciones Pendientes

```bash
cd /var/www/magicai
php artisan migrate:status
```

Esto mostrará qué migraciones están pendientes.

### Paso 4: Ejecutar Migraciones

```bash
# Ejecutar todas las migraciones
php artisan migrate --force

# Si hay problemas, ejecutar con más detalle:
php artisan migrate --force -v
```

### Paso 5: Verificar Tablas Creadas

```bash
sudo mysql magicai_staging -e "SHOW TABLES;" | head -20
```

**Debe mostrar tablas como:**
- `users`
- `referers`
- `migrations`
- etc.

---

## 🔍 Si Migraciones Fallan

### Verificar .env Está Correcto

```bash
cd /var/www/magicai
cat .env | grep DB_
```

**Debe mostrar:**
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024
```

### Verificar Usuario de BD Tiene Permisos

```bash
sudo mysql << EOF
SHOW GRANTS FOR 'magicai_staging'@'localhost';
EOF
```

**Debe mostrar permisos en `magicai_staging.*`**

Si no tiene permisos:
```bash
sudo mysql << EOF
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Limpiar Cache

```bash
cd /var/www/magicai
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

---

## 📋 Comandos Completos (Copia y Pega)

```bash
# Conectar
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180

# Ir al directorio
cd /var/www/magicai

# Verificar .env
cat .env | grep DB_

# Verificar BD existe
sudo mysql -e "SHOW DATABASES LIKE 'magicai_staging';"

# Verificar usuario tiene permisos
sudo mysql -e "SHOW GRANTS FOR 'magicai_staging'@'localhost';"

# Si falta, crear usuario y permisos
sudo mysql << EOF
CREATE DATABASE IF NOT EXISTS magicai_staging;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF

# Limpiar cache
php artisan config:clear
php artisan cache:clear

# Ejecutar migraciones
php artisan migrate --force

# Verificar tablas creadas
sudo mysql magicai_staging -e "SHOW TABLES;"
```

---

## ✅ Después de Ejecutar Migraciones

1. **Refrescar navegador** en `http://test.tause.pro/install`
2. **Hacer clic en "Iniciar"** de nuevo
3. **Debería funcionar** ahora ✅

---

## 🔍 Verificar Tabla Específica

Si quieres verificar que la tabla `referers` existe:

```bash
sudo mysql magicai_staging -e "DESCRIBE referers;"
```

**Debe mostrar la estructura de la tabla.**

---

**Ejecuta las migraciones y el wizard debería funcionar.**


