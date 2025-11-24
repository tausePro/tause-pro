# 🚀 Arquitectura: Hub de Agentes de Ecommerce Marketing Auto-Gestionados

## 📋 Resumen Ejecutivo

**Visión**: Transformar tause Pro en el primer **Hub de Agentes de Ecommerce Marketing** que se auto-gestionan y entienden el negocio del cliente, permitiendo ventas conversacionales automatizadas con seguimiento completo de pedidos.

**Objetivo Principal**: Crear un ecosistema donde:
- El **External Chatbot** actúa como primer agente (atención al cliente)
- Un **Agent Orchestrator** inteligente activa agentes especializados según necesidad
- Los clientes pueden **comprar agentes pre-diseñados** o **conectar agentes custom**
- Los agentes de ventas se integran con múltiples plataformas (WooCommerce, Shopify, Wompi, Epayco)
- **Seguimiento automático** de pedidos con notificaciones vía WhatsApp

---

## ✅ Análisis de Viabilidad

### Fortalezas Actuales

1. ✅ **Base sólida existente**:
   - `AgentOrchestratorService` ya implementado
   - Sistema de extensiones modular funcionando
   - Integración WooCommerce operativa
   - Integración Wompi funcionando
   - Sistema de WhatsApp con Evolution API
   - Flujo de compra conversacional (`WhatsAppPurchaseFlowService`)

2. ✅ **Arquitectura extensible**:
   - Sistema de extensiones permite agregar nuevos agentes fácilmente
   - Tabla `ext_chatbot_agents` lista para múltiples agentes
   - Service Providers modulares

3. ✅ **Infraestructura lista**:
   - Base de datos con tablas necesarias
   - Sistema de eventos/listeners para comunicación
   - Cache system para estados de conversación

### Oportunidades de Mejora

1. 🔄 **Refactorizar AgentOrchestratorService**:
   - Actualmente tiene bugs (no activa agentes correctamente)
   - Necesita mejor detección de intención
   - Falta sistema de priorización inteligente

2. 🆕 **Nuevas integraciones necesarias**:
   - Shopify (no existe)
   - Epayco (no existe)
   - Sistema de seguimiento de pedidos mejorado

3. 🏪 **Marketplace de agentes**:
   - Sistema de compra/instalación de agentes
   - Agentes pre-diseñados vs custom
   - Sistema de licencias por agente

4. 📊 **Sistema de notificaciones**:
   - Webhooks de estados de pedidos
   - Notificaciones automáticas WhatsApp
   - Seguimiento de estados (pendiente, procesando, enviado, entregado)

---

## 🏗️ Arquitectura Propuesta

### 1. Arquitectura de Capas

```
┌─────────────────────────────────────────────────────────────┐
│                    FRONTEND LAYER                            │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │   Dashboard  │  │  Marketplace │  │  Agent Config│      │
│  │   Widgets    │  │  de Agentes  │  │  Interface   │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼─────────────────┼─────────────────┼─────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────┐
│                    API LAYER                                 │
│  ┌──────────────┐  ┌──────────────┐  ┌──────────────┐      │
│  │ Chatbot API  │  │ Agent API    │  │ Order API    │      │
│  │ (External)   │  │ Marketplace  │  │ Tracking API │      │
│  └──────┬───────┘  └──────┬───────┘  └──────┬───────┘      │
└─────────┼─────────────────┼─────────────────┼─────────────┘
          │                 │                 │
          ▼                 ▼                 ▼
┌─────────────────────────────────────────────────────────────┐
│                 ORCHESTRATION LAYER                          │
│  ┌──────────────────────────────────────────────────────┐   │
│  │      Agent Orchestrator Service (Mejorado)          │   │
│  │  - Intención del usuario (NLP mejorado)             │   │
│  │  - Priorización inteligente de agentes              │   │
│  │  - Contexto de conversación                         │   │
│  │  - Gestión de estado multi-agente                   │   │
│  └──────────────┬───────────────────────────────────────┘   │
└─────────────────┼─────────────────────────────────────────┘
                   │
    ┌──────────────┼──────────────┐
    │              │              │
    ▼              ▼              ▼
┌─────────┐  ┌─────────┐  ┌─────────────┐
│ External│  │  Sales  │  │  Support    │
│ Chatbot │  │  Agent  │  │  Agent      │
│ Agent   │  │         │  │  (Futuro)   │
└────┬────┘  └────┬────┘  └──────┬──────┘
     │            │              │
     ▼            ▼              ▼
┌─────────────────────────────────────────┐
│         INTEGRATION LAYER                │
│  ┌──────────┐  ┌──────────┐  ┌────────┐ │
│  │WooCommerce│ │ Shopify  │ │ Wompi  │ │
│  │  Service  │ │ Service  │ │ Service│ │
│  └─────┬────┘ └─────┬────┘ └────┬────┘ │
│        │            │           │       │
│  ┌─────┴────────────┴───────────┴─────┐ │
│  │   Order Tracking Service          │ │
│  │   - Estado de pedidos             │ │
│  │   - Webhooks                      │ │
│  │   - Notificaciones WhatsApp       │ │
│  └───────────────────────────────────┘ │
└─────────────────────────────────────────┘
```

### 2. Flujo de Comunicación

```
Cliente → External Chatbot Agent
              ↓
         Agent Orchestrator
              ↓
    ┌─────────┼─────────┐
    │         │         │
    ▼         ▼         ▼
Sales    Support   Marketing
Agent    Agent     Agent
    │         │         │
    └─────────┼─────────┘
              ↓
      Integration Layer
              ↓
    WooCommerce/Shopify
              ↓
      Order Created
              ↓
    Order Tracking Service
              ↓
    WhatsApp Notification
```

### 3. Estructura de Base de Datos Propuesta

#### Tablas Existentes (Mejorar)
- `ext_chatbot_agents` ✅ (ya existe, mejorar)
- `ext_chatbot_products` ✅ (ya existe)
- `ext_chatbots` ✅ (ya existe)

#### Nuevas Tablas Necesarias

```sql
-- Marketplace de Agentes
CREATE TABLE ext_agent_marketplace (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    slug VARCHAR(100) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category ENUM('sales', 'support', 'marketing', 'analytics', 'custom') NOT NULL,
    is_premium BOOLEAN DEFAULT FALSE,
    price DECIMAL(10,2) NULL,
    version VARCHAR(20) NOT NULL,
    configuration_schema JSON, -- Schema para configuración
    integration_requirements JSON, -- Requisitos de integración
    icon_url VARCHAR(500),
    screenshots JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);

-- Agentes Instalados por Cliente
CREATE TABLE ext_customer_agents (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    chatbot_id BIGINT UNSIGNED NOT NULL,
    agent_marketplace_id BIGINT UNSIGNED NULL, -- NULL si es custom
    agent_type VARCHAR(50) NOT NULL,
    name VARCHAR(255) NOT NULL,
    is_custom BOOLEAN DEFAULT FALSE,
    custom_configuration JSON,
    is_active BOOLEAN DEFAULT TRUE,
    installed_at TIMESTAMP,
    expires_at TIMESTAMP NULL, -- Para suscripciones
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (chatbot_id) REFERENCES ext_chatbots(id),
    FOREIGN KEY (agent_marketplace_id) REFERENCES ext_agent_marketplace(id)
);

-- Órdenes de Ecommerce (Unificado)
CREATE TABLE ext_ecommerce_orders (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chatbot_id BIGINT UNSIGNED NOT NULL,
    conversation_id BIGINT UNSIGNED NULL,
    customer_phone VARCHAR(20),
    customer_email VARCHAR(255),
    platform ENUM('woocommerce', 'shopify', 'custom') NOT NULL,
    platform_order_id VARCHAR(255) NOT NULL,
    order_number VARCHAR(100),
    status ENUM('pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    total DECIMAL(10,2) NOT NULL,
    currency VARCHAR(10) DEFAULT 'COP',
    payment_method VARCHAR(100),
    payment_gateway ENUM('wompi', 'epayco', 'stripe', 'paypal', 'other') NULL,
    payment_status ENUM('pending', 'paid', 'failed', 'refunded') DEFAULT 'pending',
    shipping_address JSON,
    billing_address JSON,
    items JSON, -- Array de productos
    metadata JSON, -- Datos adicionales
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (chatbot_id) REFERENCES ext_chatbots(id),
    FOREIGN KEY (conversation_id) REFERENCES ext_chatbot_conversations(id),
    INDEX idx_platform_order (platform, platform_order_id),
    INDEX idx_status (status),
    INDEX idx_conversation (conversation_id)
);

-- Seguimiento de Pedidos
CREATE TABLE ext_order_tracking (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    order_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(50) NOT NULL,
    message TEXT,
    location VARCHAR(255) NULL, -- Para tracking de envío
    estimated_delivery DATE NULL,
    tracking_number VARCHAR(100) NULL,
    notified_via_whatsapp BOOLEAN DEFAULT FALSE,
    notified_at TIMESTAMP NULL,
    created_at TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES ext_ecommerce_orders(id),
    INDEX idx_order_status (order_id, status)
);

-- Integraciones de Ecommerce por Chatbot
CREATE TABLE ext_ecommerce_integrations (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chatbot_id BIGINT UNSIGNED NOT NULL,
    platform ENUM('woocommerce', 'shopify') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    api_url VARCHAR(500),
    api_key VARCHAR(255),
    api_secret VARCHAR(255),
    webhook_url VARCHAR(500) NULL,
    webhook_secret VARCHAR(255) NULL,
    sync_products BOOLEAN DEFAULT TRUE,
    sync_orders BOOLEAN DEFAULT TRUE,
    last_sync_at TIMESTAMP NULL,
    configuration JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (chatbot_id) REFERENCES ext_chatbots(id),
    UNIQUE KEY unique_chatbot_platform (chatbot_id, platform)
);

-- Configuración de Pasarelas de Pago
CREATE TABLE ext_payment_gateways (
    id BIGINT UNSIGNED PRIMARY KEY AUTO_INCREMENT,
    chatbot_id BIGINT UNSIGNED NOT NULL,
    gateway ENUM('wompi', 'epayco', 'stripe', 'paypal') NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    public_key VARCHAR(255),
    private_key VARCHAR(255),
    test_mode BOOLEAN DEFAULT FALSE,
    configuration JSON,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (chatbot_id) REFERENCES ext_chatbots(id),
    UNIQUE KEY unique_chatbot_gateway (chatbot_id, gateway)
);
```

---

## 🎯 Mejores Prácticas Propuestas

### 1. Patrón de Diseño: Agent Pattern

Cada agente debe implementar una interfaz común:

```php
interface AgentInterface
{
    public function shouldActivate(string $userQuery, string $aiResponse, array $context): bool;
    public function process(Chatbot $chatbot, string $userQuery, array $context): array;
    public function getPriority(): int;
    public function getConfiguration(): array;
    public function validateConfiguration(): bool;
}
```

### 2. Sistema de Eventos para Comunicación

Usar eventos Laravel para comunicación entre agentes:

```php
// Eventos propuestos
- AgentActivated::class
- OrderCreated::class
- OrderStatusChanged::class
- ProductSynced::class
- PaymentProcessed::class
- NotificationSent::class
```

### 3. Service Layer Pattern

Separar responsabilidades en servicios específicos:

```
Services/
├── Agent/
│   ├── AgentOrchestratorService.php (refactorizado)
│   ├── AgentRegistryService.php (nuevo)
│   └── AgentConfigurationService.php (nuevo)
├── Ecommerce/
│   ├── WooCommerceService.php (mejorar)
│   ├── ShopifyService.php (nuevo)
│   ├── OrderService.php (nuevo)
│   └── ProductSyncService.php (nuevo)
├── Payment/
│   ├── WompiService.php (existe)
│   ├── EpaycoService.php (nuevo)
│   └── PaymentGatewayService.php (nuevo - factory)
└── Notification/
    ├── OrderTrackingService.php (nuevo)
    └── WhatsAppNotificationService.php (nuevo)
```

### 4. Repository Pattern para Ecommerce

```php
interface EcommerceRepositoryInterface
{
    public function createOrder(array $data): Order;
    public function updateOrderStatus(string $orderId, string $status): bool;
    public function getOrder(string $orderId): ?Order;
    public function syncProducts(Chatbot $chatbot): array;
}
```

### 5. Strategy Pattern para Pasarelas de Pago

```php
interface PaymentGatewayInterface
{
    public function createPaymentLink(Order $order): string;
    public function verifyPayment(string $transactionId): bool;
    public function refund(string $transactionId, float $amount): bool;
}

// Implementaciones
- WompiGateway implements PaymentGatewayInterface
- EpaycoGateway implements PaymentGatewayInterface
- StripeGateway implements PaymentGatewayInterface
```

---

## 🚀 Plan de Implementación

### Fase 1: Fundación (Semanas 1-2)

#### 1.1 Refactorizar AgentOrchestratorService
- ✅ Arreglar bugs de activación
- ✅ Mejorar detección de intención con NLP
- ✅ Implementar sistema de priorización
- ✅ Agregar contexto de conversación

#### 1.2 Crear Agent Registry System
- ⏳ `AgentRegistryService` para registrar agentes disponibles (PENDIENTE - No crítico para Fase 1)
- ✅ Sistema de carga dinámica de agentes (implementado en AgentOrchestratorService)
- ✅ Validación de configuración de agentes (implementado en ChatbotAgent)

#### 1.3 Mejorar External Chatbot Agent
- ✅ Convertir en agente base que siempre está activo
- ✅ Mejorar integración con orchestrator

### Fase 2: Sales Agent Mejorado (Semanas 3-4)

#### 2.1 Refactorizar Sales Agent
- ✅ Mejorar detección de intención de compra
- ✅ Integrar con ProductOrchestratorService
- ✅ Mejorar formato de productos

#### 2.2 Integración Shopify
- ✅ Crear `ShopifyService`
- ✅ Sincronización de productos
- ✅ Creación de órdenes
- ✅ Webhooks de Shopify

#### 2.3 Integración Epayco
- ✅ Crear `EpaycoService`
- ✅ Implementar `PaymentGatewayInterface`
- ✅ Generación de links de pago
- ✅ Verificación de pagos

### Fase 3: Order Tracking System (Semanas 5-6)

#### 3.1 Order Service Unificado
- ✅ Crear `OrderService` para todas las plataformas
- ✅ Tabla `ext_ecommerce_orders` unificada
- ✅ Migración de datos existentes

#### 3.2 Order Tracking Service
- ✅ Seguimiento de estados de pedidos
- ✅ Webhooks de plataformas
- ✅ Actualización automática de estados

#### 3.3 WhatsApp Notifications
- ✅ `WhatsAppNotificationService`
- ✅ Templates de mensajes
- ✅ Notificaciones automáticas por cambio de estado

### Fase 4: Marketplace de Agentes (Semanas 7-8)

#### 4.1 Marketplace Backend
- ✅ Tabla `ext_agent_marketplace`
- ✅ CRUD de agentes disponibles
- ✅ Sistema de categorías

#### 4.2 Sistema de Instalación
- ✅ `AgentInstallationService`
- ✅ Validación de requisitos
- ✅ Configuración inicial

#### 4.3 Dashboard UI
- ✅ Vista de marketplace
- ✅ Vista de agentes instalados
- ✅ Configuración de agentes

### Fase 5: Agent Custom System (Semanas 9-10)

#### 5.1 Agent Builder
- ✅ Interface para crear agentes custom
- ✅ Configuración de triggers
- ✅ Configuración de integraciones
- ✅ Testing de agentes

#### 5.2 API para Agentes Custom
- ✅ Webhooks para agentes externos
- ✅ API de configuración
- ✅ Sistema de autenticación

---

## 💡 Ideas Innovadoras

### 1. Agent Intelligence Layer

Usar embeddings para entender mejor el contexto:

```php
class AgentIntelligenceService
{
    public function analyzeIntent(string $query, string $context): array
    {
        // Usar embeddings del chatbot para entender mejor
        // Combinar con keywords tradicionales
        // Retornar score de confianza y tipo de intención
    }
}
```

### 2. Conversational Commerce Flow

Flujo completo de venta conversacional:

```
1. Cliente pregunta por producto
   ↓
2. Sales Agent detecta intención
   ↓
3. Muestra productos relevantes
   ↓
4. Cliente selecciona producto
   ↓
5. Recolecta datos (nombre, dirección, etc.)
   ↓
6. Genera link de pago (Wompi/Epayco)
   ↓
7. Cliente paga
   ↓
8. Crea orden en WooCommerce/Shopify
   ↓
9. Confirma orden vía WhatsApp
   ↓
10. Tracking automático de estados
```

### 3. Multi-Agent Collaboration

Agentes que trabajan juntos:

```php
// Ejemplo: Sales Agent + Support Agent
if ($salesAgent->detectsIssue()) {
    $supportAgent->activate();
    $supportAgent->shareContext($salesAgent->getContext());
}
```

### 4. Agent Analytics

Métricas por agente:

- Conversiones por agente
- Tiempo promedio de respuesta
- Satisfacción del cliente
- ROI por agente

### 5. Agent Templates

Agentes pre-configurados para diferentes industrias:

- Ecommerce General
- Ropa y Moda
- Electrónica
- Alimentos
- Servicios

---

## 🔧 Implementación Técnica Detallada

### 1. AgentOrchestratorService Refactorizado

```php
class AgentOrchestratorService
{
    protected AgentRegistryService $registry;
    protected AgentIntelligenceService $intelligence;
    
    public function orchestrate(
        Chatbot $chatbot, 
        string $userQuery, 
        string $aiResponse,
        array $context = []
    ): array {
        // 1. Obtener agentes disponibles del registry
        $availableAgents = $this->registry->getActiveAgents($chatbot);
        
        // 2. Analizar intención con AI
        $intent = $this->intelligence->analyzeIntent($userQuery, $aiResponse, $context);
        
        // 3. Evaluar qué agentes activar
        $activatedAgents = $this->evaluateAgents($availableAgents, $intent);
        
        // 4. Procesar agentes en orden de prioridad
        $results = [];
        foreach ($activatedAgents as $agent) {
            $result = $agent->process($chatbot, $userQuery, $context);
            $results[] = $result;
        }
        
        return [
            'message' => $aiResponse,
            'agents_activated' => $results,
            'intent' => $intent,
        ];
    }
}
```

### 2. Shopify Service

```php
class ShopifyService implements EcommerceRepositoryInterface
{
    public function syncProducts(Chatbot $chatbot): array
    {
        $integration = $this->getIntegration($chatbot, 'shopify');
        
        $products = Http::withHeaders([
            'X-Shopify-Access-Token' => $integration->api_secret,
        ])->get("{$integration->api_url}/admin/api/2024-01/products.json");
        
        // Sincronizar productos...
    }
    
    public function createOrder(array $data): Order
    {
        // Crear orden en Shopify
        // Guardar en ext_ecommerce_orders
    }
}
```

### 3. Order Tracking Service

```php
class OrderTrackingService
{
    public function updateOrderStatus(
        Order $order, 
        string $status, 
        ?string $message = null
    ): void {
        // Actualizar estado en BD
        $order->update(['status' => $status]);
        
        // Crear tracking entry
        OrderTracking::create([
            'order_id' => $order->id,
            'status' => $status,
            'message' => $message,
        ]);
        
        // Enviar notificación WhatsApp
        if ($order->conversation_id) {
            $this->notifyViaWhatsApp($order, $status);
        }
        
        // Disparar evento
        event(new OrderStatusChanged($order, $status));
    }
    
    protected function notifyViaWhatsApp(Order $order, string $status): void
    {
        $template = $this->getStatusTemplate($status);
        $message = str_replace('{order_number}', $order->order_number, $template);
        
        WhatsAppNotificationService::send(
            $order->customer_phone,
            $message
        );
    }
}
```

---

## 📊 Métricas de Éxito

### KPIs Propuestos

1. **Conversión**:
   - Tasa de conversión por agente
   - Conversión total del chatbot

2. **Engagement**:
   - Mensajes por conversación
   - Tiempo promedio de respuesta

3. **Ventas**:
   - Órdenes generadas
   - Valor promedio de orden
   - Revenue por agente

4. **Satisfacción**:
   - NPS del chatbot
   - Resolución de problemas

---

## 🎨 Dashboard UI Propuesto

### Nuevas Vistas

1. **Agent Hub** (`/dashboard/agents`)
   - Lista de agentes instalados
   - Agregar nuevo agente
   - Configurar agente

2. **Agent Marketplace** (`/dashboard/agents/marketplace`)
   - Catálogo de agentes disponibles
   - Filtros por categoría
   - Comprar/instalar agente

3. **Agent Builder** (`/dashboard/agents/builder`)
   - Crear agente custom
   - Configurar triggers
   - Configurar integraciones
   - Test de agente

4. **Order Management** (`/dashboard/orders`)
   - Lista de órdenes
   - Detalle de orden
   - Tracking de pedidos
   - Filtros por estado

5. **Ecommerce Integrations** (`/dashboard/ecommerce/integrations`)
   - Configurar WooCommerce
   - Configurar Shopify
   - Configurar pasarelas de pago
   - Sincronización de productos

---

## 🔐 Seguridad y Permisos

### Permisos Propuestos

- `agents.view` - Ver agentes
- `agents.create` - Crear agentes
- `agents.edit` - Editar agentes
- `agents.delete` - Eliminar agentes
- `agents.marketplace.purchase` - Comprar agentes
- `orders.view` - Ver órdenes
- `orders.manage` - Gestionar órdenes
- `integrations.manage` - Gestionar integraciones

---

## 🚦 Roadmap Sugerido

### Q1 2025: Fundación
- ✅ Refactorizar AgentOrchestratorService
- ✅ Crear sistema de registro de agentes
- ✅ Mejorar External Chatbot Agent

### Q2 2025: Sales Agent Completo
- ✅ Integración Shopify
- ✅ Integración Epayco
- ✅ Order Tracking System
- ✅ WhatsApp Notifications

### Q3 2025: Marketplace
- ✅ Marketplace de agentes
- ✅ Sistema de instalación
- ✅ Dashboard UI completo

### Q4 2025: Agent Custom
- ✅ Agent Builder
- ✅ API para agentes externos
- ✅ Templates de agentes

---

## 💰 Modelo de Negocio

### Pricing de Agentes

1. **Agentes Gratuitos**:
   - External Chatbot Agent
   - Sales Agent básico

2. **Agentes Premium**:
   - Sales Agent avanzado ($29/mes)
   - Support Agent ($19/mes)
   - Marketing Agent ($39/mes)

3. **Agentes Custom**:
   - Creación: $99 one-time
   - Mantenimiento: $49/mes

---

## 🎯 Conclusión

Esta arquitectura transforma MagicAI en una plataforma única de **Agent Commerce**, donde:

1. ✅ Los agentes se auto-gestionan y entienden el negocio
2. ✅ Los clientes pueden comprar agentes según necesidad
3. ✅ Las ventas conversacionales están completamente automatizadas
4. ✅ El seguimiento de pedidos es automático
5. ✅ Las notificaciones son proactivas

**Valor Agregado**:
- Primera plataforma de su tipo
- Escalable y extensible
- Modelo de negocio claro
- Diferenciación competitiva fuerte

**Próximos Pasos**:
1. Aprobar arquitectura
2. Crear plan de desarrollo detallado
3. Comenzar Fase 1 (Fundación)

---

**Documento creado**: 2025-12-nov
**Versión**: 1.0  
**Autor**: Arquitectura propuesta para tause Pro Hub de Agentes

