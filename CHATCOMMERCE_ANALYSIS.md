# 🛍️ Análisis: ChatCommerce - Full Chat Commerce System

## 📋 Estado Actual del Sistema

### ✅ **Componentes Existentes:**

#### 1. **Backend - Workflow System**
**Archivo**: `app/Workflows/ChatcommerceWorkflow.php`
- ✅ Usa NeuronAI Workflow (sistema de agentes)
- ✅ Tiene 2 steps:
  - `product_recommendation` → ProductAgent
  - `sales_process` → SalesAgent
- ✅ Detecta intención de compra con keywords
- ✅ Flujo completo: Query → Recomendación → Venta → Info Cliente → Orden

#### 2. **Backend - Controller**
**Archivo**: `app/Http/Controllers/Api/ChatcommerceController.php`
- ✅ 3 endpoints principales:
  - `POST /api/chatcommerce/chat` - Maneja queries del cliente
  - `POST /api/chatcommerce/start-purchase` - Inicia proceso de compra
  - `POST /api/chatcommerce/customer-info` - Procesa info del cliente
  - `POST /api/chatcommerce/create-order` - Crea orden final

#### 3. **Frontend - Vista de Testing**
**Archivo**: `resources/views/chatbot-test.blade.php`
- ✅ Tienda de prueba completa
- ✅ 4 productos de ejemplo (belleza, tech, moda, food)
- ✅ Simulación de comportamientos (scroll, exit intent, etc.)
- ✅ Debug panel para triggers
- ✅ Chatbot embebido con UUID: `9ed094f8-9d78-4496-b71e-9933ea9c013d`

#### 4. **Rutas API**
**Archivo**: `routes/api.php` (líneas 221-225)
```php
Route::prefix('chatcommerce')->group(function () {
    Route::post('/chat', 'App\Http\Controllers\Api\ChatcommerceController@chat');
    Route::post('/start-purchase', 'App\Http\Controllers\Api\ChatcommerceController@startPurchase');
    Route::post('/customer-info', 'App\Http\Controllers\Api\ChatcommerceController@processCustomerInfo');
    Route::post('/create-order', 'App\Http\Controllers\Api\ChatcommerceController@createOrder');
});
```

---

## ❌ **Problemas Identificados:**

### 1. **Dependencia de NeuronAI**
```php
use NeuronAI\Workflow;
use NeuronAI\Workflow\Step;
use NeuronAI\Chat\Messages\UserMessage;
```
- ❌ NeuronAI fue eliminado del proyecto
- ❌ Los agentes `ProductAgent` y `SalesAgent` no existen
- ❌ El workflow no puede funcionar sin estas dependencias

### 2. **Agentes Faltantes**
```php
use App\Agents\ProductAgent;  // ❌ No existe
use App\Agents\SalesAgent;    // ❌ No existe
```
- Estos archivos fueron eliminados en el cleanup de NeuronAI

### 3. **Integración con WooCommerce**
- ✅ Existe `WooCommerceService` con funcionalidad completa
- ❌ No está integrado con el ChatcommerceWorkflow
- ❌ El workflow usa agentes ficticios en lugar del servicio real

---

## 🎯 **Objetivo: Full Chat Commerce**

### **Flujo Deseado:**

```
Usuario en tienda
     ↓
Trigger proactivo: "¿Necesitas ayuda?"
     ↓
Usuario: "Quiero comprar X"
     ↓
Sales Agent detecta intención
     ↓
Muestra productos de WooCommerce
     ↓
Usuario selecciona producto
     ↓
Captura info del cliente
     ↓
Crea orden en WooCommerce
     ↓
Genera link de pago Wompi
     ↓
Usuario paga
     ↓
Confirmación y seguimiento
```

---

## 🛠️ **Plan de Refactorización:**

### **Fase 1: Migrar de NeuronAI a Sistema Propio** ✅ (Ya tenemos)
- ✅ `AgentOrchestratorService` - Orquestador principal
- ✅ `ProductOrchestratorService` - Manejo de productos
- ✅ Tabla `ext_chatbot_agents` - Configuración de agentes
- ✅ `AgentTypeEnum` - Tipos de agentes

### **Fase 2: Refactorizar ChatcommerceWorkflow**
**Nuevo archivo**: `app/Extensions/Chatbot/System/Services/ChatCommerceService.php`

```php
class ChatCommerceService
{
    public function __construct(
        protected AgentOrchestratorService $orchestrator,
        protected ProductOrchestratorService $productService,
        protected WooCommerceService $woocommerce,
        protected WompiService $wompi
    ) {}

    public function handleCustomerQuery(string $query, Chatbot $chatbot): array
    {
        // 1. Detectar intención con AgentOrchestrator
        $intent = $this->orchestrator->detectIntent($query);
        
        // 2. Si es compra, usar ProductOrchestrator
        if ($intent === 'purchase') {
            return $this->productService->handlePurchaseIntent($query, $chatbot);
        }
        
        // 3. Si es consulta, responder con IA
        return $this->orchestrator->handleQuery($query, $chatbot);
    }
    
    public function startPurchaseFlow(int $productId, Chatbot $chatbot): array
    {
        // 1. Obtener producto de WooCommerce
        $product = $this->woocommerce->getProduct($productId);
        
        // 2. Crear conversación de venta
        // 3. Retornar datos para el chat
    }
    
    public function createOrder(array $customerData, array $cart, Chatbot $chatbot): array
    {
        // 1. Crear orden en WooCommerce
        $order = $this->woocommerce->createOrder($customerData, $cart);
        
        // 2. Generar link de pago Wompi
        $paymentLink = $this->wompi->createPaymentLink($order);
        
        // 3. Retornar confirmación
        return [
            'order_id' => $order['id'],
            'payment_link' => $paymentLink,
            'message' => '¡Orden creada! Procede al pago.'
        ];
    }
}
```

### **Fase 3: Actualizar Controller**
**Archivo**: `app/Http/Controllers/Api/ChatcommerceController.php`

```php
class ChatcommerceController extends Controller
{
    public function __construct(
        protected ChatCommerceService $service
    ) {}

    public function chat(Request $request): JsonResponse
    {
        $chatbot = Chatbot::where('uuid', $request->chatbot_uuid)->firstOrFail();
        
        $result = $this->service->handleCustomerQuery(
            $request->message,
            $chatbot
        );
        
        return response()->json($result);
    }
    
    // ... otros métodos actualizados
}
```

### **Fase 4: Frontend - Componente de Productos**
**Nuevo archivo**: `public/vendor/chatbot/js/chat-commerce.js`

```javascript
class ChatCommerce {
    constructor(chatbotInstance) {
        this.chatbot = chatbotInstance;
        this.cart = [];
    }
    
    showProduct(product) {
        // Renderizar tarjeta de producto en el chat
        const productCard = `
            <div class="product-card">
                <img src="${product.image}" />
                <h3>${product.name}</h3>
                <p>${product.price}</p>
                <button onclick="chatCommerce.addToCart(${product.id})">
                    Agregar al carrito
                </button>
            </div>
        `;
        
        this.chatbot.addMessage(productCard, 'assistant');
    }
    
    addToCart(productId) {
        // Agregar producto al carrito
        // Actualizar UI del carrito en el chat
    }
    
    showCheckoutForm() {
        // Mostrar formulario de datos del cliente
    }
    
    processPayment(orderData) {
        // Redirigir a Wompi o mostrar iframe de pago
    }
}
```

---

## 📊 **Arquitectura Propuesta:**

```
┌─────────────────────────────────────────────────┐
│          Frontend (Chat Embebido)               │
│  - Muestra productos                            │
│  - Captura info cliente                         │
│  - Maneja carrito                               │
│  - Integra pago Wompi                           │
└─────────────────┬───────────────────────────────┘
                  │
                  ↓
┌─────────────────────────────────────────────────┐
│     ChatCommerceService (Nuevo)                 │
│  - Orquesta el flujo completo                   │
│  - Usa AgentOrchestrator para intenciones       │
│  - Usa ProductOrchestrator para productos       │
│  - Integra WooCommerce + Wompi                  │
└─────────────────┬───────────────────────────────┘
                  │
        ┌─────────┴─────────┐
        ↓                   ↓
┌──────────────────┐  ┌──────────────────┐
│ AgentOrchestrator│  │ProductOrchestrator│
│  - Detecta intent│  │  - Busca productos│
│  - Routing       │  │  - Muestra catálogo│
│  - Sales Agent   │  │  - Crea órdenes   │
└──────────────────┘  └──────────────────┘
        │                   │
        └─────────┬─────────┘
                  ↓
┌─────────────────────────────────────────────────┐
│         Servicios Externos                      │
│  - WooCommerceService (productos, órdenes)      │
│  - WompiService (pagos)                         │
│  - OpenAI (respuestas IA)                       │
└─────────────────────────────────────────────────┘
```

---

## 🚀 **Ruta de Implementación:**

### **Sprint 1: Backend Core** (2-3 días)
- [ ] Crear `ChatCommerceService`
- [ ] Refactorizar `ChatcommerceController`
- [ ] Integrar con `AgentOrchestratorService`
- [ ] Integrar con `ProductOrchestratorService`
- [ ] Testing de endpoints

### **Sprint 2: Frontend Components** (2-3 días)
- [ ] Crear `chat-commerce.js`
- [ ] Componente de tarjeta de producto
- [ ] Componente de carrito
- [ ] Formulario de checkout
- [ ] Integración con Wompi

### **Sprint 3: Flujo Completo** (2 días)
- [ ] Conectar frontend con backend
- [ ] Testing end-to-end
- [ ] Manejo de errores
- [ ] UX polish

### **Sprint 4: Testing en Producción** (1 día)
- [ ] Deploy a staging
- [ ] Testing con productos reales
- [ ] Ajustes finales
- [ ] Deploy a producción

---

## 📝 **Archivos Clave:**

### **Existentes (Usar):**
- ✅ `app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php`
- ✅ `app/Extensions/Chatbot/System/Services/ProductOrchestratorService.php`
- ✅ `app/Extensions/Chatbot/System/Services/WooCommerceService.php`
- ✅ `app/Extensions/Chatbot/System/Services/WompiService.php`
- ✅ `resources/views/chatbot-test.blade.php` (para testing)

### **Crear:**
- 🆕 `app/Extensions/Chatbot/System/Services/ChatCommerceService.php`
- 🆕 `public/vendor/chatbot/js/chat-commerce.js`
- 🆕 `app/Extensions/Chatbot/resources/views/frontend-ui/components/product-card.blade.php`
- 🆕 `app/Extensions/Chatbot/resources/views/frontend-ui/components/checkout-form.blade.php`

### **Refactorizar:**
- 🔄 `app/Http/Controllers/Api/ChatcommerceController.php`
- 🔄 `app/Workflows/ChatcommerceWorkflow.php` → Deprecar y migrar lógica

### **Eliminar:**
- ❌ `app/Agents/ProductAgent.php` (ya eliminado)
- ❌ `app/Agents/SalesAgent.php` (ya eliminado)
- ❌ Dependencias de NeuronAI

---

## 🎯 **Próximos Pasos Inmediatos:**

1. **Revisar la vista de testing**
   - URL: `/chatbot-test` (si existe la ruta)
   - Verificar que el chatbot se carga
   - Probar triggers proactivos

2. **Crear ChatCommerceService**
   - Migrar lógica de ChatcommerceWorkflow
   - Integrar servicios existentes
   - Testing unitario

3. **Actualizar Controller**
   - Remover dependencia de Workflow
   - Usar ChatCommerceService
   - Validaciones y manejo de errores

4. **Frontend Components**
   - Diseñar tarjetas de producto
   - Implementar carrito en el chat
   - Formulario de checkout

---

## 🔗 **Rutas Relevantes:**

```php
// Testing
GET  /chatbot-test                           // Vista de testing

// API ChatCommerce
POST /api/chatcommerce/chat                  // Query del cliente
POST /api/chatcommerce/start-purchase        // Iniciar compra
POST /api/chatcommerce/customer-info         // Info del cliente
POST /api/chatcommerce/create-order          // Crear orden

// API Chatbot (existente)
POST /api/v2/chatbot/{uuid}/chat            // Chat normal
GET  /api/v2/chatbot/{uuid}/triggers        // Triggers proactivos
```

---

## ✅ **Checklist de Migración:**

- [ ] Eliminar dependencias de NeuronAI
- [ ] Crear ChatCommerceService
- [ ] Refactorizar Controller
- [ ] Crear componentes frontend
- [ ] Integrar con WooCommerce
- [ ] Integrar con Wompi
- [ ] Testing completo
- [ ] Documentación
- [ ] Deploy

---

**Estado**: 🟡 Sistema existente pero no funcional (dependencias rotas)
**Prioridad**: 🔴 Alta (core feature para ventas)
**Complejidad**: 🟠 Media-Alta (requiere refactorización completa)
