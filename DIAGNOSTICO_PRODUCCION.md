# 🔍 Diagnóstico: Producción No Conecta

## 📊 Estado Actual

- ✅ **Instancia**: Running (según AWS)
- ✅ **IP Pública**: `34.207.248.220` (correcta)
- ❌ **Ping**: No responde (100% packet loss)
- ❌ **SSH**: Timeout
- ❌ **HTTP**: No responde

---

## 🔍 Posibles Causas

### 1. Security Group Bloquea Todo el Tráfico

El Security Group puede no tener reglas que permitan:
- SSH (puerto 22)
- HTTP (puerto 80)
- HTTPS (puerto 443)

**Solución**: Agregar reglas al Security Group.

### 2. Firewall Interno Bloqueando

Puede haber un firewall dentro de la instancia bloqueando conexiones.

**Solución**: Conectar vía AWS Systems Manager Session Manager (si está habilitado).

### 3. Problema de Red/VPC

La instancia puede estar en una VPC/subnet con configuración incorrecta.

**Solución**: Verificar configuración de red.

### 4. Servicios Caídos

La instancia está corriendo pero Nginx/PHP-FPM están detenidos.

**Solución**: Reiniciar servicios (si puedes conectar).

---

## 🚀 Soluciones

### Opción 1: Verificar y Arreglar Security Group

```bash
# Obtener Security Group ID
SG_ID=$(aws ec2 describe-instances \
  --filters "Name=ip-address,Values=34.207.248.220" \
  --query 'Reservations[0].Instances[0].SecurityGroups[0].GroupId' \
  --output text)

# Ver reglas actuales
aws ec2 describe-security-groups \
  --group-ids $SG_ID \
  --query 'SecurityGroups[0].IpPermissions' \
  --output table

# Agregar regla SSH si falta
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 22 \
  --cidr 0.0.0.0/0

# Agregar regla HTTP si falta
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 80 \
  --cidr 0.0.0.0/0

# Agregar regla HTTPS si falta
aws ec2 authorize-security-group-ingress \
  --group-id $SG_ID \
  --protocol tcp \
  --port 443 \
  --cidr 0.0.0.0/0
```

### Opción 2: Conectar vía AWS Systems Manager

Si Session Manager está habilitado:

```bash
# Obtener Instance ID
INSTANCE_ID=$(aws ec2 describe-instances \
  --filters "Name=ip-address,Values=34.207.248.220" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text)

# Conectar vía Session Manager
aws ssm start-session --target $INSTANCE_ID
```

### Opción 3: Verificar en AWS Console

1. Ve a EC2 → Instances
2. Busca instancia con IP `34.207.248.220`
3. Click en la instancia → Tab "Security"
4. Click en el Security Group
5. Verifica reglas Inbound:
   - ¿Hay regla SSH (22)?
   - ¿Hay regla HTTP (80)?
   - ¿Hay regla HTTPS (443)?
6. Si faltan → Click "Edit inbound rules" → Agregar

---

## ⚠️ Mientras Tanto

**No podemos clonar BD desde producción** si producción no está accesible.

**Opciones:**

1. **Continuar con staging usando BD vacía**
   - Ejecutar migraciones
   - Crear datos de prueba

2. **Esperar a arreglar producción**
   - Luego clonar BD

3. **Usar backup local** (si tienes)
   - Restaurar en staging

---

## 📋 Checklist de Diagnóstico

- [ ] Verificar Security Group en AWS Console
- [ ] Verificar que hay reglas para puertos 22, 80, 443
- [ ] Intentar conectar vía Session Manager
- [ ] Verificar logs de CloudWatch (si está habilitado)
- [ ] Verificar estado de servicios en la instancia

---

## 💡 Próximo Paso Recomendado

**Verifica Security Group en AWS Console primero**. Es lo más probable que esté bloqueando todo el tráfico.

¿Puedes acceder a AWS Console para verificar el Security Group?



