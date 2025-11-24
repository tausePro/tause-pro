# 🚨 URGENTE: Producción No Conecta

## ⚠️ Problema Detectado

Producción (`34.207.248.220`) **NO está accesible**.

---

## 🔍 Diagnóstico Inmediato

### 1. Verificar en AWS Console

1. Ve a: https://console.aws.amazon.com/ec2/
2. Busca instancia con IP `34.207.248.220`
3. Verifica:
   - **Estado**: ¿Running, Stopped, Terminated?
   - **IP Pública**: ¿Sigue siendo `34.207.248.220`?
   - **Security Group**: ¿Permite HTTP/HTTPS?

### 2. Posibles Causas

1. **Instancia detenida** → Iniciarla
2. **Instancia terminada** → Crear nueva
3. **IP cambió** → Verificar nueva IP
4. **Security Group bloqueado** → Verificar reglas
5. **Problema de red** → Verificar VPC/Subnet

---

## 🚨 Acciones Inmediatas

### Opción A: Verificar Estado con AWS CLI

```bash
# Buscar instancia por IP
aws ec2 describe-instances \
  --filters "Name=ip-address,Values=34.207.248.220" \
  --query 'Reservations[0].Instances[0].[InstanceId,State.Name,PublicIpAddress]' \
  --output table

# Si encuentras Instance ID, verificar estado completo
aws ec2 describe-instances \
  --instance-ids i-XXXXX \
  --query 'Reservations[0].Instances[0].[State.Name,PublicIpAddress,PrivateIpAddress,InstanceType]' \
  --output table
```

### Opción B: Verificar en AWS Console

1. EC2 → Instances
2. Busca por IP `34.207.248.220`
3. Verifica estado y detalles

---

## 🔧 Soluciones Según el Problema

### Si Instancia Está Detenida

```bash
# Iniciar instancia
aws ec2 start-instances --instance-ids i-XXXXX

# Esperar a que esté running
aws ec2 wait instance-running --instance-ids i-XXXXX

# Obtener nueva IP (si no tiene Elastic IP)
aws ec2 describe-instances \
  --instance-ids i-XXXXX \
  --query 'Reservations[0].Instances[0].PublicIpAddress' \
  --output text
```

### Si Instancia Está Terminada

**⚠️ CRÍTICO**: Necesitas crear nueva instancia o restaurar desde backup.

### Si IP Cambió

1. Obtener nueva IP pública
2. Actualizar DNS si es necesario
3. Verificar que Security Group permite conexiones

### Si Security Group Bloquea

```bash
# Ver reglas del Security Group
aws ec2 describe-security-groups \
  --group-ids sg-XXXXX \
  --query 'SecurityGroups[0].IpPermissions' \
  --output table

# Agregar regla HTTP/HTTPS si falta
aws ec2 authorize-security-group-ingress \
  --group-id sg-XXXXX \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0

aws ec2 authorize-security-group-ingress \
  --group-id sg-XXXXX \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0
```

---

## 📋 Checklist de Verificación

- [ ] Verificar estado de instancia en AWS Console
- [ ] Verificar IP pública actual
- [ ] Verificar Security Group permite HTTP/HTTPS
- [ ] Verificar que servicios están corriendo (Nginx, PHP-FPM)
- [ ] Verificar logs de aplicación

---

## 🆘 Si No Puedes Acceder a AWS

**Opción temporal**: Usar staging para pruebas mientras arreglas producción.

**⚠️ IMPORTANTE**: 
- NO clonar BD desde producción si producción está caída
- Trabajar con BD vacía en staging o datos de prueba
- Arreglar producción primero

---

## 💡 Recomendación

1. **Primero**: Verificar estado en AWS Console
2. **Si está detenida**: Iniciarla
3. **Si está corriendo pero no responde**: Verificar servicios y logs
4. **Mientras tanto**: Continuar con staging usando BD vacía o datos de prueba

---

**¿Puedes acceder a AWS Console para verificar el estado de la instancia?**



