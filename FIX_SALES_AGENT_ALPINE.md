# 🔧 FIX: Sales Agent - Alpine.js Instance Not Found

## 🐛 PROBLEMA IDENTIFICADO:

El método `getChatbotInstance()` busca la instancia de Alpine.js (`chatbotEl.__x`) pero:
1. En el chatbot embebido, Alpine puede estar en un contexto diferente
2. El elemento `.lqd-ext-chatbot-window` existe pero `__x` es `undefined`
3. Después de 30 intentos (6 segundos), falla y no puede simular mensajes

## ✅ SOLUCIÓN:

Modificar `simulateUserMessage()` para que funcione **sin depender de Alpine.js**, usando el DOM y eventos directamente.

### **Opción A: Usar el input directamente**
En lugar de agregar a `chatbot.messages`, simular el comportamiento del usuario:
1. Encontrar el input del chatbot
2. Establecer su valor
3. Disparar el evento de envío

### **Opción B: Usar eventos personalizados**
Crear un evento personalizado que el chatbot escuche para agregar mensajes.

### **Opción C: Modificar el flujo (RECOMENDADO)**
En lugar de simular mensajes del usuario, mostrar el flujo de compra directamente en el chat sin necesidad de mensajes intermedios.

## 📝 IMPLEMENTACIÓN (Opción C - Más Simple):

Modificar `startPurchase()` para que:
1. NO simule mensaje del usuario
2. Muestre directamente el formulario de cantidad
3. Use `addAssistantMessage()` solo para mensajes del bot

Esto evita completamente el problema de Alpine.js.
