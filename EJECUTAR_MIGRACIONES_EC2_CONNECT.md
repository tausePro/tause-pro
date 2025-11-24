# 🔧 Ejecutar Migraciones usando EC2 Instance Connect

## ⚠️ Problema

- ✅ Sitio carga
- ❌ Error: **Table 'magicai_staging.referers' doesn't exist**
- ❌ SSH no conecta

**Solución**: Usar EC2 Instance Connect para ejecutar migraciones.

---

## 🚀 Pasos

### Paso 1: Conectar vía EC2 Instance Connect

1. **AWS Console** → **EC2** → **Instances** → `i-0bbe91c13a1343538`
2. **Connect** → Tab **"EC2 Instance Connect"**
3. **Connect**

---

### Paso 2: Ejecutar Comandos (Copia y Pega Todo)

Una vez conectado, ejecuta estos comandos **uno por uno**:

```bash
# 1. Ir al directorio de la aplicación
cd /var/www/magicai

# 2. Verificar .env tiene BD correcta
sudo grep DB_ .env | head -6

# 3. Verificar BD existe
sudo mysql -e "SHOW DATABASES LIKE 'magicai_staging';"

# 4. Verificar usuario tiene permisos
sudo mysql -e "SHOW GRANTS FOR 'magicai_staging'@'localhost';" 2>&1

# 5. Si usuario no existe o no tiene permisos, crearlo
sudo mysql << 'EOF'
CREATE DATABASE IF NOT EXISTS magicai_staging;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF

# 6. Limpiar cache
php artisan config:clear
php artisan cache:clear

# 7. Ver estado de migraciones
php artisan migrate:status

# 8. Ejecutar migraciones
php artisan migrate --force

# 9. Verificar tablas creadas
sudo mysql magicai_staging -e "SHOW TABLES;" | head -20

# 10. Verificar tabla referers existe
sudo mysql magicai_staging -e "DESCRIBE referers;" 2>&1 | head -10
```

---

## ✅ Después de Ejecutar

1. **Cerrar EC2 Instance Connect**
2. **Refrescar navegador** en `http://test.tause.pro/install`
3. **Hacer clic en "Iniciar"** de nuevo
4. **Debería funcionar** ahora ✅

---

## 🔍 Si Hay Errores

### Error: "Access denied for user"

El usuario de BD no tiene permisos. Ejecuta:

```bash
sudo mysql << 'EOF'
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Error: "Database doesn't exist"

Crear BD:

```bash
sudo mysql << 'EOF'
CREATE DATABASE magicai_staging;
CREATE USER 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF
```

### Error: ".env no tiene DB_DATABASE correcto"

Editar .env:

```bash
sudo nano /var/www/magicai/.env
```

Buscar y cambiar:
```env
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024
```

Guardar (Ctrl+O, Enter, Ctrl+X) y luego:
```bash
php artisan config:clear
php artisan migrate --force
```

---

## 📋 Checklist

- [ ] Conectar vía EC2 Instance Connect
- [ ] Verificar BD existe
- [ ] Verificar usuario tiene permisos
- [ ] Ejecutar migraciones
- [ ] Verificar tablas creadas
- [ ] Refrescar navegador y probar wizard

---

**Usa EC2 Instance Connect y ejecuta los comandos paso a paso.**


