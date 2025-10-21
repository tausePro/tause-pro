# 🎯 Sales Agent Orchestration - Arquitectura Completa

## 🎨 **Visión General**

Transformar el Sales Agent de un sistema **tosco de tarjetas de productos** a un **vendedor conversacional en tiempo real** que:

1. **Entiende la necesidad del cliente** ("estoy buscando muletas")
2. **Busca productos inteligentemente** usando ProductOrchestrator
3. **Recomienda en conversación natural** ("Tengo varias opciones, ¿quieres comprar aquí conmigo?")
4. **Facilita la compra** sin que el cliente navegue el sitio
5. **Se configura desde el dashboard** como el external chatbot

---

## 🏗️ **Arquitectura Propuesta**

```
┌─────────────────────────────────────────────────────────────┐
│                    DASHBOARD CHATBOT                         │
│                    (Wizard Steps)                            │
└──────────────────────┬──────────────────────────────────────┘
                       │
        ┌──────────────┼──────────────┐
        │              │              │
        ▼              ▼              ▼
   ┌─────────┐  ┌──────────┐  ┌─────────────┐
   │Configure│  │Prompts & │  │Estrategia & │
   │Behavior │  │Tone      │  │Triggers     │
   └────┬────┘  └────┬─────┘  └────┬────────┘
        │            │             │
        └────────────┼─────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │ SalesAgentOrchestrator     │
        │ (Servicio Central)         │
        └────────────┬───────────────┘
                     │
        ┌────────────┼────────────┐
        │            │            │
        ▼            ▼            ▼
   ┌─────────┐ ┌──────────┐ ┌──────────────┐
   │Product  │ │Conversation
   │Search   │ │Manager   │ │Payment       │
   │(Orch.)  │ │          │ │Integration   │
   └─────────┘ └──────────┘ └──────────────┘
        │            │            │
        └────────────┼────────────┘
                     │
                     ▼
        ┌────────────────────────────┐
        │  Frontend Sales Agent      │
        │  (Conversational UI)       │
        └────────────────────────────┘
```

---

## 📋 **Componentes Principales**

### **1. SalesAgentOrchestrator (Nuevo Servicio)**

**Ubicación**: `app/Extensions/ChatbotSalesAgent/System/Services/SalesAgentOrchestrator.php`

**Responsabilidades:**
- Gestionar la configuración del Sales Agent
- Orquestar búsquedas de productos
- Generar prompts dinámicos
- Manejar contexto de conversación
- Integrar con ProductOrchestrator

```php
class SalesAgentOrchestrator
{
    public function __construct(
        protected ProductOrchestratorService $productOrchestrator,
        protected ConversationManager $conversationManager,
    ) {}

    // Obtener configuración del Sales Agent
    public function getConfiguration(Chatbot $chatbot): SalesAgentConfig
    {
        return $chatbot->salesAgentConfig;
    }

    // Buscar productos basado en contexto de conversación
    public function searchProducts(
        Chatbot $chatbot,
        string $userQuery,
        array $conversationContext = []
    ): Collection
    {
        // Usar ProductOrchestrator para búsqueda inteligente
        return $this->productOrchestrator->search(
            $chatbot,
            $userQuery,
            $conversationContext
        );
    }

    // Generar respuesta del agente basada en configuración
    public function generateAgentResponse(
        Chatbot $chatbot,
        string $userMessage,
        array $foundProducts = []
    ): string
    {
        $config = $this->getConfiguration($chatbot);
        
        // Usar prompt personalizado
        $prompt = $this->buildDynamicPrompt($config, $foundProducts);
        
        // Generar respuesta con tono configurado
        return $this->generateResponse($prompt, $config->tone);
    }

    // Construir prompt dinámico basado en configuración
    private function buildDynamicPrompt(
        SalesAgentConfig $config,
        array $products
    ): string
    {
        return <<<PROMPT
        Eres {$config->agent_name}, un vendedor amable y profesional.
        
        Tono: {$config->tone_description}
        Estrategia: {$config->sales_strategy}
        
        El cliente está buscando: [USER_QUERY]
        
        Tenemos estos productos disponibles:
        {$this->formatProducts($products)}
        
        Responde de manera natural, como un vendedor real en tiempo real.
        NO muestres listas de productos.
        Ofrece ayuda personalizada.
        
        Ejemplo de respuesta:
        "Tengo varias opciones que podrían interesarte. ¿Te gustaría que te las muestre aquí mismo? Puedo ayudarte a encontrar exactamente lo que necesitas sin que tengas que navegar el sitio."
        PROMPT;
    }

    // Manejar intención de compra
    public function handlePurchaseIntent(
        Chatbot $chatbot,
        array $selectedProducts
    ): PurchaseSession
    {
        return $this->conversationManager->startPurchaseSession(
            $chatbot,
            $selectedProducts
        );
    }
}
```

---

### **2. SalesAgentConfig (Modelo)**

**Ubicación**: `app/Extensions/ChatbotSalesAgent/System/Models/SalesAgentConfig.php`

```php
class SalesAgentConfig extends Model
{
    protected $fillable = [
        'chatbot_id',
        'agent_name',           // "Ali", "Tu vendedor", etc.
        'agent_description',    // Descripción del agente
        'tone',                 // 'formal', 'casual', 'friendly'
        'tone_description',     // Descripción del tono
        'sales_strategy',       // 'consultative', 'aggressive', 'helpful'
        'custom_prompt',        // Prompt personalizado
        'search_strategy',      // Cómo buscar productos
        'product_display_mode', // 'conversational', 'cards', 'both'
        'auto_activate',        // Activar automáticamente
        'activation_keywords',  // Keywords para activar
        'discount_limits',      // Límites de descuento
        'enabled',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'activation_keywords' => 'array',
        'discount_limits' => 'array',
        'enabled' => 'boolean',
    ];

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class);
    }
}
```

---

### **3. SalesAgentConfigurationController (Dashboard)**

**Ubicación**: `app/Extensions/ChatbotSalesAgent/System/Http/Controllers/SalesAgentConfigurationController.php`

```php
class SalesAgentConfigurationController extends Controller
{
    public function __construct(
        protected SalesAgentOrchestrator $orchestrator
    ) {}

    // Mostrar configuración actual
    public function show(Chatbot $chatbot)
    {
        $config = $chatbot->salesAgentConfig ?? new SalesAgentConfig();
        
        return view('sales-agent::configuration.show', [
            'chatbot' => $chatbot,
            'config' => $config,
            'tones' => $this->getToneOptions(),
            'strategies' => $this->getStrategyOptions(),
            'displayModes' => $this->getDisplayModeOptions(),
        ]);
    }

    // Guardar configuración
    public function update(Request $request, Chatbot $chatbot)
    {
        $validated = $request->validate([
            'agent_name' => 'required|string|max:100',
            'agent_description' => 'required|string|max:500',
            'tone' => 'required|in:formal,casual,friendly',
            'sales_strategy' => 'required|in:consultative,aggressive,helpful',
            'custom_prompt' => 'nullable|string|max:2000',
            'search_strategy' => 'required|in:keyword,semantic,hybrid',
            'product_display_mode' => 'required|in:conversational,cards,both',
            'auto_activate' => 'boolean',
            'activation_keywords' => 'array',
            'discount_limits' => 'array',
            'enabled' => 'boolean',
        ]);

        $config = $chatbot->salesAgentConfig ?? new SalesAgentConfig();
        $config->fill($validated);
        $config->chatbot_id = $chatbot->id;
        $config->save();

        return redirect()->back()->with('success', 'Configuración guardada');
    }

    // Preview de conversación
    public function preview(Request $request, Chatbot $chatbot)
    {
        $userMessage = $request->input('message');
        $config = $chatbot->salesAgentConfig;

        // Buscar productos
        $products = $this->orchestrator->searchProducts(
            $chatbot,
            $userMessage
        );

        // Generar respuesta
        $response = $this->orchestrator->generateAgentResponse(
            $chatbot,
            $userMessage,
            $products->toArray()
        );

        return response()->json([
            'success' => true,
            'response' => $response,
            'products_found' => $products->count(),
        ]);
    }

    private function getToneOptions()
    {
        return [
            'formal' => 'Formal y profesional',
            'casual' => 'Casual y relajado',
            'friendly' => 'Amigable y cálido',
        ];
    }

    private function getStrategyOptions()
    {
        return [
            'consultative' => 'Consultivo (preguntar necesidades)',
            'aggressive' => 'Agresivo (vender más)',
            'helpful' => 'Servicial (ayudar sin presionar)',
        ];
    }

    private function getDisplayModeOptions()
    {
        return [
            'conversational' => 'Solo conversación natural',
            'cards' => 'Solo tarjetas de productos',
            'both' => 'Conversación + tarjetas',
        ];
    }
}
```

---

## 📊 **Integración con el Wizard del Chatbot**

### **Nuevo Step: "Sales Agent"**

**Ubicación**: `app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-sales-agent.blade.php`

```blade
{{-- Editing Step X - Sales Agent Configuration --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="X"
    x-show="editingStep === X"
    x-transition
>
    <div class="space-y-6">
        <!-- Agent Name & Description -->
        <div>
            <label class="block text-sm font-medium mb-2">
                Nombre del Agente de Ventas
            </label>
            <input 
                type="text" 
                x-model="salesAgent.agent_name"
                placeholder="Ej: Ali, Tu Vendedor, etc."
                class="w-full px-3 py-2 border rounded-lg"
            />
            <p class="text-xs text-gray-500 mt-1">
                Cómo se presentará el agente en el chat
            </p>
        </div>

        <!-- Tone Selection -->
        <div>
            <label class="block text-sm font-medium mb-2">
                Tono de Conversación
            </label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" x-model="salesAgent.tone" value="formal" />
                    <span class="ml-2">Formal y profesional</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" x-model="salesAgent.tone" value="casual" />
                    <span class="ml-2">Casual y relajado</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" x-model="salesAgent.tone" value="friendly" />
                    <span class="ml-2">Amigable y cálido</span>
                </label>
            </div>
        </div>

        <!-- Sales Strategy -->
        <div>
            <label class="block text-sm font-medium mb-2">
                Estrategia de Ventas
            </label>
            <select x-model="salesAgent.sales_strategy" class="w-full px-3 py-2 border rounded-lg">
                <option value="consultative">Consultivo (preguntar necesidades)</option>
                <option value="aggressive">Agresivo (vender más)</option>
                <option value="helpful">Servicial (ayudar sin presionar)</option>
            </select>
        </div>

        <!-- Product Display Mode -->
        <div>
            <label class="block text-sm font-medium mb-2">
                Cómo Mostrar Productos
            </label>
            <select x-model="salesAgent.product_display_mode" class="w-full px-3 py-2 border rounded-lg">
                <option value="conversational">Solo conversación natural</option>
                <option value="cards">Solo tarjetas de productos</option>
                <option value="both">Conversación + tarjetas</option>
            </select>
        </div>

        <!-- Custom Prompt -->
        <div>
            <label class="block text-sm font-medium mb-2">
                Prompt Personalizado (Opcional)
            </label>
            <textarea 
                x-model="salesAgent.custom_prompt"
                placeholder="Instrucciones adicionales para el agente..."
                rows="4"
                class="w-full px-3 py-2 border rounded-lg"
            ></textarea>
        </div>

        <!-- Preview -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <h4 class="font-medium text-blue-900 mb-3">Preview</h4>
            <div class="bg-white rounded p-3 text-sm space-y-2">
                <div class="text-gray-600">
                    <strong>Usuario:</strong> "Estoy buscando muletas"
                </div>
                <div id="previewResponse" class="text-gray-800 italic">
                    Cargando preview...
                </div>
            </div>
            <button 
                @click="previewSalesAgent()"
                class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm"
            >
                Actualizar Preview
            </button>
        </div>

        <!-- Save Button -->
        <button 
            @click="saveSalesAgentConfig()"
            class="w-full px-4 py-2 bg-green-600 text-white rounded-lg font-medium"
        >
            Guardar Configuración
        </button>
    </div>
</div>
```

---

## 🔄 **Flujo de Conversación Mejorado**

### **Antes (Actual - Tosco):**
```
Usuario: "Estoy buscando muletas"
Bot: [Muestra 4 tarjetas de productos]
```

### **Después (Conversacional - Natural):**
```
Usuario: "Estoy buscando muletas"

Bot (Ali): "¡Hola! Perfecto, tengo varias opciones de muletas que podrían 
interesarte. ¿Quieres que te ayude a encontrar exactamente lo que necesitas 
aquí mismo en tiempo real? Soy tu vendedor personal, así que no tienes que 
navegar el sitio. Dime, ¿las necesitas para recuperación, uso temporal o 
permanente?"

Usuario: "Para recuperación de una lesión"

Bot (Ali): "Entendido. En ese caso te recomiendo estas opciones:
- Muletas ajustables (muy cómodas para recuperación)
- Muletas de aluminio (ligeras y resistentes)

¿Cuál te interesa? O cuéntame más sobre tu lesión para darte la mejor 
recomendación."

Usuario: "La de aluminio"

Bot (Ali): "Excelente elección. Las muletas de aluminio son perfectas. 
¿Quieres comprarla aquí conmigo ahora mismo? Solo necesito algunos datos 
y listo, sin complicaciones."

Usuario: "Sí"

Bot (Ali): "Perfecto! ¿Cuántas unidades necesitas?"
[Continúa con flujo de compra conversacional]
```

---

## 🎯 **Integración con ProductOrchestrator**

El `SalesAgentOrchestrator` usa `ProductOrchestratorService` para:

1. **Búsqueda Semántica**: Entender "muletas" como producto
2. **Contexto**: Saber que es para "recuperación"
3. **Recomendación**: Sugerir productos relevantes
4. **Filtrado**: Mostrar solo lo que tiene stock

```php
// En SalesAgentOrchestrator
public function searchProducts(
    Chatbot $chatbot,
    string $userQuery,
    array $conversationContext = []
): Collection
{
    // Usar ProductOrchestrator con contexto
    return $this->productOrchestrator->search(
        query: $userQuery,
        context: $conversationContext,  // Historial de conversación
        filters: [
            'in_stock' => true,
            'active' => true,
        ]
    );
}
```

---

## 📱 **Rutas Necesarias**

```php
// En routes/panel.php
Route::prefix('chatbot/{chatbot}')
    ->name('dashboard.chatbot.')
    ->group(function () {
        // Sales Agent Configuration
        Route::get('sales-agent/config', 'SalesAgentConfigurationController@show')
            ->name('sales-agent.config');
        Route::post('sales-agent/config', 'SalesAgentConfigurationController@update')
            ->name('sales-agent.update');
        Route::post('sales-agent/preview', 'SalesAgentConfigurationController@preview')
            ->name('sales-agent.preview');
    });
```

---

## 🎨 **Beneficios de Esta Arquitectura**

### **Para el Usuario (Cliente):**
- ✅ Experiencia conversacional natural
- ✅ Vendedor personal que busca por ti
- ✅ Sin navegación complicada del sitio
- ✅ Compra rápida y fácil

### **Para el Negocio:**
- ✅ Configuración flexible del comportamiento del agente
- ✅ Múltiples estrategias de venta
- ✅ Personalización por chatbot
- ✅ Mejor tasa de conversión

### **Para el Desarrollador:**
- ✅ Arquitectura modular y escalable
- ✅ Fácil de mantener y extender
- ✅ Integración limpia con ProductOrchestrator
- ✅ Separación de responsabilidades

---

## 📋 **Checklist de Implementación**

- [ ] Crear modelo `SalesAgentConfig`
- [ ] Crear servicio `SalesAgentOrchestrator`
- [ ] Crear `SalesAgentConfigurationController`
- [ ] Crear vistas de configuración
- [ ] Integrar con wizard del chatbot
- [ ] Integrar con `ProductOrchestratorService`
- [ ] Adaptar frontend para modo conversacional
- [ ] Testing completo
- [ ] Documentación

---

## ✅ **Conclusión**

Esta arquitectura permite:

1. **Configurar el Sales Agent desde el dashboard** (como el external chatbot)
2. **Personalizar comportamiento y tono** del agente
3. **Conversaciones naturales** sin tarjetas toscos
4. **Búsqueda inteligente** de productos
5. **Experiencia de vendedor real en tiempo real**

**Todo integrado, modular y escalable.** 🚀
