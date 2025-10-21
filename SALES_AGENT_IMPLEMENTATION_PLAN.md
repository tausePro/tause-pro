# 🚀 Plan de Implementación - Sales Agent en Chatbot Externo

## 🎯 **Objetivo Final**

Que el **chatbot externo (embebido)** funcione con el **flujo de ventas completo** igual que el interno:

```
Usuario externo pregunta por producto
   ↓
Chatbot busca productos (ProductOrchestrator)
   ↓
Muestra tarjetas de productos + conversación natural
   ↓
Usuario hace clic en "Comprar"
   ↓
Flujo de checkout conversacional (11 pasos)
   ↓
Pago con Wompi
   ↓
✅ Venta completada
```

---

## 📋 **Fases de Implementación**

### **FASE 1: Vista de Configuración en Dashboard** (2-3 horas)

**Objetivo**: Crear un step en el wizard del chatbot para configurar el Sales Agent

#### **1.1 Crear Modelo `SalesAgentConfig`**

**Archivo**: `app/Extensions/Chatbot/System/Models/SalesAgentConfig.php`

```php
<?php

namespace App\Extensions\Chatbot\System\Models;

use Illuminate\Database\Eloquent\Model;

class SalesAgentConfig extends Model
{
    protected $table = 'ext_chatbot_sales_agent_configs';

    protected $fillable = [
        'chatbot_id',
        'enabled',
        'agent_name',              // "Ali", "Tu Vendedor", etc.
        'agent_description',       // Descripción del agente
        'tone',                    // 'formal', 'casual', 'friendly'
        'sales_strategy',          // 'consultative', 'aggressive', 'helpful'
        'search_strategy',         // 'keyword', 'semantic', 'hybrid'
        'product_display_mode',    // 'conversational', 'cards', 'both'
        'custom_prompt',           // Prompt personalizado
        'auto_activate',           // Activar automáticamente
        'activation_keywords',     // JSON array
        'discount_limits',         // JSON array
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'activation_keywords' => 'array',
        'discount_limits' => 'array',
        'enabled' => 'boolean',
        'auto_activate' => 'boolean',
    ];

    public function chatbot()
    {
        return $this->belongsTo(Chatbot::class);
    }
}
```

#### **1.2 Crear Migración**

**Archivo**: `app/Extensions/Chatbot/database/migrations/YYYY_MM_DD_create_sales_agent_configs.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ext_chatbot_sales_agent_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chatbot_id')->constrained('chatbots')->onDelete('cascade');
            $table->boolean('enabled')->default(false);
            $table->string('agent_name')->default('Vendedor');
            $table->text('agent_description')->nullable();
            $table->enum('tone', ['formal', 'casual', 'friendly'])->default('friendly');
            $table->enum('sales_strategy', ['consultative', 'aggressive', 'helpful'])->default('helpful');
            $table->enum('search_strategy', ['keyword', 'semantic', 'hybrid'])->default('semantic');
            $table->enum('product_display_mode', ['conversational', 'cards', 'both'])->default('both');
            $table->longText('custom_prompt')->nullable();
            $table->boolean('auto_activate')->default(true);
            $table->json('activation_keywords')->nullable();
            $table->json('discount_limits')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ext_chatbot_sales_agent_configs');
    }
};
```

#### **1.3 Agregar Relación en Modelo Chatbot**

**Archivo**: `app/Models/Chatbot.php`

```php
public function salesAgentConfig()
{
    return $this->hasOne(SalesAgentConfig::class);
}
```

---

### **FASE 2: Controller de Configuración** (1-2 horas)

**Archivo**: `app/Extensions/Chatbot/System/Http/Controllers/SalesAgentConfigController.php`

```php
<?php

namespace App\Extensions\Chatbot\System\Http\Controllers;

use App\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\SalesAgentConfig;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class SalesAgentConfigController extends Controller
{
    // Obtener configuración actual
    public function show(Chatbot $chatbot)
    {
        $config = $chatbot->salesAgentConfig ?? new SalesAgentConfig();

        return response()->json([
            'success' => true,
            'config' => $config,
        ]);
    }

    // Guardar configuración
    public function update(Request $request, Chatbot $chatbot)
    {
        $validated = $request->validate([
            'enabled' => 'boolean',
            'agent_name' => 'required|string|max:100',
            'agent_description' => 'nullable|string|max:500',
            'tone' => 'required|in:formal,casual,friendly',
            'sales_strategy' => 'required|in:consultative,aggressive,helpful',
            'search_strategy' => 'required|in:keyword,semantic,hybrid',
            'product_display_mode' => 'required|in:conversational,cards,both',
            'custom_prompt' => 'nullable|string|max:2000',
            'auto_activate' => 'boolean',
            'activation_keywords' => 'array',
        ]);

        $config = $chatbot->salesAgentConfig ?? new SalesAgentConfig();
        $config->fill($validated);
        $config->chatbot_id = $chatbot->id;
        $config->save();

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada',
            'config' => $config,
        ]);
    }

    // Preview de conversación
    public function preview(Request $request, Chatbot $chatbot)
    {
        $userMessage = $request->input('message', 'Estoy buscando muletas');
        $config = $chatbot->salesAgentConfig;

        // Aquí iría la lógica de preview
        // Por ahora retornamos un ejemplo

        return response()->json([
            'success' => true,
            'response' => "¡Hola! Tengo varias opciones de muletas que podrían interesarte. ¿Quieres que te ayude a encontrar exactamente lo que necesitas aquí mismo?",
            'products_found' => 3,
        ]);
    }
}
```

---

### **FASE 3: Vista de Configuración en el Wizard** (2-3 horas)

**Archivo**: `app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-sales-agent.blade.php`

```blade
{{-- Sales Agent Configuration Step --}}
<div
    class="col-start-1 col-end-1 row-start-1 row-end-1 transition-all"
    data-step="sales-agent"
    x-show="editingStep === 'sales-agent'"
    x-transition
>
    <div class="space-y-6">
        <!-- Enable/Disable -->
        <div class="flex items-center justify-between">
            <label class="block text-sm font-medium">
                Habilitar Agente de Ventas
            </label>
            <input 
                type="checkbox" 
                x-model="chatbot.salesAgent.enabled"
                class="w-4 h-4"
            />
        </div>

        <template x-if="chatbot.salesAgent.enabled">
            <!-- Agent Name -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Nombre del Agente
                </label>
                <input 
                    type="text" 
                    x-model="chatbot.salesAgent.agent_name"
                    placeholder="Ej: Ali, Tu Vendedor, etc."
                    class="w-full px-3 py-2 border rounded-lg"
                />
                <p class="text-xs text-gray-500 mt-1">
                    Cómo se presentará en el chat
                </p>
            </div>

            <!-- Agent Description -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Descripción del Agente
                </label>
                <textarea 
                    x-model="chatbot.salesAgent.agent_description"
                    placeholder="Ej: Soy tu vendedor personal, aquí para ayudarte..."
                    rows="3"
                    class="w-full px-3 py-2 border rounded-lg"
                ></textarea>
            </div>

            <!-- Tone Selection -->
            <div>
                <label class="block text-sm font-medium mb-3">
                    Tono de Conversación
                </label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            x-model="chatbot.salesAgent.tone" 
                            value="formal"
                            class="w-4 h-4"
                        />
                        <span class="ml-2 text-sm">Formal y profesional</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            x-model="chatbot.salesAgent.tone" 
                            value="casual"
                            class="w-4 h-4"
                        />
                        <span class="ml-2 text-sm">Casual y relajado</span>
                    </label>
                    <label class="flex items-center">
                        <input 
                            type="radio" 
                            x-model="chatbot.salesAgent.tone" 
                            value="friendly"
                            class="w-4 h-4"
                        />
                        <span class="ml-2 text-sm">Amigable y cálido</span>
                    </label>
                </div>
            </div>

            <!-- Sales Strategy -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Estrategia de Ventas
                </label>
                <select 
                    x-model="chatbot.salesAgent.sales_strategy"
                    class="w-full px-3 py-2 border rounded-lg"
                >
                    <option value="consultative">Consultivo (preguntar necesidades)</option>
                    <option value="aggressive">Agresivo (vender más)</option>
                    <option value="helpful">Servicial (ayudar sin presionar)</option>
                </select>
            </div>

            <!-- Product Display Mode -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Mostrar Productos
                </label>
                <select 
                    x-model="chatbot.salesAgent.product_display_mode"
                    class="w-full px-3 py-2 border rounded-lg"
                >
                    <option value="conversational">Solo conversación natural</option>
                    <option value="cards">Solo tarjetas</option>
                    <option value="both">Conversación + tarjetas</option>
                </select>
            </div>

            <!-- Search Strategy -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Estrategia de Búsqueda
                </label>
                <select 
                    x-model="chatbot.salesAgent.search_strategy"
                    class="w-full px-3 py-2 border rounded-lg"
                >
                    <option value="keyword">Por palabras clave</option>
                    <option value="semantic">Semántica (IA)</option>
                    <option value="hybrid">Híbrida (ambas)</option>
                </select>
            </div>

            <!-- Custom Prompt -->
            <div>
                <label class="block text-sm font-medium mb-2">
                    Prompt Personalizado (Opcional)
                </label>
                <textarea 
                    x-model="chatbot.salesAgent.custom_prompt"
                    placeholder="Instrucciones adicionales para el agente..."
                    rows="4"
                    class="w-full px-3 py-2 border rounded-lg text-xs"
                ></textarea>
                <p class="text-xs text-gray-500 mt-1">
                    Usa {agent_name}, {tone}, {strategy} como variables
                </p>
            </div>

            <!-- Preview Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h4 class="font-medium text-blue-900 mb-3">📋 Preview</h4>
                <div class="bg-white rounded p-3 text-sm space-y-2 mb-3">
                    <div class="text-gray-600">
                        <strong>Usuario:</strong> "Estoy buscando muletas"
                    </div>
                    <div id="previewResponse" class="text-gray-800 italic">
                        Cargando preview...
                    </div>
                </div>
                <button 
                    @click="previewSalesAgent()"
                    class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium hover:bg-blue-700"
                >
                    Actualizar Preview
                </button>
            </div>
        </template>
    </div>
</div>
```

---

### **FASE 4: Integración en Frontend del Chatbot Externo** (3-4 horas)

**Archivo**: `app/Extensions/Chatbot/resources/views/frontend-ui/chatbot.blade.php`

Incluir el componente del Sales Agent:

```blade
@if($chatbot->salesAgentConfig?->enabled)
    @include('chatbot-sales-agent::sales-agent-component', [
        'chatbot' => $chatbot,
        'config' => $chatbot->salesAgentConfig,
        'routes' => [
            'getProducts' => route('api.v2.chatbot.sales-agent.products', $chatbot->uuid),
            'createOrder' => route('api.v2.chatbot.sales-agent.create-order', $chatbot->uuid),
        ],
    ])
@endif
```

---

### **FASE 5: Rutas API** (30 minutos)

**Archivo**: `routes/api.php`

```php
Route::prefix('v2/chatbot')->group(function () {
    Route::get('{chatbot:uuid}/sales-agent/config', 'SalesAgentConfigController@show');
    Route::post('{chatbot:uuid}/sales-agent/products', 'ChatbotSalesAgentController@getProducts');
    Route::post('{chatbot:uuid}/sales-agent/order', 'ChatbotSalesAgentController@createOrder');
});
```

---

## 📊 **Resumen de Archivos a Crear/Modificar**

### **Crear:**
- [ ] `app/Extensions/Chatbot/System/Models/SalesAgentConfig.php`
- [ ] `app/Extensions/Chatbot/System/Http/Controllers/SalesAgentConfigController.php`
- [ ] `app/Extensions/Chatbot/database/migrations/YYYY_MM_DD_create_sales_agent_configs.php`
- [ ] `app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-sales-agent.blade.php`

### **Modificar:**
- [ ] `app/Models/Chatbot.php` - Agregar relación `salesAgentConfig()`
- [ ] `app/Extensions/Chatbot/resources/views/frontend-ui/chatbot.blade.php` - Incluir componente
- [ ] `routes/api.php` - Agregar rutas API
- [ ] Wizard del chatbot - Agregar step de Sales Agent

---

## ⏱️ **Tiempo Total Estimado**

- Fase 1: 2-3 horas
- Fase 2: 1-2 horas
- Fase 3: 2-3 horas
- Fase 4: 3-4 horas
- Fase 5: 30 minutos

**Total: 9-13 horas de desarrollo**

---

## ✅ **Resultado Final**

✅ Dashboard con configuración del Sales Agent
✅ Chatbot externo con flujo de ventas completo
✅ Conversación natural + tarjetas de productos
✅ Checkout conversacional
✅ Pago con Wompi
✅ Todo configurable desde el dashboard

**¿Empezamos?** 🚀
