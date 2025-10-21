# 📋 Phase 3: E-commerce Dashboard Integration

**Objetivo**: Integrar el tab de Sales Agent en la vista de E-commerce

---

## 🔍 **Paso 1: Revisar Estructura Actual**

Primero necesitamos ver cómo está estructurado el dashboard de E-commerce:

```bash
# Buscar la vista principal de E-commerce
find app/Extensions/Chatbot/resources/views -name "*ecommerce*" -type f

# Buscar el controller
grep -r "ChatbotEcommerceController" app/Extensions/Chatbot/System/Http/Controllers/
```

**Esperamos encontrar:**
- `app/Extensions/Chatbot/resources/views/ecommerce/index.blade.php` (o similar)
- `app/Extensions/Chatbot/System/Http/Controllers/ChatbotEcommerceController.php`

---

## 🔧 **Paso 2: Analizar ChatbotEcommerceController**

Una vez encontrado, revisar:

```php
// En ChatbotEcommerceController.php
public function index(Chatbot $chatbot)
{
    // ¿Qué datos pasa a la vista?
    // ¿Cómo carga la configuración actual?
    // ¿Hay tabs ya implementados?
}
```

**Preguntas clave:**
- ¿Usa Alpine.js o Livewire?
- ¿Cómo maneja los tabs?
- ¿Hay un patrón de guardado (submitData, form submit, etc)?

---

## 🎨 **Paso 3: Integrar Tab de Sales Agent**

### **Opción A: Si usa Tabs con Alpine.js**

```blade
{{-- En ecommerce/index.blade.php --}}

<div x-data="{ activeTab: 'woocommerce' }">
    {{-- Tabs Navigation --}}
    <div class="tabs-nav">
        <button @click="activeTab = 'woocommerce'">WooCommerce</button>
        <button @click="activeTab = 'wompi'">Wompi</button>
        <button @click="activeTab = 'sales-agent'">Sales Agent</button>
        <button @click="activeTab = 'products'">Products</button>
    </div>

    {{-- Tabs Content --}}
    <div x-show="activeTab === 'woocommerce'">
        {{-- WooCommerce config --}}
    </div>

    <div x-show="activeTab === 'wompi'">
        {{-- Wompi config --}}
    </div>

    <div x-show="activeTab === 'sales-agent'">
        @include('chatbot::ecommerce.tabs.sales-agent-config', [
            'salesAgentConfig' => $chatbot->salesAgentConfig,
            'chatbot' => $chatbot
        ])
    </div>

    <div x-show="activeTab === 'products'">
        {{-- Products --}}
    </div>
</div>
```

### **Opción B: Si usa Livewire**

```blade
{{-- En ecommerce/index.blade.php --}}

<livewire:ecommerce-tabs :chatbot="$chatbot" />
```

---

## 📝 **Paso 4: Crear Formulario con Guardado**

### **Opción A: Form tradicional**

```blade
<form action="{{ route('dashboard.chatbot.ecommerce.save-sales-agent', $chatbot) }}" method="POST">
    @csrf
    
    @include('chatbot::ecommerce.tabs.sales-agent-config', [
        'salesAgentConfig' => $chatbot->salesAgentConfig
    ])
    
    <button type="submit" class="btn btn-primary">
        {{ __('Save Configuration') }}
    </button>
</form>
```

### **Opción B: AJAX con Alpine.js**

```blade
<form @submit.prevent="saveSalesAgentConfig()">
    @include('chatbot::ecommerce.tabs.sales-agent-config', [
        'salesAgentConfig' => $chatbot->salesAgentConfig
    ])
    
    <button type="submit" class="btn btn-primary">
        {{ __('Save Configuration') }}
    </button>
</form>

<script>
    function saveSalesAgentConfig() {
        const formData = new FormData(this.$el);
        
        fetch(`/api/v1/chatbots/{{ $chatbot->id }}/sales-agent-config`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                toastr.success('{{ __("Configuration saved") }}');
            } else {
                toastr.error(data.message);
            }
        });
    }
</script>
```

---

## 🔗 **Paso 5: Agregar Ruta en Controller**

Si no existe, agregar método en `ChatbotEcommerceController`:

```php
public function saveSalesAgentConfig(Request $request, Chatbot $chatbot)
{
    // Verificar autorización
    if ($chatbot->user_id !== auth()->id()) {
        abort(403);
    }

    // Validar datos
    $validated = $request->validate([
        'agent_name' => 'required|string|max:100',
        'tone' => 'required|in:formal,casual,friendly',
        'sales_strategy' => 'required|in:consultative,aggressive,helpful',
        'search_strategy' => 'required|in:keyword,semantic,hybrid',
        'product_display_mode' => 'required|in:conversational,cards,both',
        'custom_prompt' => 'nullable|string|max:2000',
        'product_card_config' => 'nullable|array',
        'product_card_config.button_color' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
        // ... más validaciones
    ]);

    // Guardar configuración
    $chatbot->salesAgentConfig()->updateOrCreate(
        ['chatbot_id' => $chatbot->id],
        $validated
    );

    return back()->with('success', 'Sales Agent configuration saved');
}
```

---

## ✅ **Paso 6: Registrar Ruta**

En `routes/panel.php` o donde estén las rutas de E-commerce:

```php
Route::post('chatbot/{chatbot}/ecommerce/sales-agent', [
    ChatbotEcommerceController::class, 
    'saveSalesAgentConfig'
])->name('dashboard.chatbot.ecommerce.save-sales-agent');
```

---

## 🧪 **Paso 7: Testing**

1. **Ir a E-commerce dashboard**
   ```
   /dashboard/chatbot/{id}/ecommerce
   ```

2. **Hacer clic en tab "Sales Agent"**

3. **Cambiar valores:**
   - Agent Name
   - Tone
   - Button Color
   - Etc.

4. **Guardar**

5. **Verificar en BD:**
   ```sql
   SELECT * FROM ext_chatbot_sales_agent_configs 
   WHERE chatbot_id = {id};
   ```

6. **Recargar página y verificar que se carguen los valores**

---

## 🐛 **Troubleshooting**

### **Problema: Tab no aparece**
- Verificar que la vista se incluye correctamente
- Verificar que Alpine.js está cargado
- Revisar console del navegador

### **Problema: No guarda datos**
- Verificar que la ruta existe
- Verificar CSRF token
- Revisar logs de Laravel

### **Problema: Preview no actualiza**
- Verificar que los color pickers tienen IDs correctos
- Revisar que el JavaScript de sincronización está cargado

---

## 📚 **Archivos Relacionados**

- `SALES_AGENT_PRODUCT_CARD_CONFIGURATION.md` - Patrón Alpine
- `WIZARD_STEP_CONNECTION_EXPLAINED.md` - Cómo conectar
- `app/Extensions/Chatbot/resources/views/ecommerce/tabs/sales-agent-config.blade.php` - Vista
- `app/Extensions/Chatbot/resources/views/ecommerce/partials/product-card-preview.blade.php` - Preview

---

## 🎯 **Próximo Paso**

Una vez integrado en E-commerce, pasar a **Phase 4: Frontend Integration**
- Cargar configuración en sales-agent-component.blade.php
- Aplicar estilos de tarjetas
- Usar custom_prompt en prompts dinámicos
