# ✅ Instalación Correcta de MagicAI según Documentación

Según la [documentación oficial](https://docs.magicproject.ai/installation/), MagicAI tiene dos formas de instalación:

## 🚀 Instalación Automática (Recomendada)

MagicAI tiene un wizard de instalación en `/install` que configura todo automáticamente.

### Pasos:

1. **Asegurar que el código está subido** ✅ (ya hecho)
2. **Configurar permisos** ✅ (ya hecho)
3. **Crear .env básico** ✅ (ya hecho)
4. **Ir a**: `http://test.tause.pro/install`
5. **Seguir el wizard** que:
   - Verifica requisitos del servidor
   - Configura información del sitio
   - Configura base de datos
   - Instala automáticamente

**El wizard crea la BD y ejecuta las migraciones automáticamente.**

---

## 🔧 Instalación Manual (Si el wizard falla)

Si el wizard no funciona, seguir estos pasos:

### 1. Importar SQL Inicial

MagicAI viene con un archivo `magicai.sql` que debe importarse primero.

```bash
# En el servidor
cd /var/www/magicai

# Buscar archivo SQL
find . -name "*.sql" -type f

# Importar a la BD
sudo mysql magicai_staging < magicai.sql
# O si está en otra ubicación:
sudo mysql magicai_staging < /ruta/a/magicai.sql
```

### 2. Configurar .env

```env
APP_NAME="MagicAI"
APP_URL=http://test.tause.pro
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024
```

### 3. Generar APP_KEY

```bash
php artisan key:generate --force
```

### 4. Limpiar Cache

```bash
php artisan config:clear
php artisan cache:clear
```

---

## ⚠️ Error Actual: Columna 'hidden' Faltante

El error indica que la tabla `plans` no tiene la columna `hidden`. Esto significa que:

1. **Las migraciones no se ejecutaron correctamente**, O
2. **Falta importar el SQL inicial** (`magicai.sql`)

---

## 🔍 Verificar Archivo SQL

```bash
# Buscar archivo SQL en el proyecto
find /var/www/magicai -name "*.sql" -type f

# Si existe, importarlo
sudo mysql magicai_staging < /ruta/al/magicai.sql
```

---

## ✅ Solución Rápida

**Opción 1: Usar el Wizard** (Más fácil)
1. Ir a `http://test.tause.pro/install`
2. Seguir los pasos del wizard
3. El wizard configura todo automáticamente

**Opción 2: Importar SQL Manualmente**
1. Buscar archivo `magicai.sql`
2. Importarlo a la BD
3. Verificar que las tablas tengan todas las columnas

---

**Según la documentación, el wizard es la forma recomendada y más fácil.**


