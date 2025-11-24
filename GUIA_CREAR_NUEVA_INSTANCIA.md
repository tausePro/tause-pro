# 🚀 Guía: Crear Nueva Instancia Staging

## 📋 Pasos Detallados

### Paso 1: Crear AMI de Instancia Actual (Backup)

1. Ve a: https://console.aws.amazon.com/ec2/
2. EC2 → Instances → Selecciona `i-06113402909fd6b57`
3. **Actions** → **Image and templates** → **Create image**
4. Configuración:
   - **Image name**: `staging-magicai-backup-20241112`
   - **Description**: `Backup antes de crear nueva instancia`
   - Dejar todo lo demás por defecto
5. Click **"Create image"**
6. ⏳ Espera 5-10 minutos a que termine

### Paso 2: Obtener Detalles de Instancia Actual

Antes de crear la nueva, anota estos detalles de `i-06113402909fd6b57`:

- **Instance type**: (ver en la instancia)
- **VPC**: (ver en Networking)
- **Subnet**: (ver en Networking)
- **Security Group**: `sg-0933986b1aa1f35eb`
- **Key pair**: `staging-tausepro-key` (o el que uses)
- **Storage size**: (ver en Storage)

### Paso 3: Crear Nueva Instancia

1. EC2 → **Launch Instance**

2. **Name**: `staging-tausepro-new`

3. **AMI**: 
   - Click "Browse more AMIs"
   - Tab "My AMIs"
   - Selecciona `staging-magicai-backup-20241112` (el que acabas de crear)

4. **Instance type**: Mismo que la actual (probablemente `t3.medium` o similar)

5. **Key pair**: `staging-tausepro-key` (o el que uses)

6. **Network settings**:
   - **VPC**: Misma que la actual
   - **Subnet**: Misma que la actual
   - **Auto-assign Public IP**: **Enable**
   - **Security Group**: Selecciona `sg-0933986b1aa1f35eb` (el mismo)

7. **Configure storage**: Mismo tamaño que la actual

8. **Advanced details** → **Expandir**

9. **User data** → Copiar y pegar esto:

```bash
#!/bin/bash
set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Configurando staging..."

# Deshabilitar firewall
ufw --force disable || true
iptables -F || true
iptables -P INPUT ACCEPT || true
iptables -P FORWARD ACCEPT || true
iptables -P OUTPUT ACCEPT || true

# Iniciar servicios
systemctl start ssh || true
systemctl enable ssh || true
systemctl start nginx || true
systemctl enable nginx || true
systemctl start php8.2-fpm || true
systemctl enable php8.2-fpm || true

# Permisos
chown -R www-data:www-data /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true
chmod -R 775 /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true

# Configurar firewall correctamente
ufw --force reset || true
ufw default deny incoming || true
ufw default allow outgoing || true
ufw allow 22/tcp || true
ufw allow 80/tcp || true
ufw allow 443/tcp || true
ufw --force enable || true

# Verificar servicios
systemctl is-active --quiet ssh && echo "✅ SSH activo" || echo "❌ SSH inactivo"
systemctl is-active --quiet nginx && echo "✅ Nginx activo" || echo "❌ Nginx inactivo"
systemctl is-active --quiet php8.2-fpm && echo "✅ PHP-FPM activo" || echo "❌ PHP-FPM inactivo"

echo "✅ Configuración completada!"
```

10. Click **"Launch instance"**

11. ⏳ Espera 2-3 minutos a que inicie

12. **Anota la nueva IP pública** de la instancia

### Paso 4: Verificar Nueva Instancia

```bash
# Probar SSH (debería funcionar ahora)
ssh -4 -i staging-tausepro-key.pem ubuntu@NUEVA_IP

# Si funciona, verificar servicios
sudo systemctl status ssh
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# Verificar firewall
sudo ufw status verbose
```

### Paso 5: Configurar Nginx para test.tause.pro

Una vez conectado a la nueva instancia:

```bash
# Crear configuración de Nginx
sudo nano /etc/nginx/sites-available/test.tause.pro
```

Pega esto:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name test.tause.pro;
    root /var/www/magicai-staging/public;
    index index.php index.html;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

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
}
```

Guarda: `Ctrl+O`, `Enter`, `Ctrl+X`

```bash
# Activar sitio
sudo ln -s /etc/nginx/sites-available/test.tause.pro /etc/nginx/sites-enabled/

# Verificar configuración
sudo nginx -t

# Recargar Nginx
sudo systemctl reload nginx

# Probar localmente
curl http://localhost
```

### Paso 6: Actualizar DNS

1. Ve a tu proveedor de DNS (donde configuraste `test.tause.pro`)
2. Cambiar registro A:
   - **De**: `13.218.39.31`
   - **A**: `NUEVA_IP` (la de la nueva instancia)
3. ⏳ Esperar propagación (5-30 minutos)

### Paso 7: Verificar Todo Funciona

```bash
# Probar por IP
curl http://NUEVA_IP

# Probar por dominio (después de propagación DNS)
curl http://test.tause.pro
```

### Paso 8: Desplegar Cambios de Fase 1

Una vez que todo funciona:

```bash
# Conectar a nueva instancia
ssh -4 -i staging-tausepro-key.pem ubuntu@NUEVA_IP

# Copiar archivos de Fase 1 (desde tu máquina local)
scp -4 -i staging-tausepro-key.pem \
  app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php \
  app/Extensions/Chatbot/System/Services/AgentIntelligenceService.php \
  app/Extensions/Chatbot/System/Models/ChatbotAgent.php \
  ubuntu@NUEVA_IP:/tmp/

# En la instancia, mover archivos
sudo mv /tmp/*.php /var/www/magicai-staging/app/Extensions/Chatbot/System/Services/
# (etc...)
```

O usar el script que ya creamos:

```bash
# Desde tu máquina local
export STAGING_HOST=NUEVA_IP
./scripts/deploy-fase1-to-staging.sh
```

### Paso 9: Terminar Instancia Vieja (Opcional)

Una vez que todo funciona en la nueva instancia:

1. EC2 → Instances → `i-06113402909fd6b57`
2. **Instance state** → **Terminate instance**
3. Confirmar

---

## ✅ Checklist

- [ ] Crear AMI de instancia actual
- [ ] Anotar detalles de instancia actual
- [ ] Crear nueva instancia con User Data
- [ ] Anotar nueva IP
- [ ] Probar SSH a nueva instancia
- [ ] Configurar Nginx para test.tause.pro
- [ ] Actualizar DNS
- [ ] Verificar que sitio funciona
- [ ] Desplegar cambios de Fase 1
- [ ] Terminar instancia vieja (opcional)

---

**Sigue estos pasos y tendrás staging funcionando correctamente desde el inicio.**



