# ChatCommerce Tause

Un sistema de agentes de IA para e-commerce conversacional construido con Laravel y Neuron AI.

## 🚀 Características

- **Agentes Especializados**: ProductAgent para recomendaciones y SalesAgent para el proceso de compra
- **Workflow Orquestado**: Flujo inteligente que detecta intenciones y dirige al agente correcto
- **Integración WooCommerce**: Conexión con tiendas WooCommerce para productos y órdenes
- **Integración Wompi**: Generación de links de pago para Colombia
- **API RESTful**: Endpoints para integración con frontend o chatbots externos

## 🏗️ Arquitectura

### Agentes

1. **ProductAgent**: Recomienda productos basado en consultas del cliente
2. **SalesAgent**: Maneja el proceso de compra paso a paso

### Workflow

**ChatcommerceWorkflow** orquesta los agentes:
- Detecta intención de compra
- Dirige al agente apropiado
- Maneja el contexto entre pasos

### API Endpoints

```
POST /api/chatcommerce/chat              # Chat general
POST /api/chatcommerce/start-purchase    # Iniciar compra
POST /api/chatcommerce/customer-info     # Procesar datos del cliente
POST /api/chatcommerce/create-order      # Crear orden final
```

## 🛠️ Instalación

1. **Clonar y configurar**:
```bash
cd chatcommerce-tause
composer install
cp .env.example .env
php artisan key:generate
```

2. **Configurar variables de entorno**:
```env
# Neuron AI
OPENAI_API_KEY=tu-openai-key
OPENAI_MODEL=gpt-4

# WooCommerce
WOOCOMMERCE_URL=https://tu-tienda.com
WOOCOMMERCE_KEY=tu-consumer-key
WOOCOMMERCE_SECRET=tu-consumer-secret

# Wompi
WOMPI_PUBLIC_KEY=tu-public-key
WOMPI_PRIVATE_KEY=tu-private-key
WOMPI_ENVIRONMENT=test
```

3. **Ejecutar migraciones**:
```bash
php artisan migrate
```

## 📖 Uso

### Ejemplo de Chat

```bash
curl -X POST http://localhost:8000/api/chatcommerce/chat \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer tu-token" \
  -d '{"message": "necesito unas muletas"}'
```

### Respuesta

```json
{
  "success": true,
  "response": "¡Perfecto! Tengo justo lo que necesitas 💚 Aquí te dejo algunas opciones de muletas:\n\n✨ **Muleta Convencional en Aluminio**\n- Ligera, ajustable en altura y brinda una asistencia segura\n- Precio: $102,000 COP\n- 🛒 Comprar aquí: Muleta Convencional en Aluminio\n\n✨ **Bastón Plegable con Empuñadura en T**\n- Perfecto para quienes buscan apoyo al caminar\n- Precio: $59,500 COP\n- 🛒 Comprar aquí: Bastón Plegable",
  "context": {
    "query": "necesito unas muletas",
    "purchase_started": true
  },
  "next_action": "start_sales_process"
}
```

### Ejemplo de Compra

```bash
curl -X POST http://localhost:8000/api/chatcommerce/start-purchase \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer tu-token" \
  -d '{"product_id": 1, "quantity": 1}'
```

## 🔧 Desarrollo

### Agregar Nuevos Agentes

1. Crear clase en `app/Agents/`
2. Extender `NeuronAI\Agent`
3. Implementar `provider()`, `instructions()`, `tools()`
4. Registrar en el Workflow

### Agregar Nuevas Herramientas

1. Crear `Tool` en el método `tools()` del agente
2. Implementar el handler
3. Documentar propiedades con `ToolProperty`

### Integrar con WooCommerce

Los métodos `searchProducts()` y `getProductDetails()` en `ProductAgent` necesitan implementación real con la API de WooCommerce.

## 📁 Estructura del Proyecto

```
app/
├── Agents/
│   ├── ProductAgent.php      # Recomendaciones de productos
│   └── SalesAgent.php        # Proceso de compra
├── Workflows/
│   └── ChatcommerceWorkflow.php  # Orquestación
├── Http/Controllers/Api/
│   └── ChatcommerceController.php # API endpoints
└── Services/                 # Servicios de integración
config/
└── neuron.php               # Configuración Neuron AI
routes/
└── api.php                  # Rutas API
```

## 🎯 Próximos Pasos

- [ ] Implementar integración real con WooCommerce
- [ ] Implementar integración real con Wompi
- [ ] Agregar persistencia de conversaciones
- [ ] Implementar RAG para productos
- [ ] Agregar tests unitarios
- [ ] Crear frontend de demostración

## 📚 Documentación

- [Neuron AI Docs](https://docs.neuron-ai.dev/)
- [Laravel Docs](https://laravel.com/docs)
- [WooCommerce REST API](https://woocommerce.github.io/woocommerce-rest-api-docs/)
- [Wompi API](https://docs.wompi.co/)

## 🤝 Contribuir

1. Fork el proyecto
2. Crear feature branch (`git checkout -b feature/nueva-funcionalidad`)
3. Commit cambios (`git commit -am 'Agregar nueva funcionalidad'`)
4. Push al branch (`git push origin feature/nueva-funcionalidad`)
5. Crear Pull Request

## 📄 Licencia

Este proyecto está bajo la Licencia MIT. Ver `LICENSE` para más detalles.