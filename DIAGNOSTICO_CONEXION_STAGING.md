# 🔍 Diagnóstico: Staging No Conecta

## ⚠️ Problema

No se puede conectar a staging (`13.218.39.31`) vía SSH.

---

## 🔍 Posibles Causas

### 1. Instancia Detenida o Terminada
La instancia puede haberse detenido o terminado.

**Verificar:**
```bash
aws ec2 describe-instances --filters "Name=ip-address,Values=13.218.39.31" --query 'Reservations[0].Instances[0].State.Name' --output text
```

**Si está detenida:**
```bash
# Obtener Instance ID
INSTANCE_ID=$(aws ec2 describe-instances --filters "Name=ip-address,Values=13.218.39.31" --query 'Reservations[0].Instances[0].InstanceId' --output text)

# Iniciar instancia
aws ec2 start-instances --instance-ids $INSTANCE_ID

# Esperar a que esté running
aws ec2 wait instance-running --instance-ids $INSTANCE_ID

# Obtener nueva IP (si no tiene Elastic IP)
aws ec2 describe-instances --instance-ids $INSTANCE_ID --query 'Reservations[0].Instances[0].PublicIpAddress' --output text
```

### 2. IP Cambió
Si la instancia no tiene Elastic IP, la IP pública puede cambiar al reiniciar.

**Verificar IP actual:**
```bash
aws ec2 describe-instances --filters "Name=tag:Name,Values=*staging*" --query 'Reservations[*].Instances[*].[InstanceId,State.Name,PublicIpAddress]' --output table
```

### 3. Security Group Bloquea SSH
El Security Group puede haber cambiado o no tener regla SSH.

**Verificar reglas SSH:**
```bash
# Obtener Security Group ID
SG_ID=$(aws ec2 describe-instances --filters "Name=ip-address,Values=13.218.39.31" --query 'Reservations[0].Instances[0].SecurityGroups[0].GroupId' --output text)

# Ver reglas
aws ec2 describe-security-groups --group-ids $SG_ID --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`]' --output table
```

**Agregar regla SSH si falta:**
```bash
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0
```

### 4. Problemas de Red/Firewall
Puede haber problemas de red o firewall bloqueando.

**Probar ping:**
```bash
ping -c 3 13.218.39.31
```

Si ping no funciona, puede ser:
- Instancia detenida
- Security Group bloquea ICMP
- Problemas de red

---

## 🚀 Solución Paso a Paso

### Paso 1: Verificar Estado de Instancia

```bash
aws ec2 describe-instances \
  --filters "Name=ip-address,Values=13.218.39.31" \
  --query 'Reservations[0].Instances[0].[InstanceId,State.Name,PublicIpAddress,SecurityGroups[0].GroupId]' \
  --output table
```

**Si State.Name es "stopped":**
- Iniciar instancia (ver arriba)
- Esperar 1-2 minutos
- Verificar nueva IP

**Si State.Name es "running":**
- Continuar con Paso 2

### Paso 2: Verificar Security Group

```bash
# Obtener SG ID
SG_ID=$(aws ec2 describe-instances --filters "Name=ip-address,Values=13.218.39.31" --query 'Reservations[0].Instances[0].SecurityGroups[0].GroupId' --output text)

# Ver reglas SSH
aws ec2 describe-security-groups --group-ids $SG_ID --query 'SecurityGroups[0].IpPermissions[?FromPort==`22`]' --output table
```

**Si no hay regla SSH:**
```bash
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0
```

### Paso 3: Probar Conexión

```bash
# Con tu IP actual
MY_IP=$(curl -4 -s ifconfig.me)
ssh -4 -i staging-tausepro-key.pem ubuntu@13.218.39.31 "echo 'Conexión exitosa'"
```

---

## 💡 Alternativa: Usar AWS Systems Manager

Si Session Manager está habilitado:

```bash
# Obtener Instance ID
INSTANCE_ID=$(aws ec2 describe-instances --filters "Name=ip-address,Values=13.218.39.31" --query 'Reservations[0].Instances[0].InstanceId' --output text)

# Conectar vía Session Manager
aws ssm start-session --target $INSTANCE_ID
```

---

## 📋 Checklist de Diagnóstico

- [ ] Verificar estado de instancia (running/stopped/terminated)
- [ ] Verificar IP pública actual
- [ ] Verificar Security Group tiene regla SSH
- [ ] Probar ping a la IP
- [ ] Probar conexión SSH con timeout corto
- [ ] Verificar que la clave SSH es correcta
- [ ] Verificar usuario correcto (ubuntu)

---

**Ejecuta estos comandos de diagnóstico y comparte los resultados.**



