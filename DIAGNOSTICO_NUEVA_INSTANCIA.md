# 🔍 Diagnóstico: Nueva Instancia Staging

## 📊 Información de la Instancia

- **IP**: `44.211.83.213`
- **Key**: `staging-tausepro-key.pem`
- **Estado**: Verificando...

---

## 🔍 Problemas Detectados

### 1. Host Key Verification Failed

Esto significa que SSH está rechazando la conexión por verificación de host key.

**Solución:**
```bash
# Limpiar host key conocido
ssh-keygen -R 44.211.83.213

# Conectar con opciones que ignoran verificación
ssh -4 -i staging-tausepro-key.pem \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile=/dev/null \
  ubuntu@44.211.83.213
```

### 2. Posibles Causas

1. **Security Group no tiene regla SSH**
2. **Firewall interno bloqueando** (ufw/iptables)
3. **Servicio SSH no iniciado**
4. **User Data no se ejecutó correctamente**
5. **Instancia aún iniciando**

---

## 🚀 Solución Paso a Paso

### Paso 1: Verificar Estado de Instancia

```bash
aws ec2 describe-instances \
  --filters "Name=ip-address,Values=44.211.83.213" \
  --query 'Reservations[0].Instances[0].[InstanceId,State.Name]' \
  --output table
```

**Si estado es "pending"**: Espera 2-3 minutos más

### Paso 2: Verificar Security Group

```bash
# Obtener Security Group ID
SG_ID=$(aws ec2 describe-instances \
  --filters "Name=ip-address,Values=44.211.83.213" \
  --query 'Reservations[0].Instances[0].SecurityGroups[0].GroupId' \
  --output text)

# Ver reglas SSH
aws ec2 describe-security-groups \
  --group-ids $SG_ID \
  --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`]' \
  --output table
```

**Si no hay regla SSH**, agregarla:

```bash
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0
```

### Paso 3: Intentar Conectar

```bash
# Limpiar host key
ssh-keygen -R 44.211.83.213

# Conectar
ssh -4 -i staging-tausepro-key.pem \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile=/dev/null \
  ubuntu@44.211.83.213
```

### Paso 4: Si No Conecta, Verificar Logs

**Desde AWS Console:**
1. EC2 → Instances → Selecciona instancia con IP `44.211.83.213`
2. Tab "Monitoring" → "Get system log"
3. Busca errores relacionados con:
   - SSH
   - Firewall (ufw/iptables)
   - User Data

---

## 💡 Si User Data No Se Ejecutó

Si la instancia está corriendo pero User Data no se ejecutó:

1. **Conectar vía EC2 Instance Connect** (si está disponible)
2. **Ejecutar comandos manualmente**:

```bash
# Deshabilitar firewall
sudo ufw disable
sudo iptables -F
sudo iptables -P INPUT ACCEPT

# Iniciar servicios
sudo systemctl start ssh
sudo systemctl enable ssh
sudo systemctl start nginx
sudo systemctl enable nginx
sudo systemctl start php8.2-fpm
sudo systemctl enable php8.2-fpm

# Configurar firewall
sudo ufw allow 22/tcp
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

---

## ✅ Checklist de Diagnóstico

- [ ] Instancia está en estado "running"
- [ ] Security Group tiene regla SSH (puerto 22)
- [ ] Security Group tiene regla HTTP (puerto 80)
- [ ] Ping responde
- [ ] SSH conecta (después de limpiar host key)
- [ ] Servicios están corriendo (SSH, Nginx, PHP-FPM)
- [ ] User Data se ejecutó (verificar logs)

---

**Ejecuta estos pasos y comparte los resultados para diagnosticar el problema específico.**



