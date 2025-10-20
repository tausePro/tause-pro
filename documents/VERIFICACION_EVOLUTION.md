# 🔍 Verificación Evolution API + Chatbot

## 1. Verificar que Evolution API esté corriendo

```bash
curl -H "apikey: tause2024SecretKey" https://wa.tause.pro/
```

**Respuesta esperada:** `{"status":200,"message":"Welcome to the Evolution API..."}`

---

## 2. Verificar instancia "aliviate"

```bash
curl -H "apikey: tause2024SecretKey" https://wa.tause.pro/instance/fetchInstances | grep -A 10 "aliviate"
```

**Estado esperado:** `"connectionStatus": "open"` (después de escanear QR)

---

## 3. Probar envío de mensaje desde Evolution API

```bash
curl -X POST https://wa.tause.pro/message/sendText/aliviate%20 \
  -H "apikey: tause2024SecretKey" \
  -H "Content-Type: application/json" \
  -d '{
    "number": "573234059150",
    "text": "Hola desde Evolution API"
  }'
```

---

## 4. Ver logs de Evolution API

```bash
ssh -i evolution-key.pem ubuntu@13.220.81.41 'sudo docker logs evolution_api --tail 50'
```

---

## 5. Verificar webhook en app.tause.pro

**URL esperada:**
```
https://app.tause.pro/api/v2/chatbot/{CHATBOT_ID}/channel/{CHANNEL_ID}/evolution
```

**Formato del webhook en Evolution:**
- Activo: ✅
- URL: La que te dio app.tause.pro
- Eventos: Solo MESSAGES_UPSERT ✅

---

## 6. Probar flujo completo de asistencia humana

### a) Desde el chatbot embebido:
1. Inicia conversación
2. Escribe: "Necesito ayuda" o "Hablar con agente"
3. El chatbot debe ofrecer opciones

### b) Desde WhatsApp:
1. Envía mensaje al número de la instancia (573173637315)
2. Evolution API debe recibirlo
3. El webhook enviará el mensaje a app.tause.pro
4. Debe aparecer en Chat History

### c) Responder desde el panel:
1. Ve a: https://app.tause.pro/dashboard/chatbot
2. Click en "Chat History"
3. Busca la conversación de WhatsApp
4. Responde desde ahí
5. El usuario debe recibir la respuesta en WhatsApp

---

## 7. Troubleshooting

### Si no llegan mensajes:

```bash
# Ver logs en tiempo real
ssh -i evolution-key.pem ubuntu@13.220.81.41 'sudo docker logs -f evolution_api'
```

### Si no se conecta:

```bash
# Generar nuevo QR
curl -H "apikey: tause2024SecretKey" https://wa.tause.pro/instance/connect/aliviate%20
```

### Si hay errores de webhook:

```bash
# Verificar en DB de app.tause.pro
ssh -i magicai-tause-key.pem ubuntu@34.207.248.220 \
  'cd /var/www/magicai && mysql -u root -p magicai -e "SELECT * FROM ext_chatbot_channel_webhooks ORDER BY created_at DESC LIMIT 5;"'
```

---

## 📋 Checklist Final

- [ ] Evolution API responde en https://wa.tause.pro
- [ ] Instancia "aliviate" está conectada (QR escaneado)
- [ ] Canal de WhatsApp creado en app.tause.pro
- [ ] Webhook configurado en Evolution API
- [ ] Evento MESSAGES_UPSERT activado
- [ ] Mensaje de prueba enviado desde WhatsApp
- [ ] Mensaje aparece en Chat History
- [ ] Respuesta desde panel llega a WhatsApp

---

## 🔑 Credenciales Rápidas

```
Evolution API URL: https://wa.tause.pro
Global API Key: tause2024SecretKey
Instance Name: aliviate 
WhatsApp Number: 573173637315
```

---

## 🆘 Si algo falla:

1. Revisa logs de Evolution API
2. Verifica que el webhook esté activo
3. Confirma que MESSAGES_UPSERT esté marcado
4. Prueba enviar un mensaje de test desde Postman
5. Revisa la tabla `ext_chatbot_channel_webhooks` en la BD



