# 🔗 Asociar Elastic IP a Instancia Staging

## ✅ Elastic IP Creada

- **IP**: `3.220.198.180`
- **Allocation ID**: `eipalloc-0da6d0f6133895b88`
- **Estado**: No asociada (Associated instance ID está vacío)

---

## 🔧 Pasos para Asociar

### Paso 1: Seleccionar Elastic IP

1. En la tabla de Elastic IPs, **selecciona la fila** de `3.220.198.180`
   - Haz click en cualquier parte de la fila (no solo en la IP)
   - La fila debe quedar resaltada/azul

### Paso 2: Abrir Menú de Acciones

1. **Click en "Actions"** (botón arriba a la derecha de la tabla)
2. Se abrirá un menú desplegable

### Paso 3: Asociar Elastic IP

1. En el menú Actions, selecciona **"Associate Elastic IP address"**
2. Se abrirá un modal/pantalla nueva

### Paso 4: Seleccionar Instancia

En el modal que se abre:

1. **Resource type**: Debe estar en "Instance" (por defecto)
2. **Instance**: 
   - Click en el dropdown
   - Busca y selecciona: `i-0bbe91c13a1343538 (staging-tausepro-new)`
   - O busca por nombre: `staging-tausepro-new`
3. **Private IP address**: 
   - Déjalo en "Auto-assign" (automático)
   - O selecciona la IP privada de la instancia si la conoces
4. **Click "Associate"** (botón naranja)

---

## ✅ Verificar Asociación

Después de asociar:

1. **Refresca la página** (botón de refresh arriba)
2. Verifica en la tabla:
   - **Associated instance ID**: Debe mostrar `i-0bbe91c13a1343538`
   - La IP `3.220.198.180` ahora está asociada

---

## 🌐 Actualizar DNS (Si Usas Dominio)

Si tienes `test.tause.pro` apuntando a la IP anterior:

1. Ve a tu proveedor de DNS
2. Actualiza registro A:
   - **Nombre**: `test` (o `@` para dominio raíz)
   - **Tipo**: A
   - **Valor**: `3.220.198.180`
   - **TTL**: 300 (o el que prefieras)

---

## 🔍 Verificar IP Pública de Instancia

Después de asociar, verifica que la instancia muestra la nueva IP:

1. **EC2** → **Instances**
2. Selecciona `i-0bbe91c13a1343538`
3. En la sección "Networking", verifica:
   - **Public IPv4 address**: Debe ser `3.220.198.180`

---

## 📋 Comandos Útiles

### Verificar desde Terminal

```bash
# Ver IP pública de la instancia
aws ec2 describe-instances \
    --instance-ids i-0bbe91c13a1343538 \
    --query 'Reservations[0].Instances[0].PublicIpAddress' \
    --output text

# Debe mostrar: 3.220.198.180
```

### Conectar con Nueva IP

```bash
ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180
```

---

## ⚠️ Nota Importante

- La IP anterior (`13.223.191.141`) ya no funcionará después de asociar
- Usa siempre `3.220.198.180` para conectar
- Esta IP **NO cambiará** aunque reinicies la instancia

---

**Sigue los pasos 1-4 arriba para asociar la Elastic IP a tu instancia.**


