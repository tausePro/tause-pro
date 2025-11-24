# 🔐 Cómo Agregar Reglas al Security Group

## 📊 Situación Actual

Según la imagen que compartiste:
- **Security Group**: `sg-0933986b1aa1f35eb` (launch-wizard-2)
- **Regla SSH existente**: Puerto 22 desde `0.0.0.0/0` ✅
- **Problema**: Aún así no puedes conectar

---

## 🔍 Primero: Verificar Estado de la Instancia

Antes de agregar reglas, verifica que la instancia esté **Running**:

1. En la consola AWS, ve a la pestaña **"Details"** o **"Status and alarms"**
2. Verifica que el estado sea **"Running"**
3. Si está **"Stopped"** → Click en **"Start instance"**

---

## 🚀 Opción 1: Agregar Reglas desde AWS Console (FÁCIL)

### Paso 1: Ir al Security Group

Desde la instancia que estás viendo:

1. En la pestaña **"Security"** → Click en el Security Group ID: `sg-0933986b1aa1f35eb`
2. O ve directamente a: **EC2 → Network & Security → Security Groups**

### Paso 2: Agregar Regla Inbound

1. Selecciona el Security Group `launch-wizard-2`
2. Click en la pestaña **"Inbound rules"**
3. Click en **"Edit inbound rules"**
4. Click en **"Add rule"**
5. Configura:
   - **Type**: SSH
   - **Protocol**: TCP
   - **Port range**: 22
   - **Source**: 
     - Opción A: `0.0.0.0/0` (permite desde cualquier IP - menos seguro pero funcional para staging)
     - Opción B: `Tu_IP/32` (más seguro - solo tu IP)
6. Click en **"Save rules"**

### Paso 3: Obtener Tu IP Actual

```bash
# En tu terminal local
curl ifconfig.me
```

Anota esta IP y úsala en la regla si eliges la Opción B.

---

## 🚀 Opción 2: Agregar Reglas con AWS CLI (RÁPIDO)

### Paso 1: Instalar/Configurar AWS CLI

```bash
# Verificar si tienes AWS CLI
aws --version

# Si no lo tienes, instálalo:
# macOS:
brew install awscli

# O descarga desde: https://aws.amazon.com/cli/
```

### Paso 2: Configurar Credenciales

```bash
aws configure
# Te pedirá:
# - AWS Access Key ID
# - AWS Secret Access Key
# - Default region: us-east-1
# - Default output format: json
```

### Paso 3: Agregar Regla SSH

```bash
# Obtener tu IP actual
MY_IP=$(curl -s ifconfig.me)
echo "Tu IP: $MY_IP"

# Agregar regla SSH desde tu IP (más seguro)
aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --protocol tcp \
  --port 22 \
  --cidr $MY_IP/32

# O agregar desde cualquier IP (menos seguro, solo para staging)
aws ec2 authorize-security-group-ingress \
  --group-id sg-0933986b1aa1f35eb \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0
```

---

## 🔍 Verificar Reglas Existentes

```bash
# Ver todas las reglas inbound del Security Group
aws ec2 describe-security-groups \
  --group-ids sg-0933986b1aa1f35eb \
  --query 'SecurityGroups[0].IpPermissions' \
  --output table
```

---

## ⚠️ Problema Común: Regla Duplicada

Si ya existe una regla SSH desde `0.0.0.0/0`, **no necesitas agregar otra**.

El problema puede ser:

1. **Instancia detenida** → Iníciala
2. **IP cambió** → Verifica la IP pública actual
3. **Firewall local** → Verifica tu firewall/VPN

---

## 🎯 Verificar Estado Completo de la Instancia

```bash
# Ver estado de la instancia
aws ec2 describe-instances \
  --instance-ids i-06113402909fd6b57 \
  --query 'Reservations[0].Instances[0].[State.Name,PublicIpAddress,PrivateIpAddress]' \
  --output table

# Si está detenida, iniciarla
aws ec2 start-instances --instance-ids i-06113402909fd6b57

# Esperar a que esté running
aws ec2 wait instance-running --instance-ids i-06113402909fd6b57

# Obtener nueva IP pública
aws ec2 describe-instances \
  --instance-ids i-06113402909fd6b57 \
  --query 'Reservations[0].Instances[0].PublicIpAddress' \
  --output text
```

---

## 📋 Checklist Completo

- [ ] Verificar que instancia está **Running**
- [ ] Verificar IP pública actual de la instancia
- [ ] Verificar que Security Group tiene regla SSH (puerto 22)
- [ ] Obtener tu IP actual: `curl ifconfig.me`
- [ ] Agregar regla SSH desde tu IP (si es necesario)
- [ ] Probar conexión: `ssh -i staging-tausepro-key.pem ubuntu@[IP_ACTUAL]`

---

## 🚀 Después de Agregar Reglas

Una vez que puedas conectar:

```bash
# Completar configuración de staging
./scripts/completar-staging-no-interactive.sh

# O manualmente
ssh -i staging-tausepro-key.pem ubuntu@13.218.39.31
```

---

## 💡 Recomendación de Seguridad

**Para Staging:**
- ✅ Puedes usar `0.0.0.0/0` (permite desde cualquier IP)
- ⚠️ Menos seguro pero funcional para pruebas

**Para Producción:**
- ✅ Usa solo tu IP específica: `Tu_IP/32`
- ✅ Más seguro

---

## 🆘 Si Aún No Funciona

1. **Verifica que la instancia está Running**
2. **Verifica la IP pública actual** (puede haber cambiado)
3. **Verifica que no hay VPN activa** que cambie tu IP
4. **Prueba desde otra red** (móvil, otra WiFi)

---

**¿Quieres que te ayude a verificar el estado de la instancia primero?**



