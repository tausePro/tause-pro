# 🎯 Plan de Implementación: Hub de Agentes

## 📋 Análisis Completo del Sistema

### 1. **Sistema de Extensiones y Marketplace**

#### ✅ Cómo Funciona:
- **Tabla**: `extensions` (modelo `Extension`)
- **Helper**: `MarketplaceHelper::isRegistered('slug')`
- **Verificación**: Chequea si el ServiceProvider está cargado
- **Uso**: Condicional en menús, vistas y funcionalidades

```php
// Ejemplo de uso en MenuService
'show_condition' => MarketplaceHelper::isRegistered('chatbot-voice'),
```

#### 🎯 Implicación para Agents:
- **NO necesitamos crear una extensión separada**
- Los agentes son **features dentro de Chatbot**
- Usaremos la tabla `ext_chatbot_agents` que ya creamos
- El pricing se maneja a nivel de plan del usuario

---

### 2. **Arquitectura del Wizard de Chatbot**

#### ✅ Steps Actuales (StepEnum):
1. `configure` - Configuración básica
2. `customize` - Personalización visual  
3. `train` - Entrenamiento con embeddings
4. `triggers` - Disparadores proactivos
5. `embed` - Código de integración
6. `channel` - Canales (condicional)

#### 🎯 Nuevo Step a Agregar:
**Position**: Entre `triggers` (4) y `embed` (5)
**Name**: `agents`
**File**: `edit-step-agents.blade.php`

---

### 3. **Estructura de un Step**

```blade
{{-- Editing Step X - Name --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="X"
    x-show="editingStep === X"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">@lang('Title')</h2>
    <p class="text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Description')
    </p>

    <div class="flex flex-col gap-5 pt-9">
        <!-- Content -->
    </div>
</div>
```

---

### 4. **Sistema de Pricing en TausePro**

#### ✅ Estructura Actual:
- **Planes**: Free, Starter, Pro, Enterprise
- **Features por Plan**: Definidos en `plans` table
- **Límites**: `plan_features` table
- **Verificación**: `PlanHelper::checkPlan()`

#### 🎯 Agentes y Pricing:
Los agentes NO son extensiones de pago separadas, son **features del plan**:

```
Free Plan:
  - External Chatbot ✅
  - Basic triggers ✅
  - NO agents ❌

Starter Plan:
  - External Chatbot ✅
  - Proactive triggers ✅
  - Sales Agent ✅ (1 agent)

Pro Plan:
  - External Chatbot ✅
  - Proactive triggers ✅
  - All Agents ✅ (unlimited)
  - Custom agents ✅

Enterprise:
  - Everything ✅
  - Custom development ✅
```

---

### 5. **Tipos de Agentes Disponibles**

#### 🤖 Agentes Predefinidos:

1. **Sales Agent** (Ventas)
   - Detecta intención de compra
   - Muestra productos
   - Integración WooCommerce/Wompi
   - Keywords: "comprar", "precio", "producto"

2. **Support Agent** (Soporte)
   - Responde preguntas técnicas
   - Acceso a knowledge base
   - Escalamiento a humano
   - Keywords: "ayuda", "problema", "error"

3. **Appointment Agent** (Citas)
   - Agenda citas
   - Integración calendario
   - Confirmaciones automáticas
   - Keywords: "cita", "agendar", "reservar"

4. **Lead Capture Agent** (Leads)
   - Captura información
   - Formularios dinámicos
   - CRM integration
   - Keywords: "contacto", "información"

#### 🎨 Agentes Personalizados:
- Solo en planes Pro/Enterprise
- Usuario define: nombre, descripción, keywords, comportamiento
- Usa el mismo motor de IA

---

### 6. **Diseño del Tab "Agents"**

#### 🎨 Layout:

```
┌─────────────────────────────────────────────────┐
│ Agents Hub                                       │
│ Configure AI agents to handle specific tasks    │
├─────────────────────────────────────────────────┤
│                                                  │
│ ┌─────────────────────────────────────────┐    │
│ │ 🤖 Sales Agent              [Toggle] ✅ │    │
│ │ Helps customers find and buy products   │    │
│ │                                          │    │
│ │ ▼ Configuration                          │    │
│ │   Keywords: comprar, precio, producto   │    │
│ │   Priority: High                         │    │
│ │   [Configure WooCommerce] [Configure    │    │
│ │                           Wompi]         │    │
│ └─────────────────────────────────────────┘    │
│                                                  │
│ ┌─────────────────────────────────────────┐    │
│ │ 💬 Support Agent            [Toggle] ⬜ │    │
│ │ Provides technical support              │    │
│ │ 🔒 Available in Pro plan                │    │
│ └─────────────────────────────────────────┘    │
│                                                  │
│ ┌─────────────────────────────────────────┐    │
│ │ 📅 Appointment Agent        [Toggle] ⬜ │    │
│ │ Schedules appointments                  │    │
│ │ 🔒 Available in Pro plan                │    │
│ └─────────────────────────────────────────┘    │
│                                                  │
│ ┌─────────────────────────────────────────┐    │
│ │ ➕ Add Custom Agent                      │    │
│ │ 🔒 Available in Pro plan                │    │
│ └─────────────────────────────────────────┘    │
└─────────────────────────────────────────────────┘
```

---

### 7. **Flujo de Orquestación**

```
User Message
     ↓
AgentOrchestratorService
     ↓
Analyze Intent & Keywords
     ↓
Match to Agent (by priority)
     ↓
Agent Handles Request
     ↓
     ├─ Sales Agent → ProductOrchestratorService
     ├─ Support Agent → Knowledge Base
     ├─ Appointment Agent → Calendar API
     └─ Default → General AI Response
```

---

### 8. **Archivos a Crear/Modificar**

#### ✅ Crear:
1. `app/Extensions/Chatbot/System/Enums/AgentTypeEnum.php`
2. `app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-agents.blade.php`
3. `app/Extensions/Chatbot/System/Http/Controllers/AgentController.php`
4. `app/Extensions/Chatbot/System/Http/Requests/AgentStoreRequest.php`

#### ✅ Modificar:
1. `app/Extensions/Chatbot/System/Enums/StepEnum.php` - Agregar `agents`
2. `app/Extensions/Chatbot/resources/views/home/edit-window/edit-window.blade.php` - Include nuevo step
3. `routes/panel.php` - Rutas para CRUD de agentes
4. `app/Extensions/Chatbot/System/Services/AgentOrchestratorService.php` - Ya existe, mejorar

---

### 9. **Rutas Necesarias**

```php
// En routes/panel.php bajo el grupo de chatbot
Route::prefix('chatbot/{chatbot}/agents')
    ->name('chatbot.agents.')
    ->group(function () {
        Route::get('/', [AgentController::class, 'index'])->name('index');
        Route::post('/', [AgentController::class, 'store'])->name('store');
        Route::put('/{agent}', [AgentController::class, 'update'])->name('update');
        Route::delete('/{agent}', [AgentController::class, 'destroy'])->name('destroy');
        Route::post('/{agent}/toggle', [AgentController::class, 'toggle'])->name('toggle');
    });
```

---

### 10. **Migración de Sales Agent**

#### ✅ Estado Actual:
- JavaScript en `sales-agent-component.blade.php`
- Configuración en campos del chatbot (wompi, woocommerce)
- No hay registro en `ext_chatbot_agents`

#### 🎯 Migración:
1. Crear seeder para migrar configs existentes
2. Mover lógica a `AgentOrchestratorService`
3. Mantener backward compatibility
4. Deprecar componente antiguo gradualmente

---

## 🚀 Plan de Implementación

### Fase 1: Estructura Base ✅
- [x] Crear tabla `ext_chatbot_agents`
- [x] Crear servicios de orquestación
- [ ] Agregar `agents` a `StepEnum`
- [ ] Crear vista `edit-step-agents.blade.php`

### Fase 2: CRUD de Agentes
- [ ] Crear `AgentController`
- [ ] Crear `AgentStoreRequest`
- [ ] Definir rutas
- [ ] Implementar toggle de agentes

### Fase 3: UI del Hub
- [ ] Diseñar cards de agentes
- [ ] Implementar configuración por agente
- [ ] Agregar validación de plan
- [ ] Mostrar badges de pricing

### Fase 4: Migración Sales Agent
- [ ] Crear seeder de migración
- [ ] Integrar con orquestador
- [ ] Testing
- [ ] Deprecar código antiguo

---

## 🎨 Estilos y Componentes

### Clases Tailwind a Usar:
```css
/* Cards */
.rounded-xl .border .border-border .p-4 .hover:border-primary/30

/* Badges */
.rounded-full .px-2 .py-0.5 .text-2xs .font-medium
.bg-blue-100 .text-blue-700 /* Universal */
.bg-purple-100 .text-purple-700 /* Industry */
.bg-green-100 .text-green-700 /* Custom */

/* Toggle Switch */
.peer .h-6 .w-11 .rounded-full .bg-gray-200
.peer-checked:bg-primary

/* Lock Icon (Pro feature) */
.text-yellow-600 .bg-yellow-50
```

---

## ✅ Checklist Final

- [ ] Mantener arquitectura existente
- [ ] Seguir convenciones de rutas (`dashboard.user.chatbot.agents.*`)
- [ ] Usar `MarketplaceHelper` para features condicionales
- [ ] Respetar sistema de pricing por planes
- [ ] Mantener diseño consistente con otros steps
- [ ] Documentar cambios en rutas y menú
- [ ] Testing en todos los planes
- [ ] Backward compatibility con Sales Agent actual
