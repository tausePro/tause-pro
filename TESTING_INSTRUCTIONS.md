# 🧪 Testing Instructions - Sales Agent Configuration

**Status**: Phase 3 Complete - Ready for Testing

---

## 📋 **Pre-Testing Checklist**

### **1. Ejecutar Migración**

```bash
# En tu máquina local
php artisan migrate

# Salida esperada:
# Migrating: 2025_10_21_143000_create_ext_chatbot_sales_agent_configs_table
# Migrated: 2025_10_21_143000_create_ext_chatbot_sales_agent_configs_table
```

### **2. Verificar Tabla en BD**

```sql
-- Ejecutar en MySQL
SHOW TABLES LIKE 'ext_chatbot_sales_agent_configs';

-- Resultado esperado:
-- +------------------------------------------+
-- | Tables_in_tausepro (ext_chatbot_sales_*) |
-- +------------------------------------------+
-- | ext_chatbot_sales_agent_configs          |
-- +------------------------------------------+
```

---

## 🚀 **Testing Steps**

### **Paso 1: Acceder al Dashboard**

```
URL: http://localhost:8000/dashboard/chatbot/2/ecommerce
```

**Esperado:**
- ✅ Página carga correctamente
- ✅ 3 secciones visibles: WooCommerce, Wompi, Sales Agent
- ✅ No hay errores en console

---

### **Paso 2: Configurar Sales Agent**

#### **2.1 Llenar Campos Básicos**

En la sección "🤖 Configuración del Agente de Ventas":

1. **Agent Name**: `Ali` (o tu nombre preferido)
2. **Agent Description**: `Soy tu vendedor personal, aquí para ayudarte a encontrar lo que necesitas`
3. **Tone**: Seleccionar `Friendly and Warm`
4. **Sales Strategy**: Seleccionar `Helpful (No pressure)`
5. **Search Strategy**: Seleccionar `Semantic (AI)`
6. **Product Display**: Seleccionar `Conversation + Cards`

**Esperado:**
- ✅ Todos los campos se llenan sin errores
- ✅ Preview se actualiza en tiempo real

---

#### **2.2 Personalizar Tarjetas de Productos**

1. **Button Color**: Hacer clic en color picker, seleccionar verde (`#10b981`)
2. **Button Style**: Seleccionar `Solid`
3. **Card Shadow**: Seleccionar `Medium`
4. **Price Color**: Hacer clic en color picker, seleccionar verde (`#10b981`)
5. **Toggles**: Marcar ambos (Stock indicator + Discount badge)

**Esperado:**
- ✅ Color picker abre correctamente
- ✅ Preview se actualiza con los colores seleccionados
- ✅ Botón cambia de color en tiempo real
- ✅ Sombra de tarjeta visible

---

#### **2.3 Agregar Prompt Personalizado**

En "Custom Prompt", agregar:

```
Eres {agent_name}, un vendedor amigable y servicial.
Tu tono es {tone}.
Cuando el cliente pregunte por productos, busca opciones relevantes.
Estrategia: {strategy}
Nunca presiones, solo ayuda.
```

**Esperado:**
- ✅ Textarea acepta el texto
- ✅ No hay límite de caracteres (máx 2000)

---

### **Paso 3: Guardar Configuración**

1. Hacer clic en botón **"💾 Guardar Configuración del Agente"**

**Esperado:**
- ✅ Página recarga
- ✅ Mensaje de éxito: "✅ Configuración del Agente de Ventas guardada exitosamente"
- ✅ Valores se mantienen después de recargar

---

### **Paso 4: Verificar en BD**

```sql
-- Ejecutar en MySQL
SELECT * FROM ext_chatbot_sales_agent_configs 
WHERE chatbot_id = 2;

-- Resultado esperado:
-- id | chatbot_id | enabled | agent_name | tone | sales_strategy | ... | product_card_config | created_at | updated_at
-- 1  | 2          | 1       | Ali        | friendly | helpful | ... | {...}               | ...        | ...
```

---

### **Paso 5: Verificar Relación en Modelo**

En tinker:

```php
php artisan tinker

$chatbot = App\Extensions\Chatbot\System\Models\Chatbot::find(2);
$config = $chatbot->salesAgentConfig;
$config->agent_name; // "Ali"
$config->getProductCardConfig(); // Array con configuración
```

**Esperado:**
- ✅ Relación funciona correctamente
- ✅ Datos se cargan sin errores

---

## 🐛 **Troubleshooting**

### **Problema: Migración falla**

```
SQLSTATE[HY000]: General error: 1030 Got error...
```

**Solución:**
```bash
# Rollback y reintentar
php artisan migrate:rollback
php artisan migrate
```

---

### **Problema: Página no carga**

**Checklist:**
- [ ] ¿La ruta existe? Verificar en `routes/panel.php`
- [ ] ¿El controller existe? Verificar `ChatbotEcommerceController`
- [ ] ¿La vista existe? Verificar `ecommerce/index.blade.php`
- [ ] ¿Hay errores en logs? Revisar `storage/logs/laravel.log`

---

### **Problema: Color picker no funciona**

**Checklist:**
- [ ] ¿Alpine.js está cargado? Revisar console
- [ ] ¿Los IDs coinciden? Verificar `buttonColorInput`, `buttonColorPreview`
- [ ] ¿El script se ejecuta? Agregar `console.log()` en init()

---

### **Problema: Preview no se actualiza**

**Solución:**
1. Abrir console del navegador (F12)
2. Verificar que no hay errores JavaScript
3. Verificar que el componente de preview se incluye correctamente

---

## ✅ **Checklist Final**

- [ ] Migración ejecutada exitosamente
- [ ] Tabla creada en BD
- [ ] Dashboard carga sin errores
- [ ] Campos se llenan correctamente
- [ ] Color pickers funcionan
- [ ] Preview se actualiza en tiempo real
- [ ] Configuración se guarda en BD
- [ ] Valores persisten después de recargar
- [ ] Relación Chatbot -> SalesAgentConfig funciona
- [ ] No hay errores en logs

---

## 📊 **Próximo Paso: Phase 4**

Una vez completado el testing:

1. **Integrar con sales-agent-component.blade.php**
   - Cargar configuración desde SalesAgentConfig
   - Aplicar estilos de tarjetas según config
   - Usar custom_prompt en prompts dinámicos

2. **Testing en chatbot externo**
   - Verificar que tarjetas se muestran con estilos correctos
   - Verificar que agente responde según configuración
   - Verificar que flujo de compra funciona

---

## 📞 **Support**

Si encuentras problemas:

1. Revisar logs: `storage/logs/laravel.log`
2. Verificar console del navegador (F12)
3. Ejecutar: `php artisan config:cache` si hay cambios en config
4. Ejecutar: `php artisan view:clear` si hay cambios en vistas

---

**¡Listo para testear!** 🚀
