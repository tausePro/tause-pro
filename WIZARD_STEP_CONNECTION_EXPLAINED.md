# 🔗 Cómo Está Conectado el Step de Agents en el Wizard

## 📋 **Respuesta Corta:**

El step de "Agents" está conectado al modelo `Chatbot` mediante **campos directos en la tabla** y se guarda con la función `submitData()` que hace un **POST a `/api/v1/chatbots/{id}`**.

---

## 🔍 **Cómo Funciona:**

### **1. Campos en la Tabla `chatbots`**

En la BD, el modelo `Chatbot` tiene estos campos:

```sql
ALTER TABLE chatbots ADD COLUMN sales_agent_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE chatbots ADD COLUMN sales_agent_keywords JSON;
ALTER TABLE chatbots ADD COLUMN sales_agent_priority INT DEFAULT 5;
ALTER TABLE chatbots ADD COLUMN woocommerce_enabled BOOLEAN DEFAULT FALSE;
ALTER TABLE chatbots ADD COLUMN wompi_enabled BOOLEAN DEFAULT FALSE;
```

### **2. Modelo Chatbot**

**Archivo**: `app/Models/Chatbot.php`

```php
class Chatbot extends Model
{
    protected $fillable = [
        // ... otros campos
        'sales_agent_enabled',
        'sales_agent_keywords',
        'sales_agent_priority',
        'woocommerce_enabled',
        'wompi_enabled',
        // ... otros campos
    ];

    protected $casts = [
        'sales_agent_enabled' => 'boolean',
        'sales_agent_keywords' => 'array',
        'sales_agent_priority' => 'integer',
        'woocommerce_enabled' => 'boolean',
        'wompi_enabled' => 'boolean',
    ];
}
```

### **3. Vista del Step (Blade)**

**Archivo**: `app/Extensions/Chatbot/resources/views/home/edit-window/edit-steps/edit-step-agents.blade.php`

```blade
{{-- Toggle Sales Agent --}}
<input 
    type="checkbox" 
    class="peer sr-only" 
    x-model="activeChatbot.sales_agent_enabled"
    @change="submitData()"
>

{{-- Keywords Input --}}
<input
    type="text"
    x-model="activeChatbot.sales_agent_keywords"
    @input="submitData()"
    placeholder="comprar, precio, producto, buy, price"
>

{{-- Priority Select --}}
<select
    x-model="activeChatbot.sales_agent_priority"
    @change="submitData()"
>
    <option value="10">High</option>
    <option value="5">Medium</option>
    <option value="1">Low</option>
</select>
```

**Clave**: Cada cambio ejecuta `@change="submitData()"` o `@input="submitData()"`

### **4. Alpine.js - Objeto `activeChatbot`**

En el Alpine.js del wizard, existe un objeto global:

```javascript
// En el Alpine.js del edit-window
x-data="{
    activeChatbot: {
        id: null,
        title: '',
        sales_agent_enabled: false,
        sales_agent_keywords: [],
        sales_agent_priority: 5,
        woocommerce_enabled: false,
        wompi_enabled: false,
        // ... otros campos
    },
    
    // Función que guarda los cambios
    submitData() {
        // POST a /api/v1/chatbots/{id}
        fetch(`/api/v1/chatbots/${this.activeChatbot.id}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(this.activeChatbot)
        })
        .then(response => response.json())
        .then(data => {
            // Actualizar activeChatbot con la respuesta
            this.activeChatbot = data.data;
        });
    }
}"
```

### **5. Controller - Recibe y Guarda**

**Archivo**: `app/Extensions/Chatbot/System/Http/Controllers/ChatbotController.php`

```php
public function update(ChatbotCustomizeRequest $request): JsonResponse|ChatbotResource
{
    // Validar datos
    $data = $request->validated();
    
    // Guardar en BD
    $chatbot = $this->service->update($data['id'], $data);
    
    // Retornar como JSON
    return ChatbotResource::make($chatbot);
}
```

**Archivo**: `app/Extensions/Chatbot/System/Services/ChatbotService.php`

```php
public function update($id, array $data)
{
    $chatbot = Chatbot::findOrFail($id);
    
    // Guardar todos los campos
    $chatbot->update($data);
    
    return $chatbot;
}
```

---

## 🔄 **Flujo Completo:**

```
Usuario cambia checkbox "Sales Agent Enabled"
   ↓
@change="submitData()" se ejecuta
   ↓
Alpine.js envía POST a /api/v1/chatbots/{id}
   ↓
Body incluye: {
    id: 2,
    sales_agent_enabled: true,
    sales_agent_keywords: ['comprar', 'precio'],
    sales_agent_priority: 5,
    ...otros campos
}
   ↓
ChatbotController::update() recibe
   ↓
ChatbotService::update() guarda en BD
   ↓
Retorna ChatbotResource (JSON)
   ↓
Alpine.js actualiza activeChatbot con respuesta
   ↓
Vista se actualiza automáticamente
```

---

## 📝 **Para el Sales Agent Config, Necesitamos:**

### **Opción 1: Agregar Campos Directos a `chatbots`** (Simple)

```sql
ALTER TABLE chatbots ADD COLUMN sales_agent_config JSON;
```

Guardar todo como JSON:
```json
{
    "enabled": true,
    "agent_name": "Ali",
    "tone": "friendly",
    "sales_strategy": "helpful",
    "search_strategy": "semantic",
    "product_display_mode": "both"
}
```

### **Opción 2: Crear Tabla Separada `SalesAgentConfig`** (Escalable)

```php
// En Chatbot.php
public function salesAgentConfig()
{
    return $this->hasOne(SalesAgentConfig::class);
}

// En SalesAgentConfig.php
public function chatbot()
{
    return $this->belongsTo(Chatbot::class);
}
```

---

## ✅ **Recomendación:**

**Usar Opción 2** (tabla separada) porque:
- ✅ Más escalable
- ✅ Más fácil de mantener
- ✅ Mejor rendimiento
- ✅ Permite agregar más campos en el futuro

---

## 🎯 **Implementación para Sales Agent Config:**

### **Paso 1: Crear Modelo y Migración**

```php
// Model
class SalesAgentConfig extends Model
{
    protected $fillable = [
        'chatbot_id',
        'enabled',
        'agent_name',
        'tone',
        'sales_strategy',
        'search_strategy',
        'product_display_mode',
        'custom_prompt',
    ];
}

// Migración
Schema::create('sales_agent_configs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('chatbot_id')->constrained()->onDelete('cascade');
    $table->boolean('enabled')->default(false);
    $table->string('agent_name')->default('Vendedor');
    $table->enum('tone', ['formal', 'casual', 'friendly'])->default('friendly');
    $table->enum('sales_strategy', ['consultative', 'aggressive', 'helpful'])->default('helpful');
    $table->enum('search_strategy', ['keyword', 'semantic', 'hybrid'])->default('semantic');
    $table->enum('product_display_mode', ['conversational', 'cards', 'both'])->default('both');
    $table->longText('custom_prompt')->nullable();
    $table->timestamps();
});
```

### **Paso 2: Agregar Relación en Chatbot**

```php
// En app/Models/Chatbot.php
public function salesAgentConfig()
{
    return $this->hasOne(SalesAgentConfig::class);
}
```

### **Paso 3: Actualizar el Step en el Wizard**

```blade
{{-- En edit-step-agents.blade.php --}}
<input 
    type="checkbox" 
    x-model="activeChatbot.salesAgentConfig.enabled"
    @change="submitData()"
>

<input 
    type="text" 
    x-model="activeChatbot.salesAgentConfig.agent_name"
    @input="submitData()"
>

<select x-model="activeChatbot.salesAgentConfig.tone" @change="submitData()">
    <option value="formal">Formal</option>
    <option value="casual">Casual</option>
    <option value="friendly">Friendly</option>
</select>
```

### **Paso 4: Actualizar Controller**

```php
public function update(ChatbotCustomizeRequest $request)
{
    $data = $request->validated();
    $chatbot = $this->service->update($data['id'], $data);
    
    // Guardar o actualizar SalesAgentConfig
    if (isset($data['salesAgentConfig'])) {
        $chatbot->salesAgentConfig()->updateOrCreate(
            ['chatbot_id' => $chatbot->id],
            $data['salesAgentConfig']
        );
    }
    
    return ChatbotResource::make($chatbot);
}
```

---

## 🎉 **Resultado:**

El Sales Agent Config estará conectado **exactamente igual** que el step de Agents actual:

1. ✅ Datos en BD (tabla separada)
2. ✅ Modelo con relación
3. ✅ Vista en el wizard
4. ✅ Alpine.js sincronizado
5. ✅ Guardado automático con `submitData()`
6. ✅ Actualización en tiempo real

**¿Empezamos a implementarlo?** 🚀
