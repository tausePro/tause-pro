# 🔧 Conectar SSH con Datos Correctos

## 📋 Datos de AWS Console

- **Clave**: `stagging_tp.pem`
- **Usuario**: `root`
- **DNS**: `ec2-3-220-198-180.compute-1.amazonaws.com`
- **IP**: `3.220.198.180`

---

## 🔍 Problema

La clave `stagging_tp.pem` no está en el directorio local. Tienes:
- `staging-tausepro-key.pem` ✅
- `magicai-tause-key.pem`
- `evolution-key.pem`

---

## 🚀 Soluciones

### Opción 1: Descargar Clave desde AWS Console

1. **EC2** → **Key Pairs**
2. Busca `stagging_tp`
3. **Download** (si está disponible)
4. Guardar en: `/Users/tause/Documents/proyectos/tausepro9.4/stagging_tp.pem`
5. Dar permisos:
   ```bash
   chmod 400 stagging_tp.pem
   ```

### Opción 2: Usar Clave Existente con Usuario Root

Si `staging-tausepro-key.pem` es la misma clave pero con otro nombre:

```bash
# Probar con usuario root
ssh -4 -i staging-tausepro-key.pem root@ec2-3-220-198-180.compute-1.amazonaws.com

# O con IP directa
ssh -4 -i staging-tausepro-key.pem root@3.220.198.180
```

### Opción 3: Usar EC2 Instance Connect (Más Fácil)

Como SSH tiene problemas, usa EC2 Instance Connect:

1. **AWS Console** → **EC2** → **Instances** → `i-0bbe91c13a1343538`
2. **Connect** → **EC2 Instance Connect** → **Connect**
3. Ejecutar comandos directamente

---

## 📋 Comandos para Ejecutar Migraciones

Una vez conectado (con cualquier método):

```bash
# Ir al directorio
cd /var/www/magicai

# Verificar BD y usuario
sudo mysql << 'EOF'
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

# Verificar tablas
sudo mysql magicai_staging -e "SHOW TABLES;" | head -20
```

---

## 💡 Recomendación

**Usa EC2 Instance Connect** - es más rápido y no requiere la clave local.

1. AWS Console → EC2 → Instances → `i-0bbe91c13a1343538`
2. Connect → EC2 Instance Connect → Connect
3. Ejecutar comandos arriba

---

**¿Tienes la clave `stagging_tp.pem` en otra ubicación o prefieres usar EC2 Instance Connect?**


