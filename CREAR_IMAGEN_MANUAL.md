# 📸 Crear Imagen de Producción - Instrucciones Manuales

## ⚠️ Nota

No tengo permisos para crear imágenes automáticamente. Sigue estos pasos manuales:

---

## 🚀 Pasos para Crear Imagen

### Paso 1: Ir a AWS Console

1. Ve a: https://console.aws.amazon.com/ec2/
2. Click en "Instances" (menú izquierdo)

### Paso 2: Seleccionar Instancia de Producción

1. Busca la instancia con IP `34.207.248.220`
2. O busca por Instance ID: `i-09a8c6dc2ab7ee33a`
3. **Selecciona la instancia** (checkbox)

### Paso 3: Crear Imagen

1. Click en **"Actions"** (botón arriba)
2. **Image and templates** → **Create image**

### Paso 4: Configurar Imagen

1. **Image name**: `produccion-magicai-20251112-con-fase1`
2. **Image description**: `Backup con cambios Fase 1 - Agent Orchestrator mejorado`
3. Dejar todo lo demás por defecto
4. Click **"Create image"**

### Paso 5: Esperar

- ⏳ La creación toma **10-15 minutos**
- Puedes ver el progreso en: EC2 → AMIs
- Estado cambiará de "pending" a "available"

---

## 🔍 Verificar que se Creó

### Desde AWS Console:

1. EC2 → AMIs
2. Busca `produccion-magicai-20251112-con-fase1`
3. Verifica que estado es "available"

### Desde Terminal:

```bash
aws ec2 describe-images \
  --owners self \
  --filters "Name=name,Values=produccion-magicai-20251112-con-fase1" \
  --query 'Images[*].[ImageId,Name,State]' \
  --output table
```

---

## 📋 Después de Crear la Imagen

Una vez que la imagen esté "available":

1. **Anota el AMI ID** (ej: `ami-xxxxxxxxxxxxx`)
2. **Usa esa imagen** para crear la nueva instancia staging
3. Sigue la guía: `GUIA_CREAR_NUEVA_INSTANCIA.md`

---

## ✅ Checklist

- [ ] Ir a AWS Console → EC2 → Instances
- [ ] Seleccionar instancia de producción (`i-09a8c6dc2ab7ee33a`)
- [ ] Actions → Image and templates → Create image
- [ ] Nombre: `produccion-magicai-20251112-con-fase1`
- [ ] Crear imagen
- [ ] Esperar 10-15 minutos
- [ ] Verificar que estado es "available"
- [ ] Anotar AMI ID

---

**Ejecuta estos pasos y avísame cuando la imagen esté lista para continuar con la creación de la nueva instancia staging.**



