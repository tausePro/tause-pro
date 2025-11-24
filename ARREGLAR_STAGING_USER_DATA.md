# 🔧 Arreglar Staging con User Data

## ⚠️ Problema

- Session Manager no disponible (SSM Agent offline)
- SSH no funciona
- Necesitamos arreglar firewall y servicios automáticamente

---

## 🚀 Solución: Usar User Data Script

### Opción 1: Desde AWS Console (Recomendado)

1. Ve a: https://console.aws.amazon.com/ec2/
2. Click en "Instances"
3. Selecciona `i-06113402909fd6b57`
4. Click "Actions" → "Instance settings" → "Edit user data"
5. Copia y pega este script:

```bash
#!/bin/bash
set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Arreglando staging..."

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

echo "✅ Completado"
```

6. Guarda los cambios
7. **Reinicia la instancia** desde "Instance state" → "Reboot instance"
8. Espera 2-3 minutos
9. Prueba conectar: `ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31`

### Opción 2: Desde AWS CLI

```bash
# Crear archivo con user data
cat > /tmp/user-data.sh << 'EOF'
#!/bin/bash
set -e
exec > >(tee /var/log/user-data.log|logger -t user-data -s 2>/dev/console) 2>&1

echo "🔧 Arreglando staging..."

ufw --force disable || true
iptables -F || true
iptables -P INPUT ACCEPT || true

systemctl start ssh || true
systemctl enable ssh || true
systemctl start nginx || true
systemctl enable nginx || true
systemctl start php8.2-fpm || true
systemctl enable php8.2-fpm || true

chown -R www-data:www-data /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true
chmod -R 775 /var/www/magicai-staging/storage /var/www/magicai-staging/bootstrap/cache || true

ufw --force reset || true
ufw default deny incoming || true
ufw default allow outgoing || true
ufw allow 22/tcp || true
ufw allow 80/tcp || true
ufw allow 443/tcp || true
ufw --force enable || true

echo "✅ Completado"
EOF

# Aplicar user data
aws ec2 modify-instance-attribute \
  --instance-id i-06113402909fd6b57 \
  --user-data file:///tmp/user-data.sh

# Reiniciar
aws ec2 reboot-instances --instance-ids i-06113402909fd6b57
```

---

## 🔍 Verificar Logs del User Data

Después de reiniciar, puedes ver los logs:

**Desde AWS Console:**
1. EC2 → Instances → `i-06113402909fd6b57`
2. Tab "Monitoring" → "Get system log"
3. Busca líneas que empiezan con "user-data"

**O si logras conectar después:**
```bash
cat /var/log/user-data.log
```

---

## 💡 Alternativa: EC2 Instance Connect

Si User Data no funciona, intenta **EC2 Instance Connect**:

1. En la página de "Connect to instance"
2. Tab "EC2 Instance Connect"
3. Click "Connect"

Esto puede funcionar sin SSM Agent.

---

## ✅ Checklist

- [ ] Agregar User Data script desde AWS Console
- [ ] Reiniciar instancia
- [ ] Esperar 2-3 minutos
- [ ] Probar SSH: `ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31`
- [ ] Si no funciona, intentar EC2 Instance Connect
- [ ] Ver logs del sistema para verificar que User Data se ejecutó

---

**Agrega el User Data script desde AWS Console y reinicia la instancia. Esto debería arreglar el firewall y servicios automáticamente.**



