# 🎯 Sales Agent Orchestrator - Documentación

## 📋 Resumen

Se implementó un **orquestador automático** que detecta cuándo la respuesta del chatbot AI menciona productos y automáticamente activa el Sales Agent para mostrar un grid visual de productos e iniciar el flujo de compra.

---

## 🏗️ Arquitectura

```
┌─────────────────────────────────────────────────────────────┐
│                    FLUJO COMPLETO                            │
└─────────────────────────────────────────────────────────────┘

1. Usuario escribe: "¿Tienen aceite de coco?"
   ↓
2. Backend AI (con entrenamiento/embeddings normal)
   → Responde: "Sí, tenemos Aceite de Coco Orgánico por $45.000..."
   ↓
3. ORQUESTADOR (frontend-ui-scripts.blade.php línea ~893)
   → Ejecuta: shouldEnhanceResponse(message)
   → Detecta:
      ✅ Menciona "aceite de coco" (producto en BD)
      ✅ Contexto comercial ("tenemos", "precio")
   ↓
4. Sales Agent (sales-agent-component.blade.php)
   → Ejecuta: enhanceMessageWithProducts()
   → findMentionedProducts() → busca productos por relevancia
   → createProductCardsHTML() → genera grid visual
   ↓
5. UI: Mensaje AI + Grid con tarjetas de productos
   ↓
6. Usuario: Click "🛒 Comprar"
   ↓
7. Sales Agent: startPurchase() → Flujo de recolección de datos
   ↓
8. Backend API: createOrder() → Wompi payment link
```

---

## 🔧 Componentes Modificados

### 1. **frontend-ui-scripts.blade.php** (~línea 893)

**Ubicación**: `app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-scripts.blade.php`

**Cambio**: Agregado orquestador en método `onReceiveMessage()`

```javascript
// ✨ ORQUESTADOR: Activar Sales Agent automáticamente
if (window.SalesAgent && window.SalesAgent.enabled && window.SalesAgent.productsLoaded) {
    setTimeout(() => {
        const assistantMessages = document.querySelectorAll('...');
        if (assistantMessages.length > 0 && messageToReplace.message) {
            const lastMessageEl = assistantMessages[assistantMessages.length - 1];
            
            if (window.SalesAgent.shouldEnhanceResponse(messageToReplace.message)) {
                const contentWrap = lastMessageEl.querySelector('...');
                window.SalesAgent.enhanceMessageWithProducts(contentWrap, messageToReplace.message);
            }
        }
    }, 1500); // Esperar typing effect
}
```

**¿Qué hace?**
- Espera 1.5s a que termine el efecto de typing
- Obtiene el último mensaje assistant
- Verifica si debe activar Sales Agent
- Inyecta grid de productos automáticamente

---

### 2. **sales-agent-component.blade.php** (~línea 34)

**Ubicación**: `app/Extensions/ChatbotSalesAgent/resources/views/sales-agent-component.blade.php`

**Cambio**: Mejorada detección de contexto comercial

```javascript
shouldEnhanceResponse(message) {
    // ... código existente ...
    
    // ✨ NUEVO: Frases de contexto comercial
    const commercialPhrases = [
        'te recomiendo',
        'tenemos disponible',
        'contamos con',
        'puedes adquirir',
        'puedes comprar',
        'está en',
        'cuesta',
        'precio de',
        'valor de',
        'te ofrecemos',
        'ideal para',
        'perfecto para'
    ];
    
    const hasCommercialContext = commercialPhrases.some(phrase => 
        lowerMessage.includes(phrase)
    );
    
    // ✨ NUEVO: Lógica mejorada
    const shouldEnhance = (hasKeyword && mentionsProduct) || 
                          (hasCommercialContext && mentionsProduct);
    
    return shouldEnhance;
}
```

**¿Qué hace?**
- Detecta keywords configurables (comprar, precio, producto, etc.)
- Detecta nombres de productos en la respuesta AI
- Detecta frases de contexto comercial
- Activa Sales Agent si: (keywords + productos) O (contexto comercial + productos)

---

## 🎮 Cómo Funciona

### **Escenario 1: Usuario pregunta por producto**

```
Usuario: "¿Tienen aceite de oliva?"
   ↓
AI: "Sí, tenemos Aceite de Oliva Extra Virgen orgánico por $35.000..."
   ↓
Orquestador detecta:
   - ✅ Keyword: "oliva" (nombre de producto)
   - ✅ Contexto: "tenemos" (frase comercial)
   - ✅ Producto existe en BD
   ↓
Result: Muestra mensaje AI + Grid con tarjeta de producto
```

### **Escenario 2: Conversación normal**

```
Usuario: "¿Cómo están?"
   ↓
AI: "Muy bien, gracias por preguntar. ¿En qué puedo ayudarte?"
   ↓
Orquestador detecta:
   - ❌ No menciona productos
   - ❌ No hay contexto comercial
   ↓
Result: Solo muestra mensaje AI (no activa Sales Agent)
```

### **Escenario 3: Durante flujo de compra**

```
Usuario seleccionó producto, está en paso de cantidad
   ↓
Usuario: "2"
   ↓
handlePurchaseMessage() intercepta el mensaje
   ↓
Result: NO se envía al AI, procesa localmente
```

---

## 🔍 Debugging

El orquestador tiene logs detallados en consola:

```javascript
// Al evaluar
🎯 Orquestador: Evaluando si activar Sales Agent...

// Si detecta productos
✅ Orquestador: Activando Sales Agent - productos detectados!
🔍 Sales Agent: Checking if should enhance response: ...
   - Enabled: true
   - Products loaded: true
   - Keywords: ["comprar", "precio", ...]
   - Has keyword: true
   - Mentions product: true
   - Has commercial context: true
   ✅ Should enhance: true
🎨 Sales Agent: enhanceMessageWithProducts called
   ✅ Enhancing with 2 products
   ✅ Product cards injected successfully!

// Si no detecta productos
ℹ️ Orquestador: No se detectaron productos relevantes en esta respuesta
```

---

## ✅ Verificación Pre-Deploy

Antes de desplegar, verifica:

1. **Sales Agent habilitado en BD**:
```sql
SELECT id, name, sales_agent_enabled, woocommerce_enabled 
FROM ext_chatbots 
WHERE id = [TU_CHATBOT_ID];
```

2. **Productos sincronizados**:
```sql
SELECT COUNT(*) FROM ext_chatbot_products 
WHERE chatbot_id = [TU_CHATBOT_ID] 
AND in_stock = 1;
```

3. **Keywords configuradas**:
```sql
SELECT sales_agent_keywords FROM ext_chatbots 
WHERE id = [TU_CHATBOT_ID];
```

---

## 🧪 Pruebas Recomendadas

### Test 1: Pregunta directa por producto
```
Usuario: "¿Tienen aceite de coco?"
Esperado: Mensaje AI + Grid con productos de aceite de coco
```

### Test 2: Pregunta genérica
```
Usuario: "¿Qué productos tienen?"
Esperado: Mensaje AI listando productos + Grid con top productos
```

### Test 3: Conversación normal
```
Usuario: "Hola, ¿cómo están?"
Esperado: Solo mensaje AI, NO grid (no activa SA)
```

### Test 4: Flujo de compra completo
```
1. Usuario: "Quiero comprar aceite de coco"
2. AI responde + Grid aparece
3. Usuario click "🛒 Comprar"
4. SA: "¿Cuántas unidades?"
5. Usuario: "2"
6. SA: "¿Cuál es tu nombre?" ...
7. [Completa todos los pasos]
8. SA genera orden en WooCommerce
9. SA genera payment link en Wompi
10. Usuario click → paga en Wompi
```

---

## 🚨 Rollback

Si algo falla, ejecuta:

```bash
./rollback-sales-agent-orchestrator.sh
```

Esto restaurará los archivos desde los backups creados automáticamente.

---

## 📊 Métricas de Éxito

- **Conversiones mejoradas**: El grid visual aumenta la probabilidad de compra
- **UX fluida**: No interrumpe la conversación natural
- **Detección inteligente**: Solo se activa cuando es relevante
- **Backend sin cambios**: Usa el AI actual sin modificaciones

---

## 🔐 Seguridad

- ✅ Validación de entrada en flujo de compra
- ✅ CSRF tokens en requests
- ✅ Sanitización de HTML en productos
- ✅ Verificación de stock antes de crear orden
- ✅ Integración segura con Wompi

---

## 🎯 Próximos Pasos (Opcionales)

1. **Analytics**: Trackear cuántas veces se activa el SA
2. **A/B Testing**: Comparar conversiones con/sin grid
3. **ML**: Mejorar detección con modelo de intención
4. **Recomendaciones**: "Productos relacionados"
5. **Descuentos**: Cupones en el chat

---

## 📞 Soporte

Si hay problemas:
1. Revisar logs en consola del navegador
2. Verificar configuración en BD (`sales_agent_enabled`, `woocommerce_enabled`)
3. Verificar que productos tengan `in_stock = 1`
4. Hacer rollback si es necesario

---

**Fecha de implementación**: 18 de Octubre, 2025
**Versión**: 1.0
**Estado**: ✅ Implementado y listo para pruebas




