# Investigación Profunda: Sales Agent - Cómo Funcionaba Antes

## 🔍 Hallazgos Clave

### 1. **Cómo Funcionaba ANTES (Sin ext_chatbot_agents)**

#### Flujo Original (3 de Noviembre - Funcionaba ✅)

```
Usuario envía mensaje
    ↓
ChatbotApplicationController::storeMessage()
    ↓
GeneratorService genera respuesta (con embeddings)
    ↓
SI sales_agent_enabled = true:
    ↓
    AgentOrchestratorService::orchestrate()
        ↓
        Busca agentes en ext_chatbot_agents
        ↓
        processSalesAgent()
            ↓
            findMentionedProducts() (DUPLICADO)
            ↓
            formatProduct() (DUPLICADO)
            ↓
            Retorna productos con product_url de BD
```

**PROBLEMA**: El código del 3 de noviembre YA tenía `AgentOrchestratorService`, pero funcionaba porque:
- El Sales Agent estaba creado en `ext_chatbot_agents`
- Los productos tenían `product_url` correcto en BD
- El frontend procesaba correctamente la respuesta

#### Flujo ANTES de ext_chatbot_agents (Octubre)

```
Usuario envía mensaje
    ↓
GeneratorService genera respuesta (con embeddings)
    ↓
SI sales_agent_enabled = true:
    ↓
    ProductOrchestratorService::orchestrate() DIRECTAMENTE
        ↓
        shouldActivateSalesAgent() (usa sales_agent_keywords del chatbot)
        ↓
        findMentionedProducts() (busca en BD de productos)
        ↓
        formatProduct() (usa product_url de BD)
        ↓
        Retorna productos estructurados
```

**DIFERENCIA CLAVE**: 
- ✅ ANTES: `ProductOrchestratorService` se llamaba DIRECTAMENTE
- ❌ AHORA: `AgentOrchestratorService` intermedia y duplica código

---

### 2. **Configuración de WooCommerce**

#### Ubicación Actual
```php
// Modelo Chatbot (ext_chatbots table)
$chatbot->woocommerce_url      // URL de WooCommerce
$chatbot->woocommerce_key      // Consumer Key
$chatbot->woocommerce_secret   // Consumer Secret
$chatbot->woocommerce_enabled  // Flag de habilitación
$chatbot->woocommerce_last_sync // Última sincronización
```

#### Uso Actual
- ✅ `WooCommerceService::syncProducts()` - Sincroniza productos desde WooCommerce
- ✅ `WooCommerceService::createOrder()` - Crea órdenes en WooCommerce
- ✅ Los productos se guardan en `ext_chatbot_products` con `product_url` de WooCommerce

#### Problema Identificado
- Los productos se sincronizan desde WooCommerce y se guardan en BD
- El `product_url` viene de WooCommerce durante la sincronización
- Pero el usuario quiere usar URLs de los **embeddings del website** en lugar de BD

---

### 3. **Links desde Embeddings (Propuesta del Usuario)**

#### Estructura de Embeddings
```php
// Modelo ChatbotEmbedding (ext_chatbot_embeddings table)
$embedding->url        // URL del contenido entrenado
$embedding->content   // Contenido del embedding
$embedding->type       // Tipo: website, file, text, qa
$embedding->title      // Título del contenido
```

#### Cómo Funciona Actualmente
1. Se entrenan páginas del website → se crean embeddings con `url`
2. Cuando el AI menciona un producto, busca en embeddings
3. El `KnowledgeBase` tool retorna contenido, pero NO retorna URLs directamente

#### Propuesta: Extraer URLs de Embeddings

**Cuando el AI menciona un producto:**
1. Buscar en embeddings que coincidan con el nombre del producto
2. Extraer la `url` del embedding más relevante
3. Usar esa URL en lugar de `product_url` de la BD

**Ventajas:**
- ✅ URLs siempre actualizadas (vienen del website entrenado)
- ✅ No depende de sincronización de WooCommerce
- ✅ Más simple: un solo origen de verdad (embeddings)

**Implementación Propuesta:**
```php
// En ProductOrchestratorService::formatProduct()
protected function formatProduct(ChatbotProduct $product, ?string $embeddingUrl = null): array
{
    return [
        'id' => $product->id,
        'name' => $product->name,
        // ... otros campos ...
        'product_url' => $embeddingUrl ?? $product->product_url ?? $product->purchase_url,
    ];
}

// Nuevo método para buscar URL en embeddings
protected function findProductUrlInEmbeddings(Chatbot $chatbot, string $productName): ?string
{
    $embeddings = $chatbot->embeddings()
        ->where('type', 'website') // Solo embeddings de website
        ->whereNotNull('url')
        ->get();
    
    $productNameLower = strtolower($productName);
    
    foreach ($embeddings as $embedding) {
        // Buscar si el título o contenido menciona el producto
        $title = strtolower($embedding->title ?? '');
        $url = $embedding->url;
        
        // Si el título contiene el nombre del producto, usar esa URL
        if (str_contains($title, $productNameLower) || 
            str_contains($productNameLower, $title)) {
            return $url;
        }
        
        // También buscar en el contenido si está disponible
        if ($embedding->content) {
            $content = strtolower($embedding->content);
            if (str_contains($content, $productNameLower)) {
                return $url;
            }
        }
    }
    
    return null; // No se encontró URL en embeddings
}
```

---

### 4. **Triggers - Estado Actual**

#### Cómo Funcionan los Triggers
```php
// ChatbotApplicationController::triggers()
$triggerService = app(ProactiveTriggerService::class);
$triggers = $triggerService->getActiveTriggers((string) $chatbot->id);
```

#### Estructura de Triggers
```php
// Modelo ChatbotTrigger (ext_chatbot_triggers table)
$trigger->chatbot_id
$trigger->trigger_type      // 'page_visit', 'time_delay', 'exit_intent', etc.
$trigger->trigger_config    // JSON con configuración
$trigger->message           // Mensaje a mostrar
$trigger->is_active          // Flag de activación
```

#### Problema Reportado
- ❌ Los triggers dejaron de funcionar después de los cambios
- Necesita investigación más profunda

#### Verificación Necesaria
1. ¿Los triggers están activos en BD?
2. ¿El frontend está llamando al endpoint `/triggers`?
3. ¿El `ProactiveTriggerService` está funcionando correctamente?

---

### 5. **Arquitectura Recomendada (Simplificada)**

#### Opción A: Restaurar Flujo Original (SIN AgentOrchestratorService)

```
Usuario → ChatbotApplicationController
    ↓
GeneratorService (embeddings)
    ↓
SI sales_agent_enabled:
    ↓
    ProductOrchestratorService::orchestrate() DIRECTAMENTE
        ↓
        Busca productos en BD
        ↓
        Busca URLs en embeddings (NUEVO)
        ↓
        Formatea productos con URLs de embeddings
        ↓
        Retorna productos estructurados
```

**Ventajas:**
- ✅ Más simple
- ✅ Menos código
- ✅ Funcionaba antes

**Desventajas:**
- ⚠️ No escala para múltiples agentes (pero podemos agregarlo después)

#### Opción B: Arreglar AgentOrchestratorService (MANTENER para futuro)

```
Usuario → ChatbotApplicationController
    ↓
GeneratorService (embeddings)
    ↓
SI sales_agent_enabled:
    ↓
    AgentOrchestratorService::orchestrate()
        ↓
        Busca agentes en ext_chatbot_agents
        ↓
        processSalesAgent()
            ↓
            ProductOrchestratorService::orchestrate() (DELEGAR, no duplicar)
                ↓
                Busca productos en BD
                ↓
                Busca URLs en embeddings (NUEVO)
                ↓
                Formatea productos
                ↓
                Retorna productos estructurados
```

**Ventajas:**
- ✅ Escalable para múltiples agentes
- ✅ Mantiene arquitectura planificada

**Desventajas:**
- ⚠️ Más complejo
- ⚠️ Requiere debugging

---

## 🎯 Recomendación Final

### Fase 1: Solución Inmediata (Restaurar Funcionalidad)

1. **Usar ProductOrchestratorService directamente** (como antes)
2. **Extraer URLs de embeddings** en lugar de BD
3. **Arreglar triggers** (investigar qué se rompió)

### Fase 2: Mejoras Arquitectónicas (Futuro)

1. **Unificar AgentOrchestratorService** para usar ProductOrchestratorService internamente
2. **Eliminar código duplicado**
3. **Preparar para múltiples agentes**

---

## 📋 Plan de Acción Inmediato

### 1. Restaurar ProductOrchestratorService Directo

**Archivo**: `ChatbotApplicationController.php`
```php
// ANTES (roto):
if ($chatbot->sales_agent_enabled) {
    $orchestrator = app(AgentOrchestratorService::class);
    $orchestration = $orchestrator->orchestrate(...);
}

// DESPUÉS (funcional):
if ($chatbot->sales_agent_enabled) {
    $productOrchestrator = app(ProductOrchestratorService::class);
    $orchestration = $productOrchestrator->orchestrate(
        chatbot: $chatbot,
        aiResponse: $messageToUser,
        userQuery: $request->validated('prompt')
    );
}
```

### 2. Extraer URLs de Embeddings

**Archivo**: `ProductOrchestratorService.php`
- Añadir método `findProductUrlInEmbeddings()`
- Modificar `formatProduct()` para usar URLs de embeddings primero

### 3. Investigar Triggers

- Verificar estado en BD
- Revisar `ProactiveTriggerService`
- Verificar frontend

---

## 🔍 Preguntas Pendientes

1. **¿Cómo se entrenan los productos en embeddings?**
   - ¿Se entrenan páginas individuales de productos?
   - ¿O solo se entrenan páginas generales del website?

2. **¿Los embeddings tienen estructura de productos?**
   - ¿O solo tienen contenido HTML/texto?

3. **¿Los triggers funcionaban antes del 3 de noviembre?**
   - ¿O se rompieron en otro momento?

---

**Fecha de Investigación**: 2025-11-11  
**Estado**: Pendiente de Implementación  
**Próximo Paso**: Implementar extracción de URLs desde embeddings







