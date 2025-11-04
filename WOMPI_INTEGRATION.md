# Integración Wompi - TausePro

## 📋 Descripción

Integración completa de Wompi como gateway de pagos para suscripciones en TausePro, con soporte para:
- Suscripciones mensuales y anuales
- Sistema de cupones y descuentos
- Descuentos condicionales (Discount Manager)
- Webhooks para procesamiento automático
- Métodos de pago colombianos (PSE, Nequi, Tarjetas, etc.)

---

## 🚀 Características

### ✅ Implementado

1. **WompiService** (`app/Services/PaymentGateways/WompiService.php`)
   - Creación de transacciones
   - Cálculo de precios con descuentos
   - Validación de cupones
   - Integración con Discount Manager
   - Manejo de webhooks
   - Cancelación de suscripciones

2. **WompiWebhookController** (`app/Http/Controllers/Finance/WompiWebhookController.php`)
   - Endpoint para webhooks de Wompi
   - Verificación de firmas
   - Procesamiento de eventos

3. **Rutas**
   - `POST /webhooks/wompi` - Webhook de Wompi
   - Integrado en `routes/webhooks.php`

---

## ⚙️ Configuración

### 1. Credenciales de Wompi

Agregar en la tabla `gateways`:

```sql
INSERT INTO gateways (code, name, mode, live_client_id, live_client_secret, live_app_id, is_active) 
VALUES (
    'wompi',
    'Wompi',
    'sandbox', -- o 'live' para producción
    'pub_test_xxxxx', -- Public Key
    'prv_test_xxxxx', -- Private Key  
    'test_events_xxxxx', -- Events Key (para webhooks)
    1
);
```

### 2. Configurar Webhook en Wompi

1. Ir al dashboard de Wompi
2. Configurar webhook URL: `https://app.tause.pro/webhooks/wompi`
3. Seleccionar eventos: `transaction.updated`
4. Copiar el Events Key y agregarlo en `live_app_id`

### 3. Variables de Entorno (Opcional)

```env
WOMPI_MODE=sandbox
WOMPI_PUBLIC_KEY=pub_test_xxxxx
WOMPI_PRIVATE_KEY=prv_test_xxxxx
WOMPI_EVENTS_KEY=test_events_xxxxx
```

---

## 💰 Sistema de Descuentos

### Cupones Simples

```php
// Crear cupón de bienvenida
Coupon::create([
    'code' => 'BIENVENIDA50',
    'discount' => 50.00, // 50%
    'limit' => -1, // Ilimitado
    'is_offer' => false,
]);
```

### Descuentos Condicionales (Discount Manager)

```php
// Cupón con duración limitada
ConditionalDiscount::create([
    'title' => 'Descuento Black Friday',
    'coupon_id' => $coupon->id,
    'type' => 'percentage',
    'amount' => 30.00,
    'duration' => 'first_month', // first_month, first_year, all_time
    'total_usage_limit' => 100,
    'allow_once_per_user' => true,
    'active' => true,
    'scheduled' => true,
    'start_date' => '2025-11-25',
    'end_date' => '2025-11-30',
]);
```

---

## 🔄 Flujo de Pago

### 1. Usuario Selecciona Plan

```php
$user = Auth::user();
$plan = Plan::find($planId);
$couponCode = 'BIENVENIDA50'; // Opcional
```

### 2. Crear Suscripción

```php
use App\Services\PaymentGateways\WompiService;

$result = WompiService::subscribe($user, $plan, $couponCode);

// Resultado:
[
    'success' => true,
    'order_id' => 'WMP-ABC123XYZ',
    'transaction_id' => 'wompi_transaction_id',
    'checkout_url' => 'https://checkout.wompi.co/l/xxxxx',
    'payment_link' => 'https://checkout.wompi.co/l/xxxxx',
]
```

### 3. Redirigir a Checkout

```php
return redirect($result['checkout_url']);
```

### 4. Usuario Paga en Wompi

- Selecciona método de pago (PSE, Nequi, Tarjeta, etc.)
- Completa el pago
- Wompi envía webhook a `/webhooks/wompi`

### 5. Webhook Procesa Pago

```php
// Automático
- Verifica firma del webhook
- Actualiza orden a 'Success'
- Crea/actualiza suscripción
- Actualiza créditos del usuario
- Marca cupón como usado
- Crea log de actividad
```

---

## 📊 Métodos de Pago Disponibles

```php
WompiService::getPaymentMethods();

// Retorna:
[
    'CARD' => 'Tarjeta de Crédito/Débito',
    'NEQUI' => 'Nequi',
    'PSE' => 'PSE (Débito Bancario)',
    'BANCOLOMBIA_TRANSFER' => 'Transferencia Bancolombia',
    'BANCOLOMBIA_QR' => 'QR Bancolombia',
]
```

---

## 🔐 Seguridad

### Verificación de Webhooks

```php
// Automático en WompiService::verifyWebhookSignature()
$signature = hash_hmac('sha256', $timestamp . $payload, $eventsKey);
return hash_equals($expectedSignature, $receivedSignature);
```

### Validación de Cupones

```php
// Automático en WompiService::validateCoupon()
- Verifica que el cupón existe
- Verifica límite de usos
- Verifica que el usuario no lo haya usado
- Verifica fechas de vigencia (si aplica)
```

---

## 🧪 Testing

### Sandbox de Wompi

**Tarjetas de prueba:**

```
Aprobada:
- Número: 4242 4242 4242 4242
- CVV: 123
- Fecha: Cualquier fecha futura

Declinada:
- Número: 4000 0000 0000 0002
```

**PSE de prueba:**
- Banco: Banco de Pruebas
- Usuario: cualquiera
- Contraseña: cualquiera

### Probar Webhook Localmente

```bash
# Usar ngrok para exponer localhost
ngrok http 8000

# Configurar webhook en Wompi con URL de ngrok
https://xxxxx.ngrok.io/webhooks/wompi
```

---

## 📝 Logs

Todos los eventos se registran en `storage/logs/laravel.log`:

```
[Wompi Webhook Received] - Evento recibido
[Wompi: Payment approved] - Pago aprobado
[Wompi: Payment declined] - Pago rechazado
[Wompi: Subscription cancelled] - Suscripción cancelada
```

---

## 🔄 Integración con Checkout Registration

### Agregar Wompi como Gateway

```php
// En CheckoutRegistrationController
private function chooseService(): WompiService|StripeService|PaypalService
{
    return match (setting('default_checkout_gateway', 'stripe')) {
        'stripe' => new StripeService,
        'paypal' => new PaypalService,
        'wompi'  => new WompiService, // ← NUEVO
        default  => throw new Exception('Gateway not found'),
    };
}
```

### Actualizar Settings

```php
setting(['default_checkout_gateway' => 'wompi'])->save();
```

---

## 🚨 Troubleshooting

### Webhook no se recibe

1. Verificar que la URL es accesible públicamente
2. Verificar que el Events Key es correcto
3. Revisar logs de Wompi en su dashboard
4. Verificar logs en `storage/logs/laravel.log`

### Pago aprobado pero no se activa suscripción

1. Verificar que el webhook se está recibiendo
2. Revisar logs para ver errores
3. Verificar que el `reference` coincide con el `order_id`

### Cupón no aplica descuento

1. Verificar que el cupón existe y está activo
2. Verificar límite de usos
3. Verificar que el usuario no lo ha usado antes
4. Revisar fechas de vigencia (si aplica)

---

## 📚 Referencias

- [Documentación Wompi](https://docs.wompi.co/)
- [API Reference](https://docs.wompi.co/docs/en/api)
- [Webhooks](https://docs.wompi.co/docs/en/webhooks)
- [Métodos de Pago](https://docs.wompi.co/docs/en/payment-methods)

---

## ✅ Checklist de Implementación

- [x] Crear WompiService
- [x] Crear WompiWebhookController
- [x] Agregar rutas de webhook
- [x] Integrar con sistema de cupones
- [x] Integrar con Discount Manager
- [ ] Agregar Wompi a lista de gateways en UI
- [ ] Crear vistas de configuración
- [ ] Integrar con Checkout Registration
- [ ] Testing en sandbox
- [ ] Documentación de usuario
- [ ] Deploy a producción

---

## 🎯 Próximos Pasos

1. **Agregar Wompi a UI de gateways**
   - Agregar en `PaymentProcessController`
   - Agregar en vistas de configuración

2. **Integrar con Checkout Registration**
   - Modificar `CheckoutRegistrationController`
   - Actualizar vistas de registro

3. **Testing Completo**
   - Probar flujo completo en sandbox
   - Probar cupones simples
   - Probar descuentos condicionales
   - Probar webhooks

4. **Deploy**
   - Configurar credenciales de producción
   - Configurar webhook en producción
   - Monitorear primeros pagos

---

**Desarrollado para TausePro**
**Fecha:** Noviembre 2025
**Versión:** 1.0.0
