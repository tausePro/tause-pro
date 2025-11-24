# Análisis Arquitectónico: Sales Agent - External Chatbot

## 📋 Resumen Ejecutivo

**Estado Actual**: Sistema parcialmente roto con arquitectura duplicada y conflictiva
**Problema Principal**: Duplicación de servicios de orquestación que rompió el flujo original funcional
**Impacto**: Sales Agent no funciona en web, links incorrectos, triggers rotos

---

## 🏗️ Arquitectura Original (Funcionaba)

### Flujo Web (External Chatbot)
```
Usuario → ChatbotApplicationController::storeMessage()
  → GeneratorService (embeddings)
  → Respuesta AI con links de productos (product_url directo)
  → Frontend muestra productos con links limpios
```

### Flujo WhatsApp
```
WhatsApp → EvolutionConversationService::processMessage()
  → GeneratorService (embeddings)
  → ProductOrchestratorService::orchestrate() 
    → Detecta productos mencionados
    → Retorna productos estructurados
  → WhatsAppPurchaseFlowService (flujo paso a paso)
    → Recolecta datos del usuario
    → WooCommerceService::createOrder()
    → WompiService::generatePaymentLink()
```

### Servicios Originales
1. **ProductOrchestratorService** (204 líneas)
   - Detecta productos mencionados en respuesta AI
   - Busca productos en BD
   - Retorna productos estructurados
   - ✅ Funcionaba correctamente

2. **WhatsAppPurchaseFlowService** (500 líneas)
   - Maneja estados de compra (idle, selecting_product, asking_quantity, etc.)
   - Recolecta datos paso a paso
   - Crea orden en WooCommerce
   - Genera link de pago Wompi
   - ✅ Funcionaba correctamente

3. **WooCommerceService** (578 líneas)
   - Sincroniza productos desde WooCommerce
   - Crea órdenes
   - Genera cupones dinámicos
   - ✅ Funcionaba correctamente

4. **WompiService** (377 líneas)
   - Genera links de pago
   - Maneja webhooks de pago
   - ✅ Funcionaba correctamente

---

## 🔴 Arquitectura Actual (Rota)

### Flujo Web (External Chatbot) - ACTUAL
```
Usuario → ChatbotApplicationController::storeMessage()
  → GeneratorService (embeddings)
  → AgentOrchestratorService::orchestrate() ❌ NUEVO/DUPLICADO
    → Busca agentes en ext_chatbot_agents
    → processSalesAgent()
      → findMentionedProducts() (duplicado de ProductOrchestratorService)
      → formatProduct() (duplicado de ProductCardService)
  → Retorna orchestration pero frontend no lo procesa correctamente
  → Links apuntan al home en lugar de product_url
```

### Problemas Identificados

#### 1. Duplicación de Servicios
- ❌ **AgentOrchestratorService** (261 líneas) - NUEVO, duplica funcionalidad
- ❌ **ProductOrchestratorService** - Ahora usado por dos sistemas diferentes
- ❌ Lógica duplicada en `findMentionedProducts()` y `formatProduct()`

#### 2. Conflicto de Responsabilidades
- `AgentOrchestratorService` intenta hacer lo que ya hacía `ProductOrchestratorService`
- `ProductCardService` tiene lógica de formateo duplicada
- `ProductIntegrationService` tiene búsquedas duplicadas

#### 3. Problemas Específicos

**A. Links Incorrectos**
- Frontend recibe `product_url` pero lo muestra como `https://www.aliviate.com.co/` (home)
- El problema está en cómo se formatea/envía la respuesta

**B. Sales Agent No Se Activa**
- Logs muestran: `"agents_activated":[]` 
- El agente existe en BD pero no se activa correctamente
- `shouldActivate()` puede estar fallando

**C. Triggers Rotos**
- No se menciona en el código actual cómo se manejan
- Probablemente se rompió al cambiar la estructura

**D. Embeddings con Productos**
- Se mencionó que se añadieron productos a embeddings del external chatbot
- Esto puede estar causando respuestas confusas

---

## 🔍 Análisis de Archivos Clave

### ChatbotApplicationController.php
**Línea 496-499**: Llama a `AgentOrchestratorService` cuando `sales_agent_enabled = true`
```php
if ($chatbot->sales_agent_enabled) {
    $orchestrator = app(AgentOrchestratorService::class);
    $orchestration = $orchestrator->orchestrate(...);
}
```

**Problema**: 
- El `AgentOrchestratorService` busca agentes en `ext_chatbot_agents`
- Pero el flujo original NO necesitaba esta tabla
- El Sales Agent funcionaba directamente con `sales_agent_enabled` flag

### AgentOrchestratorService.php
**Línea 32-60**: `findMentionedProducts()` - Duplica lógica de `ProductOrchestratorService`
**Línea 62-80**: `formatProduct()` - Duplica lógica de `ProductCardService`

**Problema**: 
- Código duplicado = mantenimiento difícil
- Si se corrige uno, el otro queda roto
- No hay single source of truth

### ProductOrchestratorService.php
**Usado por**:
- ✅ WhatsApp (EvolutionConversationService) - FUNCIONA
- ❌ Web (ChatbotApplicationController) - NO SE USA (reemplazado por AgentOrchestratorService)

**Problema**: 
- El servicio original funciona pero no se usa en web
- Se creó un duplicado innecesario

---

## ✅ Solución Propuesta

### Opción 1: Restaurar Arquitectura Original (RECOMENDADA)

**Principio**: Si funcionaba antes, restaurar y mejorar, no reemplazar

#### Pasos:

1. **Eliminar AgentOrchestratorService**
   - Es una duplicación innecesaria
   - El flujo original no lo necesitaba

2. **Restaurar uso de ProductOrchestratorService en Web**
   ```php
   // En ChatbotApplicationController::storeMessage()
   if ($chatbot->sales_agent_enabled) {
       $productOrchestrator = app(ProductOrchestratorService::class);
       $orchestration = $productOrchestrator->orchestrate(
           chatbot: $chatbot,
           aiResponse: $messageToUser,
           userQuery: $request->validated('prompt')
       );
   }
   ```

3. **Corregir ProductCardService**
   - Asegurar que `product_url` se envía correctamente
   - Verificar formato de respuesta JSON

4. **Verificar Frontend**
   - Asegurar que procesa `orchestration.products` correctamente
   - Verificar que usa `product_url` y no `purchase_url`

5. **Restaurar Triggers**
   - Revisar cómo funcionaban antes
   - Verificar que no se rompieron con los cambios

### Opción 2: Unificar Arquitectura (Si se quiere mantener AgentOrchestratorService)

**Principio**: Si queremos mantener la nueva estructura, unificar correctamente

#### Pasos:

1. **AgentOrchestratorService debe usar ProductOrchestratorService**
   ```php
   // En AgentOrchestratorService::processSalesAgent()
   $productOrchestrator = app(ProductOrchestratorService::class);
   $products = $productOrchestrator->orchestrate(...);
   ```

2. **Eliminar código duplicado**
   - Eliminar `findMentionedProducts()` de AgentOrchestratorService
   - Eliminar `formatProduct()` de AgentOrchestratorService
   - Usar ProductCardService para formateo

3. **Mantener tabla ext_chatbot_agents solo para configuración**
   - No para lógica de búsqueda
   - Solo para configuración de triggers/keywords

---

## 🎯 Recomendación Final

**OPCIÓN 1: Restaurar Arquitectura Original**

**Razones**:
1. ✅ Funcionaba perfectamente antes
2. ✅ Menos código = menos bugs
3. ✅ Ya está probado y funcionando en WhatsApp
4. ✅ No necesita tabla adicional (ext_chatbot_agents) para funcionar
5. ✅ Más simple de mantener

**Plan de Acción**:
1. Eliminar `AgentOrchestratorService`
2. Restaurar uso de `ProductOrchestratorService` en `ChatbotApplicationController`
3. Corregir `ProductCardService` para asegurar URLs correctas
4. Verificar que frontend procesa productos correctamente
5. Restaurar triggers si es necesario
6. Remover productos de embeddings si están causando problemas

---

## 📊 Comparación de Arquitecturas

| Aspecto | Original (Funcionaba) | Actual (Rota) |
|---------|----------------------|---------------|
| Servicios | ProductOrchestratorService | AgentOrchestratorService + ProductOrchestratorService |
| Líneas de código | ~204 | ~465 (duplicado) |
| Tablas necesarias | ext_chatbots (sales_agent_enabled) | ext_chatbots + ext_chatbot_agents |
| Complejidad | Baja | Media-Alta |
| Funciona en WhatsApp | ✅ Sí | ✅ Sí (usa original) |
| Funciona en Web | ✅ Sí | ❌ No |
| Links correctos | ✅ Sí | ❌ No |
| Triggers | ✅ Funcionaban | ❌ Rotos |

---

## 🔧 Archivos a Modificar

### Eliminar:
- `app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php`
- `app/Extensions/Chatbot/System/Models/ChatbotAgent.php` (si solo se usa para AgentOrchestratorService)

### Modificar:
- `app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotApplicationController.php`
  - Línea 17: Eliminar `use AgentOrchestratorService`
  - Línea 496-510: Restaurar uso de `ProductOrchestratorService`
  
- `app/Extensions/Chatbot/System/Services/ProductCardService.php`
  - Verificar que `product_url` se envía correctamente
  - Asegurar formato correcto de respuesta

### Verificar:
- Frontend JavaScript que procesa `orchestration.products`
- Triggers y cómo se manejan
- Embeddings (remover productos si están causando problemas)

---

## 📝 Notas Importantes

1. **No tocar WhatsApp**: El flujo de WhatsApp funciona correctamente con `ProductOrchestratorService` y `WhatsAppPurchaseFlowService`. NO MODIFICAR.

2. **Backup antes de cambios**: Crear backup completo antes de eliminar archivos.

3. **Testing**: Probar cada cambio individualmente:
   - Primero restaurar ProductOrchestratorService
   - Luego verificar links
   - Luego verificar triggers
   - Finalmente eliminar AgentOrchestratorService

4. **Migración de datos**: Si hay datos en `ext_chatbot_agents`, migrarlos a configuración del chatbot antes de eliminar.

---

## 🚀 Próximos Pasos

1. ✅ Crear este documento de análisis
2. ⏳ Revisar con usuario antes de proceder
3. ⏳ Crear backup completo
4. ⏳ Restaurar ProductOrchestratorService en ChatbotApplicationController
5. ⏳ Corregir ProductCardService
6. ⏳ Verificar frontend
7. ⏳ Restaurar triggers
8. ⏳ Eliminar AgentOrchestratorService
9. ⏳ Testing completo
10. ⏳ Deploy a producción

---

**Fecha de Análisis**: 2025-11-11
**Analista**: AI Assistant
**Estado**: Pendiente de Aprobación







