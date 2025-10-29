# 📋 PLAN: Implementación Vista de Configuración Sales Agent

**Fecha:** 23 de Octubre, 2025 - 2:30 PM

---

## ✅ ARCHIVOS YA EXISTENTES

### 1. Vista de Configuración del Sales Agent
- **Archivo:** `app/Extensions/Chatbot/resources/views/ecommerce/tabs/sales-agent-config.blade.php`
- **Estado:** ✅ Existe y está completa (301 líneas)
- **Contenido:**
  - Configuración de comportamiento del agente
  - Configuración de tarjetas de producto (colores, estilos)
  - Preview en vivo
  - Prompt personalizado

### 2. Vista Principal de E-commerce
- **Archivo:** `app/Extensions/Chatbot/resources/views/ecommerce/index.blade.php`
- **Estado:** ✅ Existe (431 líneas)
- **Contenido Actual:**
  - Configuración WooCommerce
  - Configuración Wompi
  - Configuración básica Sales Agent (keywords, activación)
  - Lista de productos

---

## 🎯 OBJETIVO

Integrar la vista detallada de configuración del Sales Agent (`sales-agent-config.blade.php`) en la vista principal usando un sistema de tabs.

---

## 📐 DISEÑO PROPUESTO

### Sistema de Tabs:

```
┌─────────────────────────────────────────────────────┐
│  🔌 Integraciones  |  🤖 Sales Agent  |  📦 Productos │
└─────────────────────────────────────────────────────┘

Tab 1: Integraciones
  - Configuración WooCommerce
  - Configuración Wompi
  - Botón: Sincronizar Productos

Tab 2: Sales Agent
  - Comportamiento del Agente (nombre, tono, estrategia)
  - Configuración de Tarjetas (colores, estilos)
  - Preview en Vivo
  - Prompt Personalizado
  - Keywords de Activación

Tab 3: Productos
  - Grid de productos sincronizados
  - Activar/Desactivar productos
  - Paginación
```

---

## 🔧 CAMBIOS NECESARIOS

### 1. Modificar `index.blade.php`
- Agregar Alpine.js para tabs: `x-data="{ activeTab: 'integrations' }"`
- Crear navegación de tabs
- Envolver contenido actual en Tab 1 (Integraciones)
- Crear Tab 2 con `@include('chatbot::ecommerce.tabs.sales-agent-config')`
- Mover lista de productos a Tab 3

### 2. Actualizar Controlador
- **Archivo:** `app/Extensions/Chatbot/System/Http/Controllers/ChatbotEcommerceController.php`
- Pasar `$salesAgentConfig` a la vista
- Crear método `saveSalesAgentConfig()` si no existe

### 3. Crear/Actualizar Modelo
- **Archivo:** `app/Extensions/Chatbot/System/Models/SalesAgentConfig.php`
- Métodos necesarios:
  - `getProductCardConfig()` - Retorna configuración de tarjetas
  - Relación con Chatbot

---

## 📝 CÓDIGO A IMPLEMENTAR

### Estructura de Tabs (Alpine.js):

```blade
<div x-data="{ activeTab: 'integrations' }">
    {{-- Navigation --}}
    <nav class="flex space-x-8 border-b">
        <button @click="activeTab = 'integrations'" 
                :class="activeTab === 'integrations' ? 'border-blue-500' : 'border-transparent'">
            🔌 Integraciones
        </button>
        <button @click="activeTab = 'sales-agent'" 
                :class="activeTab === 'sales-agent' ? 'border-blue-500' : 'border-transparent'">
            🤖 Sales Agent
        </button>
        <button @click="activeTab = 'products'" 
                :class="activeTab === 'products' ? 'border-blue-500' : 'border-transparent'">
            📦 Productos
        </button>
    </nav>

    {{-- Tab Content --}}
    <div x-show="activeTab === 'integrations'">
        {{-- WooCommerce + Wompi --}}
    </div>

    <div x-show="activeTab === 'sales-agent'" style="display: none;">
        <form action="{{ route('dashboard.chatbot.ecommerce.sales-agent.save', $chatbot) }}" method="POST">
            @csrf
            @include('chatbot::ecommerce.tabs.sales-agent-config')
            <button type="submit">Guardar</button>
        </form>
    </div>

    <div x-show="activeTab === 'products'" style="display: none;">
        {{-- Grid de productos --}}
    </div>
</div>
```

---

## ⚠️ PROBLEMA ACTUAL

El archivo `index.blade.php` se editó incorrectamente y quedó corrupto.

### Solución:
1. ✅ Backup creado: `index.blade.php.backup`
2. ⏳ Restaurar desde backup
3. ⏳ Aplicar cambios correctamente

---

## 🔄 PRÓXIMOS PASOS

1. Restaurar `index.blade.php` desde backup
2. Aplicar cambios de tabs correctamente
3. Verificar que el modelo `SalesAgentConfig` existe
4. Probar la vista en el navegador
5. Implementar el guardado de configuración

---

**Estado:** ⏸️ PAUSADO - Esperando restauración del archivo
