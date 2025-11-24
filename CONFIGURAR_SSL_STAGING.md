# 🔒 Configurar SSL para test.tause.pro

## 🚀 Método Rápido (Recomendado)

### Opción 1: Desde el Servidor (Más Rápido)

Conecta vía SSH y ejecuta:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180

# Instalar Certbot
sudo apt-get update
sudo apt-get install -y certbot python3-certbot-nginx

# Obtener certificado SSL
sudo certbot --nginx -d test.tause.pro --non-interactive --agree-tos --email admin@tause.pro --redirect
```

**Esto automáticamente:**
- ✅ Obtiene certificado SSL de Let's Encrypt
- ✅ Configura Nginx para HTTPS
- ✅ Redirige HTTP → HTTPS
- ✅ Configura renovación automática

---

### Opción 2: Usar Script

```bash
# Copiar script al servidor
scp -i staging-tausepro-key.pem scripts/configurar-ssl-staging.sh ubuntu@3.220.198.180:~/

# Conectar y ejecutar
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180
chmod +x configurar-ssl-staging.sh
./configurar-ssl-staging.sh
```

---

## ✅ Verificar SSL

Después de configurar:

1. **Probar HTTPS:**
   ```bash
   curl -I https://test.tause.pro
   ```

2. **Abrir en navegador:**
   - https://test.tause.pro
   - Debe mostrar candado verde 🔒

3. **Verificar renovación automática:**
   ```bash
   sudo certbot renew --dry-run
   ```

---

## 🔧 Si Hay Problemas

### Error: "Could not find a virtual host"

Asegúrate que Nginx tiene configuración para `test.tause.pro`:

```bash
sudo cat /etc/nginx/sites-enabled/test.tause.pro
```

Debe tener `server_name test.tause.pro;`

### Error: "Domain not pointing to this server"

Verifica DNS:
```bash
dig test.tause.pro +short
# Debe mostrar: 3.220.198.180
```

### Error: "Port 80 is not open"

Verifica Security Group tiene regla HTTP (80):
```bash
# Desde AWS Console o CLI
aws ec2 describe-security-groups --group-ids sg-0daf518dc8b65271d
```

---

## 📋 Configuración Manual (Si Certbot Falla)

Si Certbot no funciona automáticamente:

1. **Obtener certificado standalone:**
   ```bash
   sudo certbot certonly --standalone -d test.tause.pro --non-interactive --agree-tos --email admin@tause.pro
   ```

2. **Configurar Nginx manualmente:**
   ```bash
   sudo nano /etc/nginx/sites-enabled/test.tause.pro
   ```

   Agregar configuración SSL:
   ```nginx
   server {
       listen 80;
       server_name test.tause.pro;
       return 301 https://$server_name$request_uri;
   }

   server {
       listen 443 ssl http2;
       server_name test.tause.pro;
       root /var/www/magicai/public;
       index index.php index.html;

       ssl_certificate /etc/letsencrypt/live/test.tause.pro/fullchain.pem;
       ssl_certificate_key /etc/letsencrypt/live/test.tause.pro/privkey.pem;
       
       # SSL configuration
       ssl_protocols TLSv1.2 TLSv1.3;
       ssl_ciphers HIGH:!aNULL:!MD5;
       ssl_prefer_server_ciphers on;

       # ... resto de configuración ...
   }
   ```

3. **Recargar Nginx:**
   ```bash
   sudo nginx -t
   sudo systemctl reload nginx
   ```

---

## 🔄 Renovación Automática

Certbot configura renovación automática, pero puedes verificar:

```bash
# Ver cron job
sudo cat /etc/cron.d/certbot

# Probar renovación
sudo certbot renew --dry-run
```

---

## ✅ Checklist

- [ ] Certbot instalado
- [ ] Certificado SSL obtenido
- [ ] Nginx configurado para HTTPS
- [ ] HTTP redirige a HTTPS
- [ ] HTTPS funciona en navegador
- [ ] Renovación automática configurada

---

**Ejecuta el comando de Opción 1 arriba. Debería tomar menos de 2 minutos.**


