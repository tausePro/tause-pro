# 🔗 Asociar Elastic IP - Paso a Paso Visual

## 📍 Situación Actual

- ✅ Elastic IP creada: `3.220.198.180`
- ❌ No asociada a ninguna instancia
- 🎯 Objetivo: Asociarla a `i-0bbe91c13a1343538`

---

## 🖱️ Pasos Detallados

### Paso 1: Seleccionar la Elastic IP

En la tabla que ves:

1. **Busca la fila** con IP `3.220.198.180`
2. **Haz click en cualquier parte de esa fila** (no solo en el número)
   - Puede ser en la columna "Name", "Type", "Allocation ID", etc.
   - La fila completa debe quedar **resaltada en azul/gris**

**Visual**: La fila debe verse así cuando está seleccionada:
```
[✓] - | 3.220.198.180 | Public IP | eipalloc-... | - | -
```

---

### Paso 2: Abrir Menú Actions

1. **Mira arriba de la tabla**, a la derecha del botón "Allocate Elastic IP address"
2. Verás un botón que dice **"Actions"** (puede tener un ícono de flecha hacia abajo ▼)
3. **Click en "Actions"**
4. Se abrirá un **menú desplegable** con opciones

**Opciones que deberías ver:**
- Associate Elastic IP address ← **Esta es la que necesitas**
- Disassociate Elastic IP address
- Release Elastic IP address
- View details
- etc.

---

### Paso 3: Seleccionar "Associate Elastic IP address"

1. En el menú desplegable, **click en "Associate Elastic IP address"**
2. Se abrirá un **modal/pantalla nueva** con el formulario de asociación

---

### Paso 4: Completar Formulario de Asociación

En el modal que se abre, verás:

#### Campo 1: Resource type
- **Deja como está**: "Instance" (debe estar seleccionado por defecto)

#### Campo 2: Instance (IMPORTANTE)
- **Click en el dropdown** (puede decir "Choose an instance" o estar vacío)
- Se abrirá una lista de instancias
- **Busca y selecciona**:
  - `i-0bbe91c13a1343538`
  - O busca por nombre: `staging-tausepro-new`
- Puedes usar el buscador dentro del dropdown si hay muchas instancias

#### Campo 3: Private IP address
- **Deja en "Auto-assign"** (recomendado)
- O si quieres especificar, selecciona la IP privada de la instancia

#### Botón Final
- **Click en "Associate"** (botón naranja abajo a la derecha)

---

### Paso 5: Verificar

Después de hacer click en "Associate":

1. El modal se cerrará
2. Puede aparecer un mensaje de éxito
3. **Refresca la página** (botón de refresh 🔄 arriba)
4. Verifica en la tabla:
   - La columna **"Associated instance ID"** ahora debe mostrar: `i-0bbe91c13a1343538`

---

## 🔍 Si No Ves el Botón "Actions"

Si no ves el botón "Actions" o el menú:

1. **Asegúrate de haber seleccionado la fila** de la Elastic IP
   - La fila debe estar resaltada
   - Si no está resaltada, haz click de nuevo en la fila

2. **Verifica que estás en la vista correcta**
   - Debe decir "Elastic IP addresses" en el título
   - No debe estar en modo "Details" de una IP individual

3. **Intenta desde la IP directamente**
   - Click en el número `3.220.198.180` (link azul)
   - Esto te llevará a la página de detalles
   - Ahí deberías ver un botón "Actions" también

---

## 🌐 Después de Asociar

Una vez asociada:

1. **La IP pública de la instancia será**: `3.220.198.180`
2. **Conecta con SSH**:
   ```bash
   ssh -4 -i staging-tausepro-key.pem ubuntu@3.220.198.180
   ```

3. **Actualiza DNS** (si usas `test.tause.pro`):
   - Registro A → `3.220.198.180`

4. **Esta IP NO cambiará** aunque reinicies la instancia ✅

---

## ❓ ¿Dónde Está el Botón Actions?

El botón "Actions" está en la **barra superior de la tabla**, generalmente:

```
[Search bar] [🔄] [Actions ▼] [Allocate Elastic IP address]
```

O puede estar en la **barra de herramientas** arriba de la tabla.

---

**¿Puedes ver el botón "Actions"? Si no lo ves, dime qué botones/opciones ves arriba de la tabla.**


