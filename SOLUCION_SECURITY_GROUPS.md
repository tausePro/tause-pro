# 🔧 Solución: Security Groups - SSH No Conecta

## ✅ Lo Que Ya Verificamos

- ✅ Firewall interno deshabilitado (`ufw disable`)
- ✅ SSH servicio corriendo (`systemctl status ssh`)
- ✅ SSH escuchando en puerto 22 (`ss -tlnp | grep :22`)
- ✅ Servicios iniciados (Nginx, PHP-FPM)

**Pero SSH aún no conecta** → El problema está en **AWS Security Groups**.

---

## 🔍 Verificar Security Groups

### Opción 1: Desde AWS Console (Más Fácil)

1. **AWS Console**: https://console.aws.amazon.com/ec2/
2. **Instances** → Selecciona `i-0bbe91c13a1343538`
3. **Tab "Security"** → Ver **Security groups**
4. **Click en el Security Group** (nombre o ID)
5. **Tab "Inbound rules"**

**Verifica:**
- ¿Hay regla SSH (puerto 22)?
- ¿Desde qué CIDR? (debe ser `0.0.0.0/0` para cualquier IP)

---

### Opción 2: Desde Terminal (Script)

```bash
./VERIFICAR_SECURITY_GROUPS.sh
```

Este script verifica automáticamente todos los Security Groups.

---

## 🔧 Agregar Regla SSH

### Opción 1: Desde AWS Console (Recomendado)

1. **EC2** → **Security Groups**
2. **Selecciona el Security Group** de tu instancia
3. **Tab "Inbound rules"**
4. **Edit inbound rules**
5. **Add rule:**
   - **Type**: SSH
   - **Protocol**: TCP
   - **Port**: 22
   - **Source**: `0.0.0.0/0` (o `Custom` → `0.0.0.0/0`)
6. **Save rules**

---

### Opción 2: Desde Terminal (Script Automático)

```bash
chmod +x AGREGAR_REGLA_SSH.sh
./AGREGAR_REGLA_SSH.sh
```

Este script agrega automáticamente la regla SSH a todos los Security Groups de la instancia.

---

### Opción 3: Comando Manual AWS CLI

```bash
# Obtener Security Group ID primero
SG_ID=$(aws ec2 describe-instances \
    --instance-ids i-0bbe91c13a1343538 \
    --query 'Reservations[0].Instances[0].SecurityGroups[0].GroupId' \
    --output text)

# Agregar regla SSH
aws ec2 authorize-security-group-ingress \
    --group-id $SG_ID \
    --protocol tcp \
    --port 22 \
    --cidr 0.0.0.0/0
```

---

## 🔍 Otros Problemas Posibles

### 1. Network ACLs (Menos Común)

Si Security Groups están bien pero aún no conecta, verifica **Network ACLs**:

1. **VPC** → **Network ACLs**
2. Busca el Network ACL de la subnet de tu instancia
3. Verifica reglas **Inbound**:
   - Debe permitir SSH (22) desde `0.0.0.0/0`

---

### 2. Múltiples Security Groups

La instancia puede tener **múltiples Security Groups**. Todos deben tener regla SSH.

**Verifica en AWS Console:**
- Instancia → Tab "Security" → Ver todos los Security Groups
- Verifica reglas SSH en **cada uno**

---

### 3. IP Pública Cambió

Si reiniciaste la instancia, la IP puede haber cambiado:

```bash
# Verificar IP actual
aws ec2 describe-instances \
    --instance-ids i-0bbe91c13a1343538 \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text
```

---

## 📋 Checklist

- [ ] Verificar Security Groups en AWS Console
- [ ] Verificar regla SSH (22) desde `0.0.0.0/0`
- [ ] Si falta, agregar regla SSH
- [ ] Verificar Network ACLs (si aplica)
- [ ] Verificar IP pública actual
- [ ] Probar SSH: `ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213`

---

## 🚀 Pasos Rápidos

1. **AWS Console** → **EC2** → **Instances** → `i-0bbe91c13a1343538`
2. **Tab "Security"** → Click en Security Group
3. **Inbound rules** → **Edit inbound rules**
4. **Add rule** → SSH (22) desde `0.0.0.0/0` → **Save**
5. **Probar SSH** inmediatamente

---

**El problema está en Security Groups. Verifica y agrega la regla SSH si falta.**


