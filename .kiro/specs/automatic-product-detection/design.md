# Design Document

## Overview

El sistema de detección automática de productos debe integrarse seamlessly en el flujo de creación de chatbots cuando un usuario proporciona una URL. El diseño se enfoca en hacer que todo el proceso sea automático, desde la detección hasta la visualización, sin requerir intervención manual del usuario.

## Architecture

### High-Level Flow
```
Usuario proporciona URL → Validación URL → Escaneo automático → 
Extracción de productos → Guardado en BD → Asociación con chatbot → 
Actualización de interfaz → Chatbot listo con productos
```

### Integration Points
- **Chatbot Creation Wizard**: Integración en el paso de configuración inicial
- **Product Detection Service**: Servicio existente mejorado para automatización
- **Database Layer**: Modelos existentes con nuevas relaciones automáticas
- **Frontend Interface**: Actualización automática de la vista de productos

## Components and Interfaces

### 1. Automatic Product Detection Service

**Location**: `app/Extensions/Chatbot/System/Services/AutomaticProductDetectionService.php`

**Responsibilities**:
- Coordinar todo el flujo de detección automática
- Manejar validación de URLs y reintentos
- Integrar con ProductExtractor existente
- Gestionar asociación automática con chatbot

**Key Methods**:
```php
public function detectAndAssociateProducts(string $url, int $chatbotId): ProductDetectionResult
public function validateUrl(string $url): bool
public function retryDetection(string $url, int $maxRetries = 3): array
```

### 2. Enhanced Product Extractor

**Location**: `app/Extensions/Chatbot/System/Parsers/ProductExtractor.php` (existing, enhanced)

**Enhancements**:
- Mejor manejo de errores y timeouts
- Soporte para más tipos de sitios web
- Extracción mejorada de metadatos de productos
- Validación de datos extraídos

### 3. Chatbot Product Association Manager

**Location**: `app/Extensions/Chatbot/System/Services/ChatbotProductAssociationService.php`

**Responsibilities**:
- Crear asociaciones automáticas entre productos y chatbots
- Manejar duplicados y conflictos
- Actualizar configuración del chatbot con productos
- Generar conocimiento base automático

### 4. Frontend Integration Handler

**Location**: `app/Extensions/Chatbot/resources/js/automatic-product-detection.js`

**Responsibilities**:
- Mostrar progreso de detección en tiempo real
- Actualizar interfaz automáticamente cuando se completa
- Manejar estados de error y reintentos
- Integrar con wizard de creación existente

## Data Models

### Enhanced ChatbotProduct Model

**New Fields**:
```php
// Campos para tracking automático
'auto_detected' => 'boolean',
'detection_source_url' => 'string',
'detection_timestamp' => 'timestamp',
'detection_confidence' => 'float',
'last_validation' => 'timestamp'
```

### New ProductDetectionLog Model

**Location**: `app/Extensions/Chatbot/System/Models/ProductDetectionLog.php`

**Purpose**: Tracking de intentos de detección para debugging y analytics

**Fields**:
```php
'chatbot_id' => 'integer',
'source_url' => 'string',
'status' => 'enum[pending,success,failed,partial]',
'products_found' => 'integer',
'error_message' => 'text',
'detection_time' => 'float',
'retry_count' => 'integer',
'created_at' => 'timestamp'
```

## Error Handling

### URL Validation Errors
- **Invalid URL format**: Mensaje claro con sugerencias
- **URL not accessible**: Verificación de conectividad y sugerencias
- **Protected content**: Detección de login walls y notificación

### Detection Errors
- **Network timeouts**: Sistema de reintentos automáticos (3 intentos)
- **Parsing failures**: Fallback a métodos alternativos de extracción
- **Partial detection**: Guardar productos válidos, reportar problemas

### Integration Errors
- **Database failures**: Rollback automático y notificación
- **Association conflicts**: Resolución automática de duplicados
- **Frontend update failures**: Fallback a refresh manual

## Testing Strategy

### Unit Tests
- `AutomaticProductDetectionServiceTest`: Lógica de detección y asociación
- `ProductExtractorEnhancedTest`: Mejoras en extracción
- `ChatbotProductAssociationTest`: Lógica de asociación automática

### Integration Tests
- `AutomaticDetectionFlowTest`: Flujo completo end-to-end
- `WizardIntegrationTest`: Integración con wizard de creación
- `ErrorHandlingTest`: Manejo de errores y reintentos

### Frontend Tests
- `AutomaticDetectionUITest`: Interfaz de usuario y actualizaciones
- `ProgressIndicatorTest`: Indicadores de progreso y estados
- `ErrorDisplayTest`: Manejo de errores en frontend

## Performance Considerations

### Optimization Strategies
- **Async Processing**: Detección en background con updates en tiempo real
- **Caching**: Cache de resultados de URLs previamente escaneadas
- **Rate Limiting**: Prevenir abuse del sistema de detección
- **Timeout Management**: Timeouts apropiados para diferentes tipos de sitios

### Scalability
- **Queue Integration**: Usar Laravel queues para detección pesada
- **Database Indexing**: Índices optimizados para queries de productos
- **Memory Management**: Limpieza de memoria durante extracción masiva

## Security Measures

### Input Validation
- Sanitización de URLs de entrada
- Validación de dominios permitidos/bloqueados
- Prevención de SSRF attacks

### Data Protection
- Encriptación de URLs sensibles
- Anonimización de logs de detección
- Respeto a robots.txt y políticas de sitios

## Migration Strategy

### Database Changes
```sql
-- Agregar campos a tabla existente
ALTER TABLE ext_chatbot_products ADD COLUMN auto_detected BOOLEAN DEFAULT FALSE;
ALTER TABLE ext_chatbot_products ADD COLUMN detection_source_url VARCHAR(500);
ALTER TABLE ext_chatbot_products ADD COLUMN detection_timestamp TIMESTAMP;
ALTER TABLE ext_chatbot_products ADD COLUMN detection_confidence DECIMAL(3,2);

-- Nueva tabla para logs
CREATE TABLE ext_chatbot_product_detection_logs (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    chatbot_id BIGINT NOT NULL,
    source_url VARCHAR(500) NOT NULL,
    status ENUM('pending','success','failed','partial') NOT NULL,
    products_found INTEGER DEFAULT 0,
    error_message TEXT,
    detection_time DECIMAL(8,3),
    retry_count INTEGER DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_chatbot_status (chatbot_id, status),
    INDEX idx_url_status (source_url, status)
);
```

### Backward Compatibility
- Productos existentes mantienen funcionalidad actual
- Nuevos campos opcionales no afectan funcionalidad existente
- API endpoints mantienen compatibilidad con versiones anteriores