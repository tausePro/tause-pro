# 📸 Gestión de Imágenes AMI

## 💰 Costos de las Imágenes AMI

### ¿Las imágenes generan costo?

**SÍ**, las imágenes AMI generan costo por almacenamiento:

- **Costo**: ~$0.10 por GB/mes (en S3)
- **Ejemplo**: Imagen de 30GB = ~$3/mes
- **Ejemplo**: Imagen de 10GB = ~$1/mes

### Cálculo de tus imágenes actuales:

1. `ami-00cee3e99312902ad` (30GB) = ~$3/mes
2. `ami-0f6c695b48223bbd0` (30GB) = ~$3/mes
3. `ami-0bef165e9662ff9d6` (10GB) = ~$1/mes

**Total actual**: ~$7/mes en imágenes

---

## ✅ Recomendación: Crear Nueva Imagen AHORA

### ¿Por qué crear una nueva imagen ahora?

1. **Incluye cambios de esta mañana**:
   - Refactorización Fase 1
   - AgentOrchestratorService mejorado
   - AgentIntelligenceService nuevo
   - Cambios en ChatbotAgent
   - Mejoras en UI

2. **Backup actualizado**:
   - Tienes un punto de restauración reciente
   - Si algo sale mal, puedes restaurar rápido

3. **Para staging**:
   - Nueva instancia tendrá todos los cambios
   - No necesitarás desplegar manualmente

---

## 📋 Pasos para Crear Nueva Imagen

### Opción 1: Desde AWS Console

1. Ve a: https://console.aws.amazon.com/ec2/
2. EC2 → Instances
3. Selecciona la instancia de **producción** (`34.207.248.220`)
4. **Actions** → **Image and templates** → **Create image**
5. Configuración:
   - **Image name**: `produccion-magicai-20241112-con-fase1`
   - **Description**: `Backup con cambios Fase 1 - Agent Orchestrator mejorado`
   - Dejar todo lo demás por defecto
6. Click **"Create image"**
7. ⏳ Espera 10-15 minutos

### Opción 2: Desde AWS CLI

```bash
# Obtener Instance ID de producción
PROD_INSTANCE_ID=$(aws ec2 describe-instances \
  --filters "Name=ip-address,Values=34.207.248.220" \
  --query 'Reservations[0].Instances[0].InstanceId' \
  --output text)

# Crear imagen
aws ec2 create-image \
  --instance-id $PROD_INSTANCE_ID \
  --name "produccion-magicai-20241112-con-fase1" \
  --description "Backup con cambios Fase 1 - Agent Orchestrator mejorado"
```

---

## 🗑️ Gestión de Imágenes Antiguas

### ¿Qué imágenes mantener?

**Mantener:**
1. ✅ **Nueva imagen** (la que vas a crear ahora) - Más reciente con Fase 1
2. ✅ **Imagen de ayer** (`ami-00cee3e99312902ad`) - Backup reciente
3. ⚠️ **Imagen de Nov 5** (`ami-0f6c695b48223bbd0`) - Solo si quieres mantener backup antiguo

**Eliminar:**
- ❌ **Imagen de staging** (`ami-0bef165e9662ff9d6`) - 10GB, incompleta, problemática

### Recomendación de Limpieza:

**Opción Conservadora** (mantener más backups):
- Mantener: Nueva imagen + Imagen de ayer + Imagen Nov 5
- Eliminar: Solo imagen de staging
- **Costo**: ~$9/mes (nueva imagen incluida)

**Opción Optimizada** (solo backups necesarios):
- Mantener: Nueva imagen + Imagen de ayer
- Eliminar: Imagen Nov 5 + Imagen de staging
- **Costo**: ~$6/mes
- **Ahorro**: $3/mes

---

## 🚀 Plan Recomendado

### Paso 1: Crear Nueva Imagen (AHORA)

```bash
# Desde producción
# Crear imagen con nombre descriptivo
# Esperar a que termine
```

### Paso 2: Crear Nueva Instancia Staging

1. Usar la **nueva imagen** que acabas de crear
2. Configurar con User Data
3. Debería tener todos los cambios de Fase 1

### Paso 3: Limpiar Imágenes Antiguas

**Eliminar:**
- Imagen de staging (`ami-0bef165e9662ff9d6`) - Problemas conocidos
- Imagen de Nov 5 (`ami-0f6c695b48223bbd0`) - Muy antigua (opcional)

**Mantener:**
- Nueva imagen (con Fase 1)
- Imagen de ayer (backup reciente)

---

## 📋 Comandos para Eliminar Imágenes

### Desde AWS Console:

1. EC2 → AMIs
2. Selecciona imagen a eliminar
3. **Actions** → **Deregister AMI**
4. Confirmar
5. **Snapshots** → Buscar snapshot asociado → **Delete**

### Desde AWS CLI:

```bash
# Eliminar imagen de staging
aws ec2 deregister-image --image-id ami-0bef165e9662ff9d6

# Encontrar snapshot asociado
aws ec2 describe-snapshots \
  --filters "Name=description,Values=*ami-0bef165e9662ff9d6*" \
  --query 'Snapshots[*].SnapshotId' \
  --output text

# Eliminar snapshot
aws ec2 delete-snapshot --snapshot-id snap-XXXXX
```

---

## ✅ Checklist

- [ ] Crear nueva imagen de producción AHORA
- [ ] Esperar a que termine (10-15 min)
- [ ] Anotar nuevo AMI ID
- [ ] Crear nueva instancia staging con nueva imagen
- [ ] Eliminar imagen de staging problemática
- [ ] (Opcional) Eliminar imagen de Nov 5 si quieres ahorrar

---

## 💡 Resumen

1. **SÍ, crea nueva imagen ahora** - Incluye cambios de Fase 1
2. **SÍ, las imágenes generan costo** - ~$0.10/GB/mes
3. **SÍ, elimina imágenes antiguas** - Mantén solo las necesarias
4. **Recomendación**: Mantener nueva imagen + imagen de ayer = ~$6/mes

**¿Quieres que te guíe para crear la nueva imagen ahora?**



