# 🔧 Arreglar MySQL en Staging

## Problema

MySQL en staging requiere credenciales específicas. No se puede acceder con `root` sin contraseña ni con `magicai_staging`.

## Solución

Usar el usuario `debian-sys-maint` que tiene acceso completo.

---

## Comandos para Ejecutar en Staging

Conecta a staging:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31
```

### 1. Obtener Credenciales de debian-sys-maint

```bash
sudo cat /etc/mysql/debian.cnf
```

Anota el `user` y `password`.

### 2. Crear Base de Datos y Usuario

```bash
# Obtener password de debian-sys-maint
DEBIAN_PASS=$(sudo grep password /etc/mysql/debian.cnf | head -1 | cut -d'=' -f2 | tr -d ' ')

# Conectar y crear
sudo mysql -u debian-sys-maint -p$DEBIAN_PASS << EOF
CREATE DATABASE IF NOT EXISTS magicai_staging CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
SHOW DATABASES;
EOF
```

### 3. Verificar Conexión

```bash
mysql -u magicai_staging -pstaging_password_2024 -e "SELECT 1"
```

### 4. Ejecutar Migraciones

```bash
cd /var/www/magicai-staging
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

---

## Alternativa: Usar Root con Contraseña

Si prefieres usar root:

```bash
sudo mysql_secure_installation
# Seguir las instrucciones para configurar root
```

Luego usar root para crear el usuario.

---

**¿Quieres que ejecute estos comandos por ti?**



