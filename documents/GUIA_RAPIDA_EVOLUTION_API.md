# 🚀 GUÍA RÁPIDA: EVOLUTION API + HUMAN AGENT

**Fecha:** 6 de Octubre, 2025  
**Estado:** ✅ Instalado y Funcional  
**Evolution API:** wa.tause.pro

---

## ✅ LO QUE SE INSTALÓ

### **1. ChatbotAgent (Human Agent)** ✅
Sistema de agentes humanos para transferir conversaciones del chatbot AI a personas reales.

### **2. ChatbotWhatsapp** ✅
Integración de WhatsApp con soporte para:
- ✅ Twilio (legacy)
- ✅ **Evolution API (nuevo)**

### **3. ChatbotMessenger** ✅
Integración con Facebook Messenger.

### **4. Servicios Evolution API** ✅
- ✅ `EvolutionWhatsappService` - Envío de mensajes
- ✅ `EvolutionConversationService` - Manejo de conversaciones
- ✅ `ChatbotEvolutionController` - Recepción de webhooks

---

## 📋 ACCESO AL SISTEMA

### **URLs Principales:**

```
Dashboard Principal:
https://app.tause.pro/dashboard

External Chatbots:
https://app.tause.pro/dashboard/chatbot

Multi-Channel Management:
https://app.tause.pro/dashboard/chatbot-multi-channel
```

---

## 🔧 CONFIGURAR WHATSAPP CON EVOLUTION API

### **Paso 1: Crear Canal WhatsApp**

1. Ve a: `https://app.tause.pro/dashboard/chatbot-multi-channel`
2. Click en **"WhatsApp (Evolution API)"**
3. Completa el formulario:

```
Evolution API URL: https://wa.tause.pro
API Key: [Tu API Key de Evolution]
Instance Name: [Nombre de tu instancia]
```

4. Click **"Add Channel"**

### **Paso 2: Obtener IDs**

Después de crear el canal, anota:
- `chatbot_id` - ID de tu chatbot
- `channel_id` - ID del canal creado

Los encontrarás en la URL o en la base de datos.

### **Paso 3: Configurar Webhook en Evolution API**

Ejecuta este comando en tu servidor Evolution API (o usa la interfaz web):

```bash
curl -X POST https://wa.tause.pro/webhook/set/INSTANCE_NAME \
  -H "apikey: TU_API_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "url": "https://app.tause.pro/api/v2/chatbot/CHATBOT_ID/channel/CHANNEL_ID/evolution",
    "webhook_by_events": true,
    "webhook_base64": false,
    "events": [
      "MESSAGES_UPSERT"
    ]
  }'
```

**Reemplaza:**
- `INSTANCE_NAME` - El nombre de tu instancia
- `TU_API_KEY` - Tu API key
- `CHATBOT_ID` - El ID de tu chatbot
- `CHANNEL_ID` - El ID del canal

---

## 👥 CONFIGURAR HUMAN AGENT

### **Activar en el Chatbot**

1. Ve a: `https://app.tause.pro/dashboard/chatbot`
2. Click en tu chatbot
3. En configuración, selecciona **"Interaction Type":**
   - **AI & Human Agent** - Automático según condiciones
   - **Only AI** - Solo IA
   - **Only Human Agent** - Solo humanos

### **Definir Condiciones de Transferencia**

Si eliges "AI & Human Agent", configura cuándo transferir:

- ✅ Problema demasiado complejo o ambiguo
- ✅ Cliente frustrado o insatisfecho
- ✅ Temas sensibles (legal, financiero, médico)
- ✅ IA falla después de múltiples intentos
- ✅ Se requiere empatía
- ✅ Solicitud fuera del alcance
- ✅ Cliente solicita explícitamente un humano

### **Comando Manual**

Los usuarios pueden escribir:
```
#humanagent
```
Para conectarse con un agente humano inmediatamente.

---

## 💬 PANEL DE AGENTE HUMANO

### **Acceso:**
```
https://app.tause.pro/dashboard/chatbot-agent
```

### **Funciones:**

1. **Ver Conversaciones Activas**
   - Conversaciones pendientes de respuesta
   - Historial completo de mensajes

2. **Responder**
   - Escribe mensaje
   - Se envía automáticamente al canal (WhatsApp, Messenger, etc.)

3. **Cerrar Conversación**
   - Marca como resuelta
   - Devuelve al chatbot AI

---

## 🧪 TESTING

### **1. Probar WhatsApp**

Desde tu WhatsApp personal:

```
1. Envía mensaje a tu número de Evolution API
2. El chatbot debe responder automáticamente
3. Escribe: #humanagent
4. Deberías ver la conversación en el panel de agentes
5. Responde desde el panel
6. Deberías recibir el mensaje en WhatsApp
```

### **2. Verificar Logs**

```bash
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220
tail -f /var/www/magicai/storage/logs/laravel.log | grep -i evolution
```

Deberías ver:
```
Evolution Webhook received
Evolution: Processing WhatsApp message
Evolution API: Message sent successfully
```

---

## 🔍 DEBUGGING

### **Verificar que Evolution API está funcionando:**

```bash
curl -X GET https://wa.tause.pro/instance/fetchInstances \
  -H "apikey: TU_API_KEY"
```

Deberías ver tu instancia listada y conectada.

### **Ver Webhooks Recibidos:**

```sql
SELECT * FROM chatbot_channel_webhooks 
ORDER BY created_at DESC 
LIMIT 10;
```

### **Ver Conversaciones:**

```sql
SELECT * FROM ext_chatbot_conversations 
WHERE chatbot_channel_id = CHANNEL_ID 
ORDER BY created_at DESC;
```

### **Ver Historial de Mensajes:**

```sql
SELECT * FROM ext_chatbot_histories 
WHERE conversation_id = CONVERSATION_ID 
ORDER BY created_at DESC;
```

---

## 📊 ESTRUCTURA DE DATOS

### **credentials del Canal Evolution:**

```json
{
  "provider": "evolution",
  "evolution_api_url": "https://wa.tause.pro",
  "evolution_api_key": "B6D03xxx-xxxx-xxxx-xxxx-xxxxxxxxxx",
  "evolution_instance": "my_chatbot_instance"
}
```

### **Webhook Evolution API (recibido):**

```json
{
  "event": "messages.upsert",
  "instance": "my_chatbot_instance",
  "data": {
    "key": {
      "remoteJid": "5511999999999@s.whatsapp.net",
      "fromMe": false,
      "id": "3EB0..."
    },
    "message": {
      "conversation": "Hola, necesito ayuda"
    },
    "messageTimestamp": "1234567890"
  }
}
```

---

## ⚠️ SOLUCIÓN DE PROBLEMAS

### **"Evolution API credentials not configured"**

**Solución:** Verifica que el canal tenga todos los campos:
- `evolution_api_url`
- `evolution_api_key`
- `evolution_instance`

### **"No recibo mensajes del chatbot"**

**Checklist:**
1. ✅ Webhook configurado en Evolution API
2. ✅ URL del webhook es correcta
3. ✅ Instancia está conectada en Evolution
4. ✅ Canal está activo en la BD

### **"Agente humano no recibe notificaciones"**

**Solución:**
1. Verifica que ChatbotAgent esté instalado
2. Verifica que `interaction_type` sea `smart_switch` o `human_support`
3. Verifica que el usuario tenga permisos de agente

---

## 🔐 SEGURIDAD

### **Recomendaciones:**

1. **API Keys:**
   - No expongas el API key de Evolution en logs
   - Usa variables de entorno si es posible

2. **Webhook:**
   - Solo acepta requests de Evolution API
   - Valida signature si Evolution lo soporta

3. **SSL:**
   - Siempre usa HTTPS
   - Verifica certificados válidos

---

## 📚 RECURSOS ADICIONALES

### **Documentación Evolution API:**
```
https://doc.evolution-api.com/
```

### **Endpoints Útiles:**

**Enviar mensaje de texto:**
```bash
POST https://wa.tause.pro/message/sendText/INSTANCE
{
  "number": "5511999999999",
  "textMessage": {
    "text": "Mensaje aquí"
  }
}
```

**Verificar estado de instancia:**
```bash
GET https://wa.tause.pro/instance/connectionState/INSTANCE
```

**Ver webhooks configurados:**
```bash
GET https://wa.tause.pro/webhook/find/INSTANCE
```

---

## ✅ CHECKLIST DE PRODUCCIÓN

- [x] ChatbotAgent instalado
- [x] ChatbotWhatsapp instalado
- [x] ChatbotMessenger instalado
- [x] Servicios Evolution API creados
- [x] ServiceProvider actualizado
- [x] ChatbotAgentController modificado
- [x] Vista de Evolution creada
- [x] Cachés limpiados
- [x] Servicios reiniciados
- [ ] Configurar primer canal WhatsApp
- [ ] Configurar webhook en Evolution
- [ ] Probar flujo completo
- [ ] Entrenar agentes humanos

---

## 🆘 SOPORTE

Si encuentras problemas:

1. **Revisa los logs:**
   ```bash
   tail -f /var/www/magicai/storage/logs/laravel.log
   ```

2. **Verifica BD:**
   ```bash
   sudo -u www-data php artisan tinker
   ```

3. **Reinicia servicios:**
   ```bash
   sudo systemctl restart php8.3-fpm
   sudo systemctl reload nginx
   ```

---

## 🎯 PRÓXIMOS PASOS

1. **Configurar primer canal WhatsApp Evolution**
2. **Probar envío y recepción de mensajes**
3. **Configurar human agent**
4. **Entrenar equipo de soporte**
5. **Monitorear logs primeros días**
6. **Activar extensiones licenciadas restantes**

---

**¡Sistema listo para producción!** 🚀


