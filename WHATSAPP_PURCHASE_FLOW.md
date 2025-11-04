# 📱 FLUJO DE COMPRA WHATSAPP - DOCUMENTACIÓN

## ✅ IMPLEMENTACIÓN COMPLETADA

### **Fecha:** 30 de Octubre, 2025
### **Rama:** `feature/sales-agent-config-view`
### **Commits:**
- `f50a5aff9` - Sales Agent con orquestación funcionando en web
- `ac6c87389` - Flujo de compra conversacional para WhatsApp

---

## 🏗️ ARQUITECTURA

```
Sales Agent (Único - Multicanal)
    ↓
├── Web Channel
│   └── HTMLRenderer (tarjetas + formularios)
│
└── WhatsApp Channel
    └── ConversationalRenderer (mensajes + estados)
        ├── WhatsAppPurchaseFlowService (State Machine)
        ├── WooCommerceService (crear orden)
        └── WompiService (generar link de pago)
```

---

## 📁 ARCHIVOS CREADOS/MODIFICADOS

### **Nuevos:**
1. `/app/Extensions/Chatbot/System/Services/WhatsAppPurchaseFlowService.php`
   - State Machine para flujo conversacional
   - Recolección de datos paso a paso
   - Integración con WooCommerce y Wompi

### **Modificados:**
1. `/app/Extensions/ChatbotWhatsapp/System/Services/Evolution/EvolutionConversationService.php`
   - Detección de flujo de compra activo
   - Detección de intención de compra
   - Inicio de flujo desde número de producto

2. `/app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php`
   - Agregada orquestación de agentes
   - Envío de datos de productos al frontend

3. `/app/Extensions/Chatbot/System/Services/WooCommerceService.php`
   - Agregados meta_data de atribución TausePro

4. `/config/app.php`
   - Registrado ChatbotSalesAgentServiceProvider

---

## 🔄 FLUJO DE COMPRA WHATSAPP

### **1. Usuario solicita producto:**
```
Usuario: "quiero comprar un kit deportivo"
    ↓
Bot detecta intención (ProductOrchestratorService)
    ↓
Bot muestra productos:
    🛍️ *Productos disponibles:*
    
    *1. KIT DEPORTIVO* - $139,900
    *2. MEDIA DAMA* - $136,400
    
    Escribe el número del producto
```

### **2. Usuario selecciona producto:**
```
Usuario: "1"
    ↓
isPurchaseIntent() detecta número
    ↓
extractProductNumber() extrae "1"
    ↓
startPurchaseFromProductNumber() inicia flujo
    ↓
setState(ASKING_QUANTITY)
    ↓
Bot: "¡Perfecto! KIT DEPORTIVO - $139,900
      ¿Cuántas unidades necesitas?"
```

### **3. Recolección de datos (paso a paso):**

| Estado | Pregunta | Validación |
|--------|----------|------------|
| `ASKING_QUANTITY` | ¿Cuántas unidades? | 1-100 |
| `ASKING_FIRST_NAME` | ¿Tu nombre? | Min 2 caracteres |
| `ASKING_LAST_NAME` | ¿Tu apellido? | Min 2 caracteres |
| `ASKING_EMAIL` | ¿Tu email? | Formato válido |
| `ASKING_DEPARTMENT` | ¿Departamento? | Min 3 caracteres |
| `ASKING_CITY` | ¿Ciudad? | Min 3 caracteres |
| `ASKING_ADDRESS` | ¿Dirección completa? | Min 10 caracteres |

### **4. Creación de orden:**
```
handleAddressResponse()
    ↓
createOrder() llama a WooCommerceService
    ↓
Orden creada en WooCommerce (#1899)
    ↓
generatePaymentLink() llama a WompiService
    ↓
Link de pago generado
    ↓
setState(COMPLETED)
    ↓
Bot envía resumen completo con link de pago
```

---

## 💾 ALMACENAMIENTO DE ESTADOS

### **Redis Cache (24h TTL):**

```php
// Estado actual
Cache::put("whatsapp_purchase_state_{$conversation_id}", $state, 24h);

// Datos temporales
Cache::put("whatsapp_purchase_data_{$conversation_id}", [
    'product_id' => 123,
    'product_name' => 'KIT DEPORTIVO',
    'quantity' => 2,
    'first_name' => 'Juan',
    'last_name' => 'Pérez',
    'email' => 'juan@example.com',
    'phone' => '573001234567',
    'department' => 'Cundinamarca',
    'city' => 'Bogotá',
    'address' => 'Calle 123 #45-67',
    'total' => 279800
], 24h);
```

---

## 🔍 DETECCIÓN DE INTENCIÓN DE COMPRA

### **Método: `isPurchaseIntent()`**

Detecta:
1. **Números simples:** `1`, `2`, `3`
2. **Frases de compra:**
   - "quiero el"
   - "comprar el"
   - "me interesa el"
   - "dame el"
   - "quiero comprar"
   - "lo quiero"
   - "me lo llevo"

### **Método: `extractProductNumber()`**

Extrae número de:
- `"1"` → 1
- `"el 2"` → 2
- `"número 3"` → 3
- `"#4"` → 4

---

## 📊 COMPARACIÓN: WEB vs WHATSAPP

| Aspecto | Widget Web | WhatsApp |
|---------|-----------|----------|
| **UI** | Tarjetas HTML | Mensajes de texto |
| **Datos recolectados** | Formulario visual | Preguntas secuenciales |
| **Validación** | En tiempo real | Después de respuesta |
| **Orden WooCommerce** | ✅ Creada | ✅ Creada |
| **Link Wompi** | ✅ Generado | ✅ Generado |
| **Atribución** | TausePro - Chatbot | TausePro - Chatbot |
| **Resultado final** | **IDÉNTICO** | **IDÉNTICO** |

---

## 🚀 DESPLIEGUE A PRODUCCIÓN

### **Archivos a subir:**

```bash
# 1. Nuevo servicio
app/Extensions/Chatbot/System/Services/WhatsAppPurchaseFlowService.php

# 2. Servicio modificado
app/Extensions/ChatbotWhatsapp/System/Services/Evolution/EvolutionConversationService.php
```

### **Comandos en producción:**

```bash
# 1. Subir archivos
scp -i magicai-tause-key.pem \
  app/Extensions/Chatbot/System/Services/WhatsAppPurchaseFlowService.php \
  ubuntu@34.207.248.220:/tmp/

scp -i magicai-tause-key.pem \
  app/Extensions/ChatbotWhatsapp/System/Services/Evolution/EvolutionConversationService.php \
  ubuntu@34.207.248.220:/tmp/

# 2. Copiar a ubicación final
sudo cp /tmp/WhatsAppPurchaseFlowService.php \
     /var/www/magicai/app/Extensions/Chatbot/System/Services/

sudo cp /tmp/EvolutionConversationService.php \
     /var/www/magicai/app/Extensions/ChatbotWhatsapp/System/Services/Evolution/

# 3. Permisos
sudo chown -R www-data:www-data /var/www/magicai/app/Extensions/

# 4. Limpiar cache
cd /var/www/magicai
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan config:clear

# 5. Verificar logs
tail -f storage/logs/laravel.log | grep "WhatsApp Purchase"
```

---

## 🧪 PRUEBAS

### **Escenario 1: Compra exitosa**
```
1. Enviar: "quiero comprar un kit deportivo"
2. Esperar lista de productos
3. Enviar: "1"
4. Responder cada pregunta:
   - Cantidad: "2"
   - Nombre: "Juan"
   - Apellido: "Pérez"
   - Email: "juan@test.com"
   - Departamento: "Cundinamarca"
   - Ciudad: "Bogotá"
   - Dirección: "Calle 123 #45-67"
5. Verificar:
   - ✅ Orden creada en WooCommerce
   - ✅ Link de pago Wompi generado
   - ✅ Atribución "TausePro - Chatbot"
```

### **Escenario 2: Validación de datos**
```
1. Iniciar compra
2. Enviar cantidad inválida: "0" o "abc"
3. Verificar mensaje de error
4. Enviar cantidad válida: "1"
5. Continuar flujo
```

### **Escenario 3: Cancelación automática**
```
1. Iniciar compra
2. Esperar 24 horas
3. Intentar continuar
4. Verificar que el estado se resetea
```

---

## 📝 LOGS IMPORTANTES

### **Inicio de flujo:**
```
WhatsApp Purchase Flow: State changed
  conversation_id: 123
  new_state: asking_quantity
```

### **Procesamiento de respuesta:**
```
Evolution: Purchase flow response sent
  conversation_id: 123
  state: asking_first_name
```

### **Creación de orden:**
```
WhatsApp Purchase Flow: Order creation started
  conversation_id: 123
  product_id: 456
  total: 279800
```

---

## ⚠️ CONSIDERACIONES

### **✅ Ventajas:**
- Reutiliza servicios existentes (WooCommerce, Wompi)
- No duplica código
- Mismo resultado que widget web
- Fácil de mantener
- Logs detallados

### **⚠️ Limitaciones:**
- Estados se pierden después de 24h
- No soporta múltiples productos en una orden
- No calcula envío automáticamente
- Requiere Redis/Cache funcionando

### **🔮 Mejoras futuras:**
- Persistir estados en BD en lugar de Cache
- Soporte para múltiples productos
- Cálculo automático de envío
- Resumen antes de confirmar
- Opción de cancelar en cualquier momento

---

## 🎯 RESULTADO FINAL

**El flujo de compra en WhatsApp:**
1. ✅ Funciona exactamente igual que en web
2. ✅ Crea órdenes en WooCommerce
3. ✅ Genera links de pago Wompi
4. ✅ Atribuye correctamente a TausePro
5. ✅ NO rompe funcionalidad existente
6. ✅ Es fácil de mantener y extender

---

**Documentado por:** Cascade AI
**Fecha:** 30 de Octubre, 2025
