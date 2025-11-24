# 🔍 Diagnóstico: Staging No Accesible

## ❌ Problema Detectado

El servidor staging (`13.218.39.31`) **NO está accesible** desde tu máquina local.

**Síntomas:**
- ❌ Ping falla (100% packet loss)
- ❌ SSH timeout
- ❌ No se puede conectar

---

## 🔍 Posibles Causas

### 1. Instancia Detenida
La instancia EC2 puede estar detenida o en estado incorrecto.

**Solución:**
1. Ve a AWS Console → EC2
2. Busca la instancia con IP `13.218.39.31`
3. Verifica el estado:
   - ✅ **Running** = Está corriendo
   - ⚠️ **Stopped** = Necesitas iniciarla
   - ❌ **Terminated** = Necesitas crear nueva

### 2. Security Group Bloquea Conexión
El Security Group puede no permitir SSH desde tu IP actual.

**Solución:**
1. En AWS Console → EC2 → Security Groups
2. Encuentra el Security Group de la instancia staging
3. Verifica regla SSH (puerto 22):
   - Debe permitir tu IP actual
   - O permitir `0.0.0.0/0` (menos seguro, solo para staging)

### 3. IP Cambió
Si la instancia se reinició, la IP pública puede haber cambiado.

**Solución:**
1. Ve a AWS Console → EC2
2. Encuentra la instancia
3. Verifica la IP pública actual
4. Actualiza `STAGING_HOST` en los scripts

### 4. Elastic IP No Asignado
Si no tienes Elastic IP, la IP cambia al reiniciar.

**Solución:**
1. Asigna Elastic IP a la instancia
2. Usa esa IP fija para staging

---

## ✅ Verificación Paso a Paso

### Paso 1: Verificar Estado en AWS Console

1. Ve a: https://console.aws.amazon.com/ec2/
2. Instances → Busca instancia con IP `13.218.39.31`
3. Verifica:
   - **State**: Debe ser "Running"
   - **Public IPv4 address**: Anota la IP actual
   - **Security Group**: Anota el nombre

### Paso 2: Verificar Security Group

1. En la instancia → Security tab
2. Click en el Security Group
3. Inbound rules → Verifica regla SSH:
   ```
   Type: SSH
   Protocol: TCP
   Port: 22
   Source: Tu IP o 0.0.0.0/0
   ```

### Paso 3: Obtener Tu IP Actual

```bash
# En tu máquina local
curl ifconfig.me
```

Anota esta IP y agrega una regla en Security Group si es necesario.

---

## 🚀 Soluciones Rápidas

### Opción A: Arreglar Security Group

```bash
# 1. Obtener tu IP actual
MY_IP=$(curl -s ifconfig.me)
echo "Tu IP: $MY_IP"

# 2. Agregar regla SSH desde tu IP en AWS Console
# O usar AWS CLI:
aws ec2 authorize-security-group-ingress \
  --group-id sg-XXXXX \
  --protocol tcp \
  --port 22 \
  --cidr $MY_IP/32
```

### Opción B: Iniciar Instancia si Está Detenida

```bash
# Encontrar Instance ID
aws ec2 describe-instances \
  --filters "Name=ip-address,Values=13.218.39.31" \
  --query 'Reservations[*].Instances[*].[InstanceId,State.Name]' \
  --output table

# Iniciar instancia
aws ec2 start-instances --instance-ids i-XXXXX

# Esperar a que esté running
aws ec2 wait instance-running --instance-ids i-XXXXX

# Obtener nueva IP
aws ec2 describe-instances \
  --instance-ids i-XXXXX \
  --query 'Reservations[*].Instances[*].PublicIpAddress' \
  --output text
```

### Opción C: Crear Nueva Instancia Staging

Si la instancia fue terminada, crea una nueva:

```bash
./scripts/create-staging-ec2.sh
```

---

## 📋 Checklist de Verificación

Ejecuta estos comandos para diagnosticar:

```bash
# 1. Verificar que puedes hacer ping (puede estar deshabilitado)
ping -c 3 13.218.39.31

# 2. Verificar puerto SSH
nc -zv 13.218.39.31 22

# 3. Intentar SSH directo
ssh -i staging-tausepro-key.pem -v ubuntu@13.218.39.31

# 4. Verificar en AWS Console
# - Estado de instancia
# - Security Group rules
# - IP pública actual
```

---

## 🎯 Próximos Pasos

**Una vez que staging esté accesible:**

1. Ejecutar script de completar:
   ```bash
   ./scripts/completar-staging-no-interactive.sh
   ```

2. Clonar base de datos (si es necesario):
   ```bash
   ./scripts/clone-production-db-to-staging.sh
   ```

3. Desplegar cambios de Fase 1:
   ```bash
   export STAGING_HOST=[IP_ACTUAL]
   ./scripts/deploy-to-staging.sh
   ```

---

## 💡 Recomendación

**Antes de continuar con staging:**

1. ✅ Verifica en AWS Console que la instancia está **Running**
2. ✅ Verifica que el Security Group permite SSH desde tu IP
3. ✅ Verifica la IP pública actual de la instancia
4. ✅ Prueba conexión SSH manualmente

**Si la instancia no existe o fue terminada:**
- Crea una nueva con `./scripts/create-staging-ec2.sh`
- O usa la instancia de producción para pruebas (no recomendado)

---

## 🆘 Si No Puedes Acceder a AWS Console

**Alternativas:**

1. **Usar producción para pruebas** (temporalmente)
   - ⚠️ Solo para pruebas de código
   - ⚠️ No para pruebas de integración completa

2. **Configurar staging localmente**
   - Usar Docker o servidor local
   - Menos realista pero funcional

3. **Esperar a tener acceso a AWS**
   - Completar staging después

---

**¿Necesitas ayuda con AWS Console o prefieres otra solución?**



