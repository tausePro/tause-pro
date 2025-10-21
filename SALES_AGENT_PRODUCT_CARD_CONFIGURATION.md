# 🎨 Sales Agent - Product Card Configuration & Preview System

## 📊 **Análisis: Cómo Está Construido el Wizard del External Chatbot**

### **Patrón Alpine.js del Wizard:**

```javascript
// 1. Objeto Global: activeChatbot
x-data="{
    activeChatbot: {
        id: null,
        title: '',
        color: '#017BE5',           // Color principal
        header_bg_type: 'color',    // color, gradient, image
        header_bg_color: '#017BE5',
        header_bg_gradient: '...',
        avatar: '...',
        logo: '...',
        // ... más campos
    }
}"

// 2. Binding Bidireccional
x-model="activeChatbot.color"
x-model="activeChatbot.header_bg_color"

// 3. Watchers para Preview en Tiempo Real
x-effect="picker && picker.setColor(activeChatbot.header_bg_color)"

// 4. Guardado Automático
@change="submitData()"
@input="submitData()"

// 5. Preview en Vivo (lado derecho)
:style="{ backgroundColor: activeChatbot.color }"
```

---

## 🎯 **Lo que Necesitamos para Sales Agent**

### **1. Estructura Alpine.js Similar:**

```javascript
x-data="{
    activeChatbot: {
        // ... campos existentes
        salesAgent: {
            enabled: false,
            agent_name: 'Vendedor',
            tone: 'friendly',
            sales_strategy: 'helpful',
            search_strategy: 'semantic',
            product_display_mode: 'both',
            
            // NUEVO: Configuración de Tarjetas
            product_card: {
                button_color: '#10b981',      // Color del botón "Comprar"
                button_text_color: '#ffffff',
                button_style: 'solid',        // solid, gradient, outline
                card_border_color: '#e5e7eb',
                card_shadow: 'md',            // none, sm, md, lg
                card_border_radius: '0.75rem',
                price_color: '#10b981',
                discount_badge_color: '#ef4444',
                show_stock_indicator: true,
                show_discount_badge: true,
            },
            
            // Preview
            preview_product: {
                id: 1,
                name: 'Muletas de Aluminio',
                price: 89900,
                formatted_price: '$89.900 COP',
                image_url: 'https://...',
                has_discount: true,
                discount_percentage: 15,
                in_stock: true,
            }
        }
    }
}"
```

---

## 🏗️ **Arquitectura Propuesta**

### **Tab: Sales Agent Config en E-commerce**

```
/dashboard/chatbot/{id}/ecommerce
├── Tab: WooCommerce
├── Tab: Wompi
├── Tab: Sales Agent ← AQUÍ
│   ├── Sección 1: Comportamiento
│   │   ├── Agent Name
│   │   ├── Tone (Radio buttons)
│   │   ├── Sales Strategy (Select)
│   │   └── Search Strategy (Select)
│   │
│   ├── Sección 2: Configuración de Tarjetas ← NUEVO
│   │   ├── Button Color Picker
│   │   ├── Button Style (Solid, Gradient, Outline)
│   │   ├── Card Border Color
│   │   ├── Card Shadow Level
│   │   ├── Price Color
│   │   ├── Discount Badge Color
│   │   └── Toggles (Stock, Discount Badge)
│   │
│   ├── Sección 3: Preview en Vivo ← NUEVO
│   │   └── Tarjeta de Producto con Configuración Aplicada
│   │
│   └── Sección 4: Prompt Personalizado
│       └── Textarea para entrenar respuestas
```

---

## 💻 **Implementación Paso a Paso**

### **Paso 1: Extender SalesAgentConfig Model**

```php
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
        
        // NUEVO: Product Card Config
        'product_card_config', // JSON
    ];

    protected $casts = [
        'product_card_config' => 'array',
    ];

    public function getProductCardConfig()
    {
        return $this->product_card_config ?? [
            'button_color' => '#10b981',
            'button_text_color' => '#ffffff',
            'button_style' => 'solid',
            'card_border_color' => '#e5e7eb',
            'card_shadow' => 'md',
            'card_border_radius' => '0.75rem',
            'price_color' => '#10b981',
            'discount_badge_color' => '#ef4444',
            'show_stock_indicator' => true,
            'show_discount_badge' => true,
        ];
    }
}
```

### **Paso 2: Vista Blade - Tab Sales Agent**

```blade
{{-- resources/views/ecommerce/tabs/sales-agent-config.blade.php --}}

<div class="space-y-8">
    {{-- Sección 1: Comportamiento --}}
    <div class="space-y-4">
        <h3 class="font-semibold">Comportamiento del Agente</h3>
        
        <div>
            <label class="block text-sm font-medium mb-2">Nombre del Agente</label>
            <input 
                type="text" 
                x-model="activeChatbot.salesAgent.agent_name"
                @input="submitData()"
                class="w-full px-3 py-2 border rounded-lg"
            />
        </div>

        <div>
            <label class="block text-sm font-medium mb-2">Tono</label>
            <div class="space-y-2">
                <label class="flex items-center">
                    <input type="radio" x-model="activeChatbot.salesAgent.tone" value="formal" @change="submitData()" />
                    <span class="ml-2">Formal</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" x-model="activeChatbot.salesAgent.tone" value="casual" @change="submitData()" />
                    <span class="ml-2">Casual</span>
                </label>
                <label class="flex items-center">
                    <input type="radio" x-model="activeChatbot.salesAgent.tone" value="friendly" @change="submitData()" />
                    <span class="ml-2">Amigable</span>
                </label>
            </div>
        </div>
    </div>

    <hr>

    {{-- Sección 2: Configuración de Tarjetas --}}
    <div class="space-y-4">
        <h3 class="font-semibold">Configuración de Tarjetas de Productos</h3>
        
        {{-- Button Color --}}
        <div>
            <label class="block text-sm font-medium mb-2">Color del Botón "Comprar"</label>
            <div class="flex items-center gap-2">
                <div 
                    class="w-10 h-10 rounded-lg border-2 border-gray-300 cursor-pointer"
                    :style="{ backgroundColor: activeChatbot.salesAgent.product_card.button_color }"
                    @click="$refs.buttonColorInput.click()"
                ></div>
                <input 
                    type="color"
                    x-ref="buttonColorInput"
                    x-model="activeChatbot.salesAgent.product_card.button_color"
                    @input="submitData()"
                    class="w-16"
                />
                <input 
                    type="text"
                    x-model="activeChatbot.salesAgent.product_card.button_color"
                    @input="submitData()"
                    class="flex-1 px-3 py-2 border rounded-lg text-sm"
                    placeholder="#10b981"
                />
            </div>
        </div>

        {{-- Button Style --}}
        <div>
            <label class="block text-sm font-medium mb-2">Estilo del Botón</label>
            <select 
                x-model="activeChatbot.salesAgent.product_card.button_style"
                @change="submitData()"
                class="w-full px-3 py-2 border rounded-lg"
            >
                <option value="solid">Sólido</option>
                <option value="gradient">Gradiente</option>
                <option value="outline">Contorno</option>
            </select>
        </div>

        {{-- Card Shadow --}}
        <div>
            <label class="block text-sm font-medium mb-2">Sombra de la Tarjeta</label>
            <select 
                x-model="activeChatbot.salesAgent.product_card.card_shadow"
                @change="submitData()"
                class="w-full px-3 py-2 border rounded-lg"
            >
                <option value="none">Sin sombra</option>
                <option value="sm">Pequeña</option>
                <option value="md">Media</option>
                <option value="lg">Grande</option>
            </select>
        </div>

        {{-- Price Color --}}
        <div>
            <label class="block text-sm font-medium mb-2">Color del Precio</label>
            <div class="flex items-center gap-2">
                <div 
                    class="w-10 h-10 rounded-lg border-2 border-gray-300 cursor-pointer"
                    :style="{ backgroundColor: activeChatbot.salesAgent.product_card.price_color }"
                    @click="$refs.priceColorInput.click()"
                ></div>
                <input 
                    type="color"
                    x-ref="priceColorInput"
                    x-model="activeChatbot.salesAgent.product_card.price_color"
                    @input="submitData()"
                    class="w-16"
                />
            </div>
        </div>

        {{-- Toggles --}}
        <div class="space-y-2">
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    x-model="activeChatbot.salesAgent.product_card.show_stock_indicator"
                    @change="submitData()"
                    class="w-4 h-4"
                />
                <span class="ml-2">Mostrar indicador de stock</span>
            </label>
            <label class="flex items-center">
                <input 
                    type="checkbox" 
                    x-model="activeChatbot.salesAgent.product_card.show_discount_badge"
                    @change="submitData()"
                    class="w-4 h-4"
                />
                <span class="ml-2">Mostrar badge de descuento</span>
            </label>
        </div>
    </div>

    <hr>

    {{-- Sección 3: Preview en Vivo --}}
    <div class="space-y-4">
        <h3 class="font-semibold">Vista Previa</h3>
        
        <div class="bg-gray-50 p-6 rounded-lg">
            @include('ecommerce.partials.product-card-preview')
        </div>
    </div>

    <hr>

    {{-- Sección 4: Prompt Personalizado --}}
    <div class="space-y-4">
        <h3 class="font-semibold">Prompt Personalizado</h3>
        
        <textarea 
            x-model="activeChatbot.salesAgent.custom_prompt"
            @input="submitData()"
            rows="6"
            class="w-full px-3 py-2 border rounded-lg"
            placeholder="Instrucciones para entrenar cómo responde el agente..."
        ></textarea>
    </div>
</div>
```

### **Paso 3: Componente de Preview**

```blade
{{-- resources/views/ecommerce/partials/product-card-preview.blade.php --}}

<div 
    class="enhanced-product-card"
    :style="{
        border: '2px solid ' + activeChatbot.salesAgent.product_card.card_border_color,
        borderRadius: activeChatbot.salesAgent.product_card.card_border_radius,
        boxShadow: {
            'none': 'none',
            'sm': '0 1px 2px rgba(0,0,0,0.05)',
            'md': '0 4px 6px rgba(0,0,0,0.1)',
            'lg': '0 10px 15px rgba(0,0,0,0.1)'
        }[activeChatbot.salesAgent.product_card.card_shadow],
        overflow: 'hidden',
        background: 'white'
    }"
>
    {{-- Imagen --}}
    <img 
        src="https://via.placeholder.com/200x130"
        alt="Preview"
        style="width: 100%; height: 130px; object-fit: cover;"
    />
    
    {{-- Contenido --}}
    <div style="padding: 0.75rem;">
        {{-- Nombre --}}
        <div style="font-weight: 600; font-size: 0.85rem; margin-bottom: 0.5rem;">
            Muletas de Aluminio
        </div>
        
        {{-- Precio --}}
        <div 
            style="font-size: 1.1rem; font-weight: 700; margin-bottom: 0.5rem;"
            :style="{ color: activeChatbot.salesAgent.product_card.price_color }"
        >
            $89.900 COP
        </div>
        
        {{-- Badge de Descuento --}}
        <template x-if="activeChatbot.salesAgent.product_card.show_discount_badge">
            <div 
                style="display: inline-block; color: white; padding: 0.15rem 0.4rem; border-radius: 0.25rem; font-size: 0.7rem; font-weight: 600; margin-bottom: 0.5rem;"
                :style="{ backgroundColor: activeChatbot.salesAgent.product_card.discount_badge_color }"
            >
                -15%
            </div>
        </template>
        
        {{-- Botón Comprar --}}
        <button 
            style="width: 100%; border: none; padding: 0.6rem; border-radius: 0.5rem; font-weight: 600; font-size: 0.85rem; cursor: pointer;"
            :style="{
                backgroundColor: activeChatbot.salesAgent.product_card.button_style === 'solid' 
                    ? activeChatbot.salesAgent.product_card.button_color 
                    : 'transparent',
                color: activeChatbot.salesAgent.product_card.button_text_color,
                border: activeChatbot.salesAgent.product_card.button_style === 'outline'
                    ? `2px solid ${activeChatbot.salesAgent.product_card.button_color}`
                    : 'none',
                background: activeChatbot.salesAgent.product_card.button_style === 'gradient'
                    ? `linear-gradient(135deg, ${activeChatbot.salesAgent.product_card.button_color} 0%, ${activeChatbot.salesAgent.product_card.button_color}dd 100%)`
                    : undefined
            }"
        >
            🛒 Comprar
        </button>
    </div>
</div>
```

---

## 🔄 **Flujo de Guardado**

```
Usuario cambia color del botón
   ↓
x-model actualiza activeChatbot.salesAgent.product_card.button_color
   ↓
@input="submitData()" se ejecuta
   ↓
POST a /api/v1/chatbots/{id}
   ↓
Body: { salesAgent: { product_card: { button_color: '#10b981' } } }
   ↓
Controller guarda en SalesAgentConfig
   ↓
Preview se actualiza en tiempo real
```

---

## 📱 **Integración en Frontend (sales-agent-component.blade.php)**

```javascript
// Usar la configuración guardada
window.SalesAgent = {
    config: @json($config), // SalesAgentConfig
    
    createProductCardsHTML(products) {
        const config = this.config.product_card_config;
        
        let html = `<div class="sales-agent-products-grid" style="...">`;
        
        products.forEach(product => {
            html += `
                <div class="enhanced-product-card" style="
                    border: 2px solid ${config.card_border_color};
                    border-radius: ${config.card_border_radius};
                    box-shadow: ${this.getShadowStyle(config.card_shadow)};
                ">
                    <img src="${product.image_url}" />
                    <div style="padding: 0.75rem;">
                        <div style="color: ${config.price_color}; font-weight: 700;">
                            ${product.formatted_price}
                        </div>
                        <button style="
                            background-color: ${config.button_color};
                            color: ${config.button_text_color};
                        ">
                            🛒 Comprar
                        </button>
                    </div>
                </div>
            `;
        });
        
        return html;
    }
}
```

---

## ✅ **Checklist de Implementación**

- [ ] Extender `SalesAgentConfig` con `product_card_config`
- [ ] Crear migración para agregar columna JSON
- [ ] Crear vista Blade del tab Sales Agent
- [ ] Crear componente de preview
- [ ] Implementar color pickers con Alpine
- [ ] Agregar watchers para preview en tiempo real
- [ ] Integrar configuración en `sales-agent-component.blade.php`
- [ ] Testing de preview en tiempo real
- [ ] Testing de guardado de configuración
- [ ] Testing en chatbot externo

---

## 🎉 **Resultado Final**

El usuario podrá:
1. ✅ Configurar colores de botones
2. ✅ Elegir estilos de botones (sólido, gradiente, contorno)
3. ✅ Ver preview en tiempo real
4. ✅ Entrenar el agente con prompts personalizados
5. ✅ Todo se guarda automáticamente
6. ✅ El chatbot externo usa esta configuración

**Exactamente como el wizard del external chatbot.** 🚀
