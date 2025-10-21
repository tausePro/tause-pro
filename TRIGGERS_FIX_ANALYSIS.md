# 🔧 Análisis y Solución: Triggers Proactivos

## 🐛 Problema Identificado

### **Comportamiento Actual (INCORRECTO):**
Los triggers proactivos se muestran como un **popup/bubble flotante ENCIMA del chat** en lugar de aparecer como **mensajes DENTRO del chat**.

**Ubicación del código problemático:**
- `app/Extensions/Chatbot/resources/assets/js/proactive-triggers.js`
- Líneas 73-167: Método `showTrigger()`

### **Código Actual (Líneas 77-89):**
```javascript
// Create trigger bubble
const bubble = document.createElement('div');
bubble.className = 'lqd-ext-chatbot-proactive-trigger';
bubble.innerHTML = `
    <div class="lqd-ext-chatbot-proactive-trigger-content">
        <button class="lqd-ext-chatbot-proactive-trigger-close" onclick="this.parentElement.parentElement.remove()">
            <svg>...</svg>
        </button>
        <p>${trigger.message}</p>
    </div>
`;
```

**Estilos CSS (Líneas 96-108):**
```css
.lqd-ext-chatbot-proactive-trigger {
    position: fixed;        /* ← PROBLEMA: Fixed position */
    bottom: 130px;          /* ← PROBLEMA: Flotando sobre la página */
    right: 20px;
    max-width: 300px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.12);
    padding: 18px;
    z-index: 999998;        /* ← PROBLEMA: Z-index alto, encima de todo */
}
```

## ✅ Solución Correcta

### **Comportamiento Deseado:**
Los triggers deben inyectarse como **mensajes del asistente DENTRO del chat**, exactamente igual que si el bot enviara un mensaje normal.

### **Estrategia de Implementación:**

#### **Opción 1: Inyectar mensaje en el array de mensajes de Alpine.js** ⭐ RECOMENDADA
```javascript
showTrigger(trigger) {
    console.log('[Triggers] Showing:', trigger.type);
    this.shown[trigger.id] = true;

    // 1. Encontrar la instancia del chatbot
    const chatbotEl = document.querySelector('.lqd-ext-chatbot-window');
    if (!chatbotEl || !chatbotEl.__x || !chatbotEl.__x.$data) {
        console.error('[Triggers] Chatbot instance not found');
        return;
    }

    const chatbotData = chatbotEl.__x.$data;

    // 2. Crear mensaje del asistente
    const triggerMessage = {
        id: 'trigger_' + Date.now(),
        message: trigger.message,
        role: 'assistant',
        created_at: new Date().toISOString(),
        isProactiveTrigger: true  // Flag para identificarlo
    };

    // 3. Agregar al array de mensajes
    if (chatbotData.messages && Array.isArray(chatbotData.messages)) {
        chatbotData.messages.push(triggerMessage);
        
        // 4. Scroll al final
        setTimeout(() => {
            if (chatbotData.scrollMessagesToBottom) {
                chatbotData.scrollMessagesToBottom();
            }
        }, 100);
        
        console.log('[Triggers] Message injected into chat');
    }

    // 5. Abrir el chat si está cerrado
    const chatbotTrigger = document.querySelector('.lqd-ext-chatbot-trigger');
    if (chatbotTrigger && chatbotEl.dataset.windowState === 'close') {
        chatbotTrigger.click();
    }
}
```

#### **Opción 2: Usar la API del chatbot** (Más robusta)
```javascript
showTrigger(trigger) {
    console.log('[Triggers] Showing:', trigger.type);
    this.shown[trigger.id] = true;

    // Enviar mensaje proactivo a través de la API
    const apiUrl = `${this.chatbotHost}/api/v2/chatbot/${this.chatbotUuid}/proactive-message`;
    
    fetch(apiUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            message: trigger.message,
            trigger_type: trigger.type
        })
    })
    .then(response => response.json())
    .then(data => {
        // El backend retorna el mensaje formateado
        // El chatbot lo recibe vía WebSocket o polling
        console.log('[Triggers] Proactive message sent');
    })
    .catch(error => {
        console.error('[Triggers] Failed to send proactive message:', error);
    });
}
```

## 🎯 Plan de Implementación

### **Fase 1: Corregir showTrigger() - Opción 1 (Más rápida)**

1. **Modificar `proactive-triggers.js`:**
   - Eliminar creación de bubble flotante
   - Inyectar mensaje directamente en el array de mensajes
   - Abrir chat automáticamente si está cerrado

2. **Eliminar estilos CSS innecesarios:**
   - Remover `.lqd-ext-chatbot-proactive-trigger`
   - Remover animaciones `slideInUp`

3. **Probar:**
   - Trigger aparece como mensaje del bot
   - Chat se abre automáticamente
   - Mensaje se ve igual que otros mensajes del asistente

### **Fase 2: Mejorar UI del Dashboard**

1. **Agregar funcionalidad de crear triggers personalizados:**
```blade
{{-- En edit-step-triggers.blade.php --}}

{{-- Botón para agregar nuevo trigger --}}
<x-button
    class="w-full"
    variant="outline"
    @click.prevent="addCustomTrigger()"
>
    <x-tabler-plus class="size-4" />
    @lang('Add Custom Trigger')
</x-button>

{{-- Modal para crear trigger --}}
<div x-show="showAddTriggerModal" x-cloak>
    <div class="fixed inset-0 z-50 bg-black/50" @click="showAddTriggerModal = false"></div>
    <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="w-full max-w-md rounded-xl bg-background p-6 shadow-xl">
            <h3 class="mb-4 text-lg font-semibold">@lang('Create Custom Trigger')</h3>
            
            <div class="space-y-4">
                <div>
                    <label class="mb-1.5 block text-xs font-medium">@lang('Trigger Name')</label>
                    <input type="text" x-model="newTrigger.name" class="w-full rounded-lg border px-3 py-2">
                </div>
                
                <div>
                    <label class="mb-1.5 block text-xs font-medium">@lang('Trigger Type')</label>
                    <select x-model="newTrigger.type" class="w-full rounded-lg border px-3 py-2">
                        <option value="time_based">@lang('Time Based')</option>
                        <option value="scroll_based">@lang('Scroll Based')</option>
                        <option value="exit_intent">@lang('Exit Intent')</option>
                        <option value="page_specific">@lang('Page Specific')</option>
                    </select>
                </div>
                
                <div>
                    <label class="mb-1.5 block text-xs font-medium">@lang('Message')</label>
                    <textarea x-model="newTrigger.message" rows="3" class="w-full rounded-lg border px-3 py-2"></textarea>
                </div>
                
                <div>
                    <label class="mb-1.5 block text-xs font-medium">@lang('Delay (seconds)')</label>
                    <input type="number" x-model="newTrigger.delay" min="0" class="w-full rounded-lg border px-3 py-2">
                </div>
            </div>
            
            <div class="mt-6 flex gap-3">
                <x-button variant="outline" @click="showAddTriggerModal = false" class="flex-1">
                    @lang('Cancel')
                </x-button>
                <x-button @click="saveCustomTrigger()" class="flex-1">
                    @lang('Create Trigger')
                </x-button>
            </div>
        </div>
    </div>
</div>
```

2. **Agregar métodos en triggersManager():**
```javascript
function triggersManager() {
    return {
        triggers: [],
        showAddTriggerModal: false,
        newTrigger: {
            name: '',
            type: 'time_based',
            message: '',
            delay: 30
        },
        
        addCustomTrigger() {
            this.newTrigger = {
                name: '',
                type: 'time_based',
                message: '',
                delay: 30
            };
            this.showAddTriggerModal = true;
        },
        
        saveCustomTrigger() {
            const customTrigger = {
                type: 'custom_' + Date.now(),
                name: this.newTrigger.name,
                description: 'Custom trigger',
                category: 'custom',
                message: this.newTrigger.message,
                enabled: true,
                cooldown: 60,
                priority: 2,
                config: {
                    trigger_type: this.newTrigger.type,
                    delay: this.newTrigger.delay
                }
            };
            
            this.triggers.push(customTrigger);
            this.showAddTriggerModal = false;
            toastr.success('Custom trigger created!');
        },
        
        deleteTrigger(index) {
            if (confirm('Are you sure you want to delete this trigger?')) {
                this.triggers.splice(index, 1);
                toastr.success('Trigger deleted');
            }
        }
    };
}
```

### **Fase 3: Backend - API para triggers proactivos**

1. **Crear endpoint en ChatbotController:**
```php
public function getTriggers(Chatbot $chatbot): JsonResponse
{
    $triggers = $chatbot->triggers ?? [];
    return response()->json(['triggers' => $triggers]);
}

public function saveTriggers(Request $request, Chatbot $chatbot): JsonResponse
{
    $validated = $request->validate([
        'triggers' => 'required|array',
        'triggers.*.type' => 'required|string',
        'triggers.*.enabled' => 'required|boolean',
        'triggers.*.message' => 'required|string|max:500',
        'triggers.*.cooldown' => 'required|integer|min:1',
        'triggers.*.priority' => 'required|integer|min:1|max:3',
    ]);
    
    $chatbot->triggers = $validated['triggers'];
    $chatbot->save();
    
    return response()->json(['success' => true]);
}
```

2. **Agregar columna `triggers` a tabla `ext_chatbots`:**
```php
// Migration
Schema::table('ext_chatbots', function (Blueprint $table) {
    $table->json('triggers')->nullable()->after('sales_agent_keywords');
});
```

## 📊 Checklist de Implementación

### ✅ Paso 1: Corregir Triggers (CRÍTICO)
- [ ] Modificar `showTrigger()` en `proactive-triggers.js`
- [ ] Inyectar mensajes en el array de Alpine.js
- [ ] Eliminar bubble flotante
- [ ] Eliminar estilos CSS innecesarios
- [ ] Probar que triggers aparecen dentro del chat
- [ ] Probar que chat se abre automáticamente

### ✅ Paso 2: Mejorar Dashboard
- [ ] Agregar botón "Add Custom Trigger"
- [ ] Crear modal para nuevo trigger
- [ ] Implementar métodos `addCustomTrigger()` y `saveCustomTrigger()`
- [ ] Agregar botón de eliminar trigger
- [ ] Permitir editar triggers existentes

### ✅ Paso 3: Backend
- [ ] Crear migración para columna `triggers`
- [ ] Implementar `getTriggers()` y `saveTriggers()`
- [ ] Agregar rutas API
- [ ] Probar guardado y carga de triggers

### ✅ Paso 4: Testing
- [ ] Trigger "Welcome 30s" funciona
- [ ] Trigger "Exit Intent" funciona
- [ ] Triggers personalizados funcionan
- [ ] Cooldown funciona correctamente
- [ ] Prioridades funcionan
- [ ] Triggers no se repiten incorrectamente

## 🎯 Resultado Final

- ✅ Triggers aparecen DENTRO del chat como mensajes del asistente
- ✅ No más popups flotantes
- ✅ UI profesional y consistente
- ✅ Usuarios pueden crear triggers personalizados
- ✅ Triggers editables y eliminables
- ✅ Sistema robusto y escalable

---

**¿Procedemos con la implementación?**
