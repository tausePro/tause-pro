# 🎯 Plan de Implementación: Tab "Agents" 

## 📋 Análisis de Problemas Actuales

### ❌ **Problema 1: Tab Triggers**
- **Ubicación:** `edit-step-triggers.blade.php` (Step 4)
- **Problemas identificados:**
  1. No interactúa correctamente con la burbuja de chat del preview
  2. Se renderiza "encima" en lugar de integrarse
  3. GDPR no muestra botón de aceptar
  4. "Advance Customization" no funciona

### ❌ **Problema 2: Sistema de Steps**
```php
// StepEnum.php - Solo 6 steps definidos
case configure = 'configure';  // Step 1
case customize = 'customize';  // Step 2
case train = 'train';          // Step 3
case triggers = 'triggers';    // Step 4
case embed = 'embed';          // Step 5
case channel = 'channel';      // Step 6
```

**Cálculo de progreso:**
```javascript
width: editingStep * {{ existChannels() ? 16.66 : 20 }} + '%'
```
- Con channels: 6 steps × 16.66% = 100%
- Sin channels: 5 steps × 20% = 100%

## ✅ Solución Profesional

### **Fase 1: Corregir Problemas Existentes** ⚠️ CRÍTICO

#### 1.1 Analizar por qué Triggers no funciona bien
```bash
# Archivos a revisar:
- edit-step-triggers.blade.php (línea 4: data-step="4")
- frontend-ui-preview.blade.php (preview container)
- edit-window.blade.php (línea 138: comentado x-show)
```

**Hipótesis del problema:**
- El preview está comentado para step 3 (train)
- Triggers (step 4) no tiene condiciones especiales
- Puede estar renderizándose sobre el preview en lugar de al lado

#### 1.2 Verificar estructura del preview
```html
<!-- Estructura actual -->
<div class="container flex flex-wrap justify-between gap-y-5">
    <!-- Options Container (izquierda) -->
    <div class="lqd-chatbot-edit-window-options w-full lg:w-[430px]">
        <!-- Steps 1-6 aquí -->
    </div>
    
    <!-- Preview Container (derecha) -->
    <div class="hidden w-full lg:grid lg:w-1/2 lg:py-16">
        <!-- Preview del chatbot -->
    </div>
</div>
```

**Problema potencial:** El preview está siempre visible, pero los steps pueden estar ocultándolo.

### **Fase 2: Agregar Tab "Agents" Correctamente**

#### 2.1 Actualizar StepEnum
```php
// app/Extensions/Chatbot/System/Enums/StepEnum.php
enum StepEnum: string
{
    case configure = 'configure';  // 1
    case customize = 'customize';  // 2
    case train = 'train';          // 3
    case triggers = 'triggers';    // 4
    case agents = 'agents';        // 5 ← NUEVO
    case embed = 'embed';          // 6
    case channel = 'channel';      // 7
}
```

#### 2.2 Actualizar cálculo de progreso
```javascript
// Antes: 6 steps
width: editingStep * {{ existChannels() ? 16.66 : 20 }} + '%'

// Después: 7 steps
width: editingStep * {{ existChannels() ? 14.28 : 16.66 }} + '%'
// 7 steps con channels: 100 / 7 = 14.28%
// 6 steps sin channels: 100 / 6 = 16.66%
```

#### 2.3 Crear vista del step
```bash
# Crear archivo
app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-agents.blade.php
```

#### 2.4 Incluir en edit-window.blade.php
```php
@include('chatbot::home.edit-window.edit-steps.edit-step-configure')
@include('chatbot::home.edit-window.edit-steps.edit-step-customize')
@include('chatbot::home.edit-window.edit-steps.edit-step-train')
@include('chatbot::home.edit-window.edit-steps.edit-step-triggers')
@include('chatbot::home.edit-window.edit-steps.edit-step-agents')  // ← NUEVO
@include('chatbot::home.edit-window.edit-steps.edit-step-embed')
@include('chatbot::home.edit-window.edit-steps.edit-step-channel')
```

### **Fase 3: Implementación del Tab Agents**

#### 3.1 Estructura de la vista
```blade
{{-- Editing Step 5 - Agents --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="5"
    x-show="editingStep === 5"
    x-transition:enter-start="opacity-0 -translate-x-3"
    x-transition:enter-end="opacity-100 translate-x-0"
    x-transition:leave-start="opacity-100 translate-x-0"
    x-transition:leave-end="opacity-0 translate-x-3"
>
    <h2 class="mb-3.5">@lang('AI Agents')</h2>
    <p class="text-xs/5 opacity-60 lg:max-w-[360px]">
        @lang('Configure specialized AI agents to handle different types of conversations.')
    </p>

    <div class="flex flex-col gap-5 pt-9" x-data="agentsManager()">
        <!-- Lista de agentes -->
    </div>
</div>
```

#### 3.2 Componente Alpine.js
```javascript
function agentsManager() {
    return {
        agents: [],
        
        init() {
            this.loadAgents();
        },
        
        async loadAgents() {
            const chatbotId = Alpine.store('chatbot').activeChatbot?.id;
            if (!chatbotId || chatbotId === 'new_chatbot') {
                this.agents = this.getDefaultAgents();
                return;
            }
            
            try {
                const response = await fetch(`/dashboard/chatbot/${chatbotId}/agents`);
                if (response.ok) {
                    const data = await response.json();
                    this.agents = data.agents || this.getDefaultAgents();
                }
            } catch (error) {
                console.error('Failed to load agents:', error);
                this.agents = this.getDefaultAgents();
            }
        },
        
        getDefaultAgents() {
            return [
                {
                    type: 'external',
                    name: 'External Chatbot',
                    description: 'Conversational AI with embeddings and knowledge base',
                    enabled: true,
                    priority: 5,
                    icon: 'tabler-message-chatbot'
                },
                {
                    type: 'sales',
                    name: 'Sales Agent',
                    description: 'Product recommendations and purchase assistance',
                    enabled: false,
                    priority: 8,
                    icon: 'tabler-shopping-cart',
                    config: {
                        woocommerce_enabled: false,
                        wompi_enabled: false
                    }
                },
                {
                    type: 'support',
                    name: 'Support Agent',
                    description: 'Technical support and troubleshooting',
                    enabled: false,
                    priority: 7,
                    icon: 'tabler-headset'
                },
                {
                    type: 'appointment',
                    name: 'Appointment Agent',
                    description: 'Schedule meetings and appointments',
                    enabled: false,
                    priority: 6,
                    icon: 'tabler-calendar'
                }
            ];
        },
        
        async saveAgents() {
            const chatbotId = Alpine.store('chatbot').activeChatbot?.id;
            if (!chatbotId) {
                toastr.error('Please save the chatbot first');
                return;
            }
            
            Alpine.store('chatbot').submittingData = true;
            
            try {
                const response = await fetch(`/dashboard/chatbot/${chatbotId}/agents`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ agents: this.agents })
                });
                
                if (response.ok) {
                    toastr.success('Agents configuration saved!');
                } else {
                    toastr.error('Failed to save agents');
                }
            } catch (error) {
                console.error('Failed to save agents:', error);
                toastr.error('Failed to save agents');
            } finally {
                Alpine.store('chatbot').submittingData = false;
            }
        }
    };
}
```

### **Fase 4: Backend - Controlador y Rutas**

#### 4.1 Crear métodos en ChatbotController
```php
// app/Extensions/Chatbot/System/Http/Controllers/ChatbotController.php

public function getAgents(Chatbot $chatbot): JsonResponse
{
    $agents = ChatbotAgent::where('chatbot_id', $chatbot->id)
        ->orderBy('priority', 'desc')
        ->get()
        ->map(function ($agent) {
            return [
                'type' => $agent->agent_type,
                'name' => $agent->name,
                'description' => $agent->description,
                'enabled' => $agent->is_enabled,
                'priority' => $agent->priority,
                'config' => $agent->configuration,
            ];
        });
    
    return response()->json(['agents' => $agents]);
}

public function saveAgents(Request $request, Chatbot $chatbot): JsonResponse
{
    $validated = $request->validate([
        'agents' => 'required|array',
        'agents.*.type' => 'required|string',
        'agents.*.enabled' => 'required|boolean',
        'agents.*.priority' => 'required|integer|min:1|max:10',
        'agents.*.config' => 'nullable|array',
    ]);
    
    foreach ($validated['agents'] as $agentData) {
        ChatbotAgent::updateOrCreate(
            [
                'chatbot_id' => $chatbot->id,
                'agent_type' => $agentData['type'],
            ],
            [
                'is_enabled' => $agentData['enabled'],
                'priority' => $agentData['priority'],
                'configuration' => $agentData['config'] ?? [],
            ]
        );
    }
    
    return response()->json(['success' => true]);
}
```

#### 4.2 Agregar rutas
```php
// En ChatbotServiceProvider o routes
Route::get('{chatbot}/agents', [ChatbotController::class, 'getAgents'])->name('agents.get');
Route::post('{chatbot}/agents', [ChatbotController::class, 'saveAgents'])->name('agents.save');
```

## 🚨 Precauciones y Validaciones

### ✅ Checklist Pre-Implementación
- [ ] Verificar que todos los steps actuales funcionan correctamente
- [ ] Corregir problema de Triggers antes de agregar Agents
- [ ] Probar cálculo de progreso con 7 steps
- [ ] Verificar que el preview se mantiene visible
- [ ] Probar navegación entre steps
- [ ] Verificar que GDPR funciona en todos los steps

### ✅ Checklist Post-Implementación
- [ ] Tab Agents aparece en la posición correcta (step 5)
- [ ] Navegación funciona (anterior/siguiente)
- [ ] Preview del chatbot se mantiene visible
- [ ] Guardar configuración funciona
- [ ] Cargar configuración funciona
- [ ] Agentes se activan/desactivan correctamente
- [ ] Backend guarda en `ext_chatbot_agents`
- [ ] Orquestador usa la configuración guardada

## 📊 Orden de Implementación

1. **PRIMERO:** Diagnosticar y corregir problema de Triggers
2. **SEGUNDO:** Actualizar StepEnum y cálculo de progreso
3. **TERCERO:** Crear vista edit-step-agents.blade.php
4. **CUARTO:** Implementar backend (controlador y rutas)
5. **QUINTO:** Probar exhaustivamente
6. **SEXTO:** Limpiar código del Sales Agent Component

## 🎯 Resultado Final

- ✅ 7 steps en el wizard (o 6 sin channels)
- ✅ Tab "Agents" funcional y profesional
- ✅ Configuración guardada en BD
- ✅ Orquestador usando configuración
- ✅ Frontend limpio sin JavaScript complejo
- ✅ Base sólida para agregar más agentes

---

**¿Procedemos paso a paso con máxima precaución?**
