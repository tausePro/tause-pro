# 🔍 Diagnóstico Completo SSH No Funciona

## ❓ Preguntas Clave

1. **¿Qué NO funciona exactamente?**
   - [ ] SSH no conecta (timeout/connection refused)
   - [ ] HTTP no carga (504/timeout)
   - [ ] Ambos fallan

2. **¿La instancia está "Running"?**
   - Verifica en AWS Console que el estado sea "Running" (no "Stopped" o "Stopping")

3. **¿La IP cambió?**
   - Verifica la IP pública en AWS Console
   - Puede haber cambiado si no tienes Elastic IP

---

## 🔧 Verificaciones AWS Console

### 1. Estado de Instancia
- **Instance state:** Debe ser "Running" (verde)
- **Status checks:** Debe ser "2/2 checks passed"

### 2. Security Group
- **Inbound rules:**
  - SSH (22) desde `0.0.0.0/0` ✅
  - HTTP (80) desde `0.0.0.0/0` ✅
  - HTTPS (443) desde `0.0.0.0/0` ✅

### 3. User Data
- **Actions** → **Instance settings** → **View/Edit user data**
- Verifica que el contenido sea el nuevo (sin `ufw enable`)

---

## 🧪 Pruebas Locales

### Prueba SSH
```bash
# Con IPv4 forzado
ssh -4 -i staging-tausepro-key.pem -v ubuntu@44.211.83.213

# Con timeout corto para ver error rápido
ssh -4 -i staging-tausepro-key.pem -o ConnectTimeout=5 ubuntu@44.211.83.213
```

### Prueba HTTP
```bash
curl -v http://44.211.83.213
curl -v http://test.tause.pro
```

---

## 🔍 Verificar User Data Se Ejecutó

Si SSH funciona pero necesitas verificar:

```bash
# Ver logs del User Data
sudo cat /var/log/user-data.log

# Ver estado de servicios
sudo systemctl status ssh
sudo systemctl status nginx
sudo systemctl status php8.2-fpm

# Ver estado del firewall
sudo ufw status
sudo iptables -L -n
```

---

## 🚨 Problemas Comunes

### 1. User Data No Se Ejecutó
**Síntoma:** SSH funciona pero servicios no están corriendo

**Solución:**
- El User Data solo se ejecuta en el primer boot
- Si la instancia ya existía, el User Data NO se ejecuta de nuevo
- Necesitas crear una NUEVA instancia desde una AMI

### 2. Firewall Sigue Bloqueando
**Síntoma:** SSH timeout o connection refused

**Solución:**
```bash
# Si puedes conectar de alguna forma (Session Manager, etc):
sudo ufw disable
sudo iptables -F
sudo systemctl restart ssh
```

### 3. Security Group Incorrecto
**Síntoma:** SSH timeout desde tu IP pero funciona desde otros lugares

**Solución:**
- Verifica que Security Group tenga SSH (22) desde `0.0.0.0/0`
- Verifica que la instancia use el Security Group correcto

### 4. IP Cambió
**Síntoma:** SSH funciona pero con otra IP

**Solución:**
- Verifica IP pública en AWS Console
- Usa Elastic IP si necesitas IP fija

---

## 💡 Solución Definitiva: Nueva Instancia

Si el User Data no se ejecutó porque la instancia ya existía:

1. **Crear AMI** de la instancia actual (para preservar datos)
2. **Crear nueva instancia** desde la AMI
3. **Configurar User Data** ANTES de iniciar
4. **Iniciar instancia** - User Data se ejecutará automáticamente

---

## 📝 Información Necesaria

Para ayudarte mejor, necesito:

1. **Error exacto de SSH:**
   ```bash
   ssh -4 -v -i staging-tausepro-key.pem ubuntu@44.211.83.213
   ```
   (Copia el output completo)

2. **Estado en AWS Console:**
   - Instance state: ?
   - Status checks: ?
   - Public IP: ?

3. **Security Group:**
   - ¿Tiene SSH (22) desde 0.0.0.0/0?

4. **User Data:**
   - ¿Lo modificaste ANTES o DESPUÉS de iniciar?
   - ¿El contenido es el nuevo (sin `ufw enable`)?


