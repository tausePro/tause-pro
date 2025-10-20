# Funcionalidad de Conexión WhatsApp - Estilo Perplexity

## Descripción
Esta funcionalidad permite a los usuarios conectar su número de WhatsApp al chatbot de manera similar a como funciona Perplexity, proporcionando una experiencia de conexión simple y familiar.

## Características Implementadas

### 1. Botón de Conexión WhatsApp
- Se agregó un botón "📱 Connect WhatsApp" en la interfaz del chatbot
- Aparece junto al botón "Connect to agent" cuando el usuario necesita ayuda adicional
- Diseño con colores de WhatsApp (#25D366) para reconocimiento visual

### 2. Flujo de Conexión
1. **Usuario hace clic en "Connect WhatsApp"**
2. **Se solicita el número de WhatsApp** (con código de país)
3. **Se genera un token único** de conexión
4. **Se abre una ventana popup** con instrucciones
5. **Usuario envía comando `/conectar {token}`** a WhatsApp de soporte
6. **Sistema verifica y conecta** automáticamente

### 3. Páginas de Conexión
- **Página principal**: Instrucciones claras con botón para abrir WhatsApp
- **Página de token expirado**: Manejo de errores cuando el token expira
- **Verificación automática**: El sistema verifica el estado cada 5 segundos

### 4. API Endpoints
- `POST /api/v2/chatbot/{uuid}/session/{sessionId}/whatsapp/generate-link` - Genera enlace de conexión
- `POST /api/v2/chatbot/whatsapp/process-command` - Procesa comandos de WhatsApp
- `GET /api/v2/chatbot/whatsapp/connection-status/{token}` - Verifica estado de conexión
- `GET /chatbot/whatsapp/connect/{token}` - Página de instrucciones

## Configuración

### 1. Número de WhatsApp de Soporte
El número de WhatsApp de soporte se configura en la tabla `settings` con la columna `whatsapp_support_number`.

**Valor por defecto**: `+1234567890`

**Para cambiar el número**:
```sql
UPDATE settings SET whatsapp_support_number = '+1234567890' WHERE id = 1;
```

### 2. Base de Datos
Se agregó la columna `whatsapp_number` a la tabla `ext_chatbot_conversations` para almacenar el número de WhatsApp del usuario.

## Archivos Modificados

### Controladores
- `app/Extensions/Chatbot/System/Http/Controllers/Api/ChatbotWhatsappConnectionController.php` (NUEVO)

### Vistas
- `app/Extensions/Chatbot/resources/views/whatsapp-connection.blade.php` (NUEVO)
- `app/Extensions/Chatbot/resources/views/whatsapp-connection-expired.blade.php` (NUEVO)
- `app/Extensions/Chatbot/resources/views/frontend-ui/components/conversation-messages.blade.php` (MODIFICADO)

### JavaScript
- `app/Extensions/Chatbot/resources/views/frontend-ui/frontend-ui-scripts.blade.php` (MODIFICADO)

### Rutas
- `app/Extensions/Chatbot/System/ChatbotServiceProvider.php` (MODIFICADO)

### Base de Datos
- `database/migrations/2025_09_18_220753_add_whatsapp_number_to_ext_chatbot_conversations_table.php` (NUEVO)
- `database/seeders/WhatsappSupportNumberSeeder.php` (NUEVO)

### Modelos
- `app/Extensions/Chatbot/System/Models/ChatbotConversation.php` (MODIFICADO)

## Uso

### Para el Usuario Final
1. Inicia una conversación con el chatbot
2. Cuando necesites ayuda adicional, verás el botón "📱 Connect WhatsApp"
3. Haz clic y ingresa tu número de WhatsApp
4. Sigue las instrucciones en la ventana que se abre
5. Envía el comando a WhatsApp de soporte
6. ¡Listo! Tu WhatsApp estará conectado

### Para el Administrador
1. Configura el número de WhatsApp de soporte en la base de datos
2. Asegúrate de que el número esté verificado en WhatsApp Business
3. Los usuarios podrán conectarse automáticamente

## Seguridad
- Los tokens de conexión expiran en 10 minutos
- Se valida que el número de WhatsApp coincida con el registrado
- Los tokens se almacenan en caché de forma segura
- Verificación automática del estado de conexión

## Próximos Pasos
Para completar la implementación, necesitarás:
1. **Configurar el número de WhatsApp de soporte** en la base de datos
2. **Integrar con un servicio de WhatsApp** (Twilio, WhatsApp Business API, etc.) para enviar respuestas automáticas
3. **Probar el flujo completo** con un número real de WhatsApp

## Notas Técnicas
- La funcionalidad está diseñada para ser compatible con la arquitectura existente
- No rompe funcionalidades existentes
- Utiliza el sistema de caché de Laravel para los tokens
- Compatible con el sistema de conversaciones existente













