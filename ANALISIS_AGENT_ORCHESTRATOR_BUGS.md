# 🐛 Análisis de Bugs - AgentOrchestratorService

## Problemas Identificados

### Problema 1: Agents no se activan (agents_activated: [])

**Ubicación**: `AgentOrchestratorService::orchestrate()`

**Causa Raíz**:
1. El método `getActiveAgents()` puede retornar colección vacía si no hay agentes creados en BD
2. El método `shouldActivate()` requiere triggers/keywords configurados correctamente
3. No hay agente "external" siempre activo por defecto
4. La lógica de detección de intención es muy básica

**Evidencia**:
- Query a BD muestra que no hay agentes creados
- El método `shouldActivate()` retorna `false` si no hay triggers configurados
- No hay fallback para activar agentes básicos

**Solución Propuesta**:
1. Crear agente "external" por defecto si no existe
2. Mejorar lógica de `shouldActivate()` con detección más inteligente
3. Agregar logging detallado para debugging
4. Implementar sistema de intención con NLP mejorado

---

### Problema 2: URLs de productos incorrectas

**Ubicación**: `AgentOrchestratorService::formatProduct()`

**Causa Raíz**:
- El método busca URLs en embeddings pero puede fallar
- Prioridad de URLs no está bien definida
- URLs del home (`https://www.aliviate.com.co/`) se están usando como fallback

**Solución Propuesta**:
1. Mejorar lógica de búsqueda de URLs en embeddings
2. Validar URLs antes de retornarlas
3. No usar URLs del home como fallback
4. Agregar validación de URLs válidas

---

### Problema 3: Falta contexto de conversación

**Problema**:
- El orchestrator no mantiene contexto entre mensajes
- No recuerda conversaciones anteriores
- No puede hacer seguimiento de intenciones a lo largo de la conversación

**Solución Propuesta**:
1. Agregar parámetro `$context` al método `orchestrate()`
2. Incluir historial de mensajes recientes
3. Analizar patrones de conversación
4. Mantener estado de intención detectada

---

### Problema 4: Falta sistema de priorización inteligente

**Problema**:
- Los agentes se procesan en orden de prioridad estática
- No hay evaluación dinámica de qué agente es más relevante
- No hay sistema de scoring de relevancia

**Solución Propuesta**:
1. Implementar sistema de scoring por agente
2. Evaluar relevancia según contexto
3. Activar múltiples agentes si es necesario
4. Priorizar según confianza de detección

---

## Plan de Refactorización

### Paso 1: Mejorar AgentOrchestratorService
- [ ] Agregar logging detallado
- [ ] Mejorar detección de intención
- [ ] Agregar contexto de conversación
- [ ] Implementar sistema de scoring

### Paso 2: Mejorar ChatbotAgent::shouldActivate()
- [ ] Mejorar detección de keywords
- [ ] Agregar detección de intención más inteligente
- [ ] Agregar soporte para "always_active" por defecto para external

### Paso 3: Crear AgentIntelligenceService
- [ ] Servicio dedicado para análisis de intención
- [ ] Usar embeddings para mejor comprensión
- [ ] Scoring de relevancia por agente

### Paso 4: Mejorar formatProduct()
- [ ] Validar URLs correctamente
- [ ] Mejorar búsqueda en embeddings
- [ ] No usar URLs del home como fallback

---

## Tests Necesarios

1. Test: AgentOrchestrator activa agentes correctamente
2. Test: External agent siempre activo
3. Test: Sales agent se activa con keywords comerciales
4. Test: URLs de productos se formatean correctamente
5. Test: Contexto de conversación se mantiene



