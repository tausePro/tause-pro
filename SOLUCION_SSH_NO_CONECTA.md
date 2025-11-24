# 🔧 Solución: SSH No Conecta a Staging

## ⚠️ Problema

SSH no conecta a `44.211.83.213`

---

## 🔍 Diagnóstico Rápido

### Verificar Estado de Instancia

```bash
aws ec2 describe-instances \
  --filters "Name=ip-address,Values=44.211.83.213" \
  --query 'Reservations[0].Instances[0].[InstanceId,State.Name]' \
  --output table
```

**Si estado es "stopped"**: Iniciar instancia desde AWS Console

### Verificar Security Group

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

---

## 🚀 Soluciones

### Opción 1: Verificar en AWS Console

1. Ve a: https://console.aws.amazon.com/ec2/
2. Instances → Busca IP `44.211.83.213`
3. Verifica:
   - **Estado**: ¿Running o Stopped?
   - **Security Group**: ¿Tiene regla SSH (puerto 22)?

### Opción 2: Intentar con Más Opciones

```bash
ssh -4 -v -i staging-tausepro-key.pem \
  -o ConnectTimeout=10 \
  -o StrictHostKeyChecking=no \
  -o UserKnownHostsFile=/dev/null \
  ubuntu@44.211.83.213
```

El `-v` mostrará detalles de por qué falla.

### Opción 3: Verificar que la Clave es Correcta

```bash
ls -la staging-tausepro-key.pem
chmod 400 staging-tausepro-key.pem
```

---

## 💡 Si Nada Funciona

**Usar EC2 Instance Connect** desde AWS Console:

1. EC2 → Instances → Selecciona instancia
2. Click "Connect"
3. Tab "EC2 Instance Connect"
4. Click "Connect"

Esto te dará acceso sin SSH.

---

**Ejecuta el diagnóstico primero para ver qué está pasando.**



