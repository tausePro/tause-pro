# 🚀 Crear Nueva Instancia Staging desde Producción

## ⚠️ Situación

- ❌ Instancia staging actual no funciona
- ❌ SSH no conecta
- ❌ EC2 Instance Connect falla
- ❌ HTTP 504 Gateway Timeout

**Solución**: Crear nueva instancia desde AMI de producción.

---

## 📋 Pasos Completos

### Paso 1: Crear AMI de Producción

1. **AWS Console** → **EC2** → **Instances**
2. **Busca la instancia de producción** (la que funciona)
3. **Selecciona** → **Actions** → **Image and templates** → **Create image**
4. **Configurar:**
   - **Image name**: `magicai-production-$(date +%Y%m%d)` (ej: `magicai-production-20241114`)
   - **Image description**: `AMI de producción para crear staging`
   - **No reboot**: Desmarcado (dejar que reinicie si es necesario)
5. **Create image**

⏳ **Esperar 5-10 minutos** hasta que la AMI esté disponible.

---

### Paso 2: Crear Nueva Instancia desde AMI

1. **EC2** → **AMIs** → **Owned by me**
2. **Busca** la AMI recién creada (`magicai-production-20241114`)
3. **Selecciona** → **Launch instance from AMI**

4. **Configurar instancia:**
   - **Name**: `staging-tausepro-new`
   - **Instance type**: `t3.small` o `t3.medium`
   - **Key pair**: `staging-tausepro-key` (seleccionar existente)
   - **Network settings**: 
     - **VPC**: Mismo que producción
     - **Subnet**: Cualquier subnet pública
     - **Auto-assign Public IP**: Enable
     - **Security group**: Seleccionar `sg-0daf518dc8b65271d` (el de staging actual)
   - **Configure storage**: Dejar como está (o reducir si quieres ahorrar)
   - **Advanced details**:
     - **User data**: **DEJAR VACÍO** (no agregar nada)
   - **Launch instance**

---

### Paso 3: Asociar Elastic IP

1. **EC2** → **Elastic IPs**
2. **Selecciona** `3.220.198.180`
3. **Actions** → **Disassociate Elastic IP address** (de instancia vieja)
4. **Actions** → **Associate Elastic IP address**
5. **Instance**: Selecciona la nueva instancia `staging-tausepro-new`
6. **Private IP**: Auto-assign
7. **Associate**

---

### Paso 4: Esperar Instancia Running

1. **EC2** → **Instances**
2. Esperar hasta que estado sea **"Running"** y **Status checks: 2/2 checks passed**
3. ⏳ **Tiempo**: 2-3 minutos

---

### Paso 5: Verificar SSH Funciona

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180
```

**Debería funcionar ahora** ✅

---

### Paso 6: Configurar Staging

Una vez conectado:

```bash
# 1. Cambiar .env
sudo nano /var/www/magicai/.env
```

**Cambiar:**
```env
APP_ENV=staging
APP_URL=http://test.tause.pro
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=magicai_staging
DB_USERNAME=magicai_staging
DB_PASSWORD=staging_password_2024
```

```bash
# 2. Crear BD staging
sudo mysql << EOF
CREATE DATABASE IF NOT EXISTS magicai_staging;
CREATE USER IF NOT EXISTS 'magicai_staging'@'localhost' IDENTIFIED BY 'staging_password_2024';
GRANT ALL PRIVILEGES ON magicai_staging.* TO 'magicai_staging'@'localhost';
FLUSH PRIVILEGES;
EOF

# 3. Ejecutar migraciones
cd /var/www/magicai
php artisan migrate --force

# 4. Limpiar cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Verificar servicios
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# 6. Verificar HTTP funciona
curl -I http://localhost
```

---

### Paso 7: Configurar SSL (Opcional)

```bash
# Instalar Certbot
sudo apt-get update
sudo apt-get install -y certbot python3-certbot-nginx

# Obtener certificado
sudo certbot --nginx -d test.tause.pro --non-interactive --agree-tos --email admin@tause.pro --redirect
```

---

## ✅ Checklist Final

- [ ] AMI de producción creada
- [ ] Nueva instancia creada desde AMI
- [ ] Elastic IP asociada (`3.220.198.180`)
- [ ] SSH funciona
- [ ] .env configurado para staging
- [ ] BD staging creada
- [ ] Migraciones ejecutadas
- [ ] HTTP funciona
- [ ] SSL configurado (opcional)
- [ ] Instancia vieja eliminada (opcional, después de verificar que nueva funciona)

---

## 🗑️ Eliminar Instancia Vieja (Después de Verificar)

Una vez que la nueva instancia funciona:

1. **EC2** → **Instances**
2. **Selecciona** `i-0bbe91c13a1343538` (instancia vieja)
3. **Instance state** → **Stop instance**
4. Esperar "Stopped"
5. **Instance state** → **Terminate instance**

---

## 💡 Ventajas de Este Método

- ✅ Sabemos que producción funciona
- ✅ Tiene toda la configuración correcta
- ✅ Solo necesitamos cambiar .env y BD
- ✅ Más rápido que seguir debuggeando
- ✅ SSH debería funcionar desde el inicio

---

**Este es el método más confiable. Empieza creando la AMI de producción.**
