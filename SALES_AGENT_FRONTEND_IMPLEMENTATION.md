# 🛍️ Sales Agent Frontend Implementation

## 📋 Resumen de Implementación

Se implementó el frontend completo del Sales Agent (Chatcommerce) para TausePro.

### ✅ Archivos Creados/Modificados:

1. **`app/Extensions/Chatbot/resources/views/frontend-ui/components/sales-agent.blade.php`** (NUEVO)
   - Componente JavaScript del Sales Agent
   - Detección de keywords de compra
   - Switch a modo fullscreen
   - Renderizado de catálogo de productos (grid responsive)
   - Manejo de selección de productos
   - Estilos CSS incluidos

2. **`app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-frontpage.blade.php`** (MODIFICADO)
   - Agregada inclusión del componente sales-agent

3. **`app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-scripts.blade.php`** (MODIFICADO)
   - Interceptación de mensajes en `onSendMessage()`
   - Detección de keywords ANTES de enviar al AI
   - Activación automática del Sales Agent cuando se detecta intención de compra

4. **`app/Extensions/Chatbot/resources/views/frame.blade.php`** (MODIFICADO)
   - Agregadas rutas para `getProducts` y `generatePaymentLink`

### 🎯 Funcionalidades Implementadas:

#### 1. ✅ Detección de Keywords
- El chatbot intercepta los mensajes antes de enviarlos al AI
- Detecta palabras clave configuradas: `comprar`, `precio`, `producto`, `catálogo`
- Si detecta intención de compra, activa el Sales Agent en lugar del AI normal

#### 2. ✅ Modo Fullscreen
- Cuando se activa el Sales Agent, el chat cambia a fullscreen
- Botón de cierre (×) en la esquina superior derecha
- Transición suave con animaciones CSS

#### 3. ✅ Catálogo de Productos
- Grid responsive de productos (auto-fill, mínimo 250px por columna)
- Cada producto muestra:
  - Imagen (con fallback si no carga)
  - Nombre del producto
  - Precio formateado
  - Badge de descuento (si aplica)
  - Estado de stock
  - Cantidad disponible
- Productos seleccionables con efecto visual (borde azul)
- Scroll vertical si hay muchos productos

#### 4. ⏳ Flujo de Compra (Pendiente de completar)
- [TODO] Selección de cantidad
- [TODO] Formulario de datos de envío
- [TODO] Generación de link de pago Wompi
- [TODO] Confirmación de compra

### 🔧 Estructura del Código:

```javascript
window.SalesAgent = {
    // State
    isActive: false,
    isFullscreen: false,
    selectedProducts: [],
    customerData: {},
    currentStep: 'catalog',
    
    // Methods
    detectPurchaseIntent(message) { ... },
    activate(chatbotInstance) { ... },
    enableFullscreen(chatbotInstance) { ... },
    loadProducts(chatbotInstance) { ... },
    renderProductCatalog(chatbotInstance) { ... },
    selectProduct(productId) { ... },
    deactivate(chatbotInstance) { ... }
}
```

### 🎨 Estilos CSS:

- `.sales-agent-fullscreen`: Modo pantalla completa
- `.product-grid`: Grid responsive de productos
- `.product-card`: Card individual de producto con hover effect
- `.product-card.selected`: Estado seleccionado (borde azul)
- `.sales-agent-header`: Header con gradiente morado

### 📡 API Endpoints Utilizados:

1. **GET** `/api/v2/chatbot/{uuid}/products`
   - Obtiene lista de productos sincronizados de WooCommerce
   - Respuesta: `{ success: true, products: [...] }`

2. **POST** `/api/v2/chatbot/{uuid}/generate-payment-link`
   - Genera link de pago con Wompi
   - Body: `{ product_id, quantity, customer_data }`
   - Respuesta: `{ success: true, payment_link: "..." }`

### 🚀 Flujo de Usuario:

```
1. Usuario: "Hola, necesito muletas"
   ↓
2. Sistema detecta keyword "muletas"
   ↓
3. Switch a fullscreen mode
   ↓
4. Carga productos desde WooCommerce
   ↓
5. Muestra catálogo en grid
   ↓
6. Usuario selecciona productos
   ↓
7. [NEXT] Cantidad, datos de envío, pago
```

### ⚠️ Pendientes:

1. Implementar selección de cantidad
2. Implementar formulario de datos de envío
3. Integrar generación de link de pago Wompi
4. Manejar respuesta de pago exitoso/fallido
5. Testing completo del flujo

### 📝 Notas Técnicas:

- El Sales Agent solo se activa en producción (`@if (!$is_editor)`)
- Keywords se cargan dinámicamente desde la BD
- Los productos se obtienen de la tabla `ext_chatbot_products`
- El modo fullscreen es completamente responsive
- Fallback de imagen si no carga la URL del producto


