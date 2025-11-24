# 🚀 Solución Alternativa: Arreglar Staging Sin User Data

## ⚠️ Problema

- No puedes editar User Data sin detener la instancia
- Session Manager no está disponible
- SSH no funciona

---

## 🎯 Solución: Crear Nueva Instancia con Configuración Correcta

### Opción 1: Crear AMI y Nueva Instancia (Recomendado)

1. **Crear AMI de la instancia actual:**
   - EC2 → Instances → `i-06113402909fd6b57`
   - Actions → Image and templates → Create image
   - Nombre: `staging-magicai-backup-$(date +%Y%m%d)`
   - Crear

2. **Crear nueva instancia con User Data:**
   - EC2 → Launch Instance
   - Selecciona el AMI que acabas de crear
   - Configura igual que la actual
   - En "Advanced details" → "User data", pega el script de `scripts/fix-staging-user-data.sh`
   - Launch

3. **Actualizar DNS:**
   - Cambiar `test.tause.pro` para apuntar a la nueva IP

### Opción 2: Intentar EC2 Instance Connect

1. EC2 → Instances → `i-06113402909fd6b57`
2. Click "Connect"
3. Tab "EC2 Instance Connect"
4. Click "Connect"

Si funciona, ejecuta estos comandos:

```bash
# Deshabilitar firewall
sudo ufw disable

# Iniciar servicios
sudo systemctl start ssh
sudo systemctl start nginx
sudo systemctl start php8.2-fpm

# Configurar firewall correctamente
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable

# Verificar
sudo systemctl status ssh
sudo systemctl status nginx
```

### Opción 3: Usar AWS Systems Manager Run Command

Si tienes permisos, puedes ejecutar comandos remotos:

```bash
aws ssm send-command \
  --instance-ids i-06113402909fd6b57 \
  --document-name "AWS-RunShellScript" \
  --parameters 'commands=["ufw disable","systemctl start ssh","systemctl start nginx"]'
```

---

## 💡 Solución Más Rápida: Crear Script de Inicio

Si puedes crear un script que se ejecute automáticamente:

1. **Crear script en `/etc/rc.local`** (si existe)
2. **O crear servicio systemd** que se ejecute al inicio
3. **O usar cron con @reboot**

Pero necesitas acceso primero...

---

## 🎯 Recomendación Final

**Opción más práctica:**

1. **Crear nueva instancia** con User Data correcto
2. **Clonar la BD** a la nueva instancia
3. **Actualizar DNS** para apuntar a la nueva IP
4. **Terminar la instancia vieja** cuando todo funcione

Esto es más rápido que intentar arreglar la instancia actual sin acceso.

---

## 📋 Checklist

- [ ] Intentar EC2 Instance Connect primero
- [ ] Si no funciona, crear AMI de instancia actual
- [ ] Crear nueva instancia con User Data correcto
- [ ] Clonar BD a nueva instancia
- [ ] Configurar Nginx en nueva instancia
- [ ] Actualizar DNS
- [ ] Verificar que funciona
- [ ] Terminar instancia vieja

---

**¿Quieres que te guíe para crear una nueva instancia con la configuración correcta? Es más rápido que intentar arreglar la actual sin acceso.**



