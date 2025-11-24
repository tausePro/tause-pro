# 🔍 Diagnóstico Completo: Red y Conectividad

## ✅ Lo Que Ya Verificamos

- ✅ Firewall interno deshabilitado
- ✅ SSH servicio corriendo y escuchando en puerto 22
- ✅ Security Group tiene regla SSH desde `0.0.0.0/0` (IPv4)

**Pero SSH aún no conecta** → Puede ser:

1. **IPv6 vs IPv4** (tu IP es IPv6 pero Security Group solo IPv4)
2. **Network ACLs** bloqueando
3. **Routing** o configuración de red
4. **IP pública cambió**

---

## 🔍 Verificaciones Necesarias

### 1. Verificar IP Pública Actual

```bash
aws ec2 describe-instances \
    --instance-ids i-0bbe91c13a1343538 \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text
```

**¿Sigue siendo `44.211.83.213`?**

---

### 2. Verificar Reglas IPv6 en Security Group

El Security Group puede tener regla IPv4 pero no IPv6.

**Desde AWS Console:**
1. EC2 → Security Groups → `sg-0daf518dc8b65271d`
2. Inbound rules → Ver si hay regla SSH para IPv6 (`::/0`)

**Si falta regla IPv6:**
```bash
aws ec2 authorize-security-group-ingress \
    --group-id sg-0daf518dc8b65271d \
    --ip-permissions IpProtocol=tcp,FromPort=22,ToPort=22,Ipv6Ranges=[{CidrIpv6=::/0}]
```

---

### 3. Verificar Network ACLs

Network ACLs pueden estar bloqueando el tráfico.

**Desde AWS Console:**
1. VPC → Network ACLs
2. Busca el Network ACL de la subnet de tu instancia
3. Verifica reglas **Inbound**:
   - Debe permitir SSH (22) desde `0.0.0.0/0`

**Obtener subnet ID:**
```bash
SUBNET_ID=$(aws ec2 describe-instances \
    --instance-ids i-0bbe91c13a1343538 \
    --query 'Reservations[0].Instances[0].SubnetId' \
    --output text)

echo "Subnet ID: $SUBNET_ID"
```

---

### 4. Forzar IPv4 en SSH

Si el problema es IPv6, fuerza IPv4:

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

O deshabilita IPv6 temporalmente:

```bash
# En tu Mac
networksetup -setv6off Wi-Fi  # Deshabilitar IPv6 en Wi-Fi
# O
networksetup -setv6off "USB 10/100/1000 LAN"  # Si usas cable
```

---

### 5. Verificar Routing y DNS

```bash
# Verificar que puedes hacer ping
ping -4 44.211.83.213

# Verificar DNS
nslookup 44.211.83.213

# Verificar traceroute
traceroute -4 44.211.83.213
```

---

## 🚀 Soluciones Rápidas

### Solución 1: Agregar Regla IPv6 al Security Group

```bash
aws ec2 authorize-security-group-ingress \
    --group-id sg-0daf518dc8b65271d \
    --ip-permissions IpProtocol=tcp,FromPort=22,ToPort=22,Ipv6Ranges=[{CidrIpv6=::/0}]
```

### Solución 2: Forzar IPv4

```bash
# Siempre usar -4 para forzar IPv4
ssh -4 -i staging-tausepro-key.pem ubuntu@44.211.83.213
```

### Solución 3: Usar Elastic IP

Si la IP cambia, asigna Elastic IP:

1. EC2 → Elastic IPs → Allocate Elastic IP address
2. Actions → Associate Elastic IP address → Selecciona instancia
3. Usa la Elastic IP para conectar

---

## 📋 Checklist Completo

- [ ] Verificar IP pública actual (`44.211.83.213` sigue siendo correcta?)
- [ ] Verificar reglas IPv6 en Security Group
- [ ] Agregar regla IPv6 si falta
- [ ] Verificar Network ACLs
- [ ] Probar con `-4` para forzar IPv4
- [ ] Verificar ping funciona: `ping -4 44.211.83.213`
- [ ] Verificar traceroute: `traceroute -4 44.211.83.213`

---

## 💡 Próximos Pasos

1. **Verifica IP pública actual** - puede haber cambiado
2. **Agrega regla IPv6** al Security Group si falta
3. **Prueba con `-4`** para forzar IPv4
4. **Verifica Network ACLs** si sigue sin funcionar

---

**El problema más probable es IPv6 vs IPv4. Prueba primero con `-4` y agrega regla IPv6 al Security Group.**


