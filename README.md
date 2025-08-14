# Tause Pro - AI Platform

Plataforma de inteligencia artificial con integración de pagos colombianos (Wompi) y despliegue continuo.

## 🚀 Características

- **AI Content Generation**: Generación de contenido con múltiples modelos de IA
- **Chat AI**: Asistentes conversacionales personalizables
- **Image Generation**: Creación de imágenes con IA
- **Voice Services**: Texto a voz y clonación de voz
- **Wompi Integration**: Pasarela de pagos colombiana
- **Multi-tenant**: Soporte para equipos y suscripciones

## 🛠️ Tecnologías

- **Backend**: Laravel 10, PHP 8.2+, MySQL
- **Frontend**: TailwindCSS, Alpine.js, Livewire 3
- **AI Services**: OpenAI, Anthropic, Google Gemini, AWS Bedrock
- **Payments**: Wompi, Stripe, PayPal
- **Deployment**: GitHub Actions, AWS EC2

## 🔧 Instalación

### Requisitos
- PHP 8.2+
- MySQL 8.0+
- Node.js 18+
- Composer

### Configuración local
```bash
# Clonar repositorio
git clone https://github.com/TU_USUARIO/tause-pro.git
cd tause-pro

# Instalar dependencias
composer install
npm install

# Configurar entorno
cp .env.example .env
php artisan key:generate

# Base de datos
php artisan migrate
php artisan db:seed

# Compilar assets
npm run dev
```

## 🚀 Despliegue

El proyecto usa GitHub Actions para despliegue continuo:

1. Push a `main` ejecuta tests automáticamente
2. Si los tests pasan, despliega a producción
3. Optimiza automáticamente para producción

### Variables de entorno requeridas

```env
# Aplicación
APP_NAME="Tause Pro"
APP_URL=https://app.tause.pro

# Base de datos
DB_DATABASE=tause_pro
DB_USERNAME=tause_user
DB_PASSWORD=TausePro2025!

# Wompi (Colombia)
WOMPI_PUBLIC_KEY=pub_test_...
WOMPI_PRIVATE_KEY=prv_test_...
WOMPI_ENVIRONMENT=sandbox

# OpenAI
OPENAI_API_KEY=sk-...

# Otros servicios de IA
ANTHROPIC_API_KEY=...
GOOGLE_AI_API_KEY=...
```

## 💳 Integración Wompi

Wompi es la pasarela de pagos líder en Colombia. Características:

- ✅ Tarjetas de crédito y débito
- ✅ PSE (Pagos Seguros en Línea)
- ✅ Corresponsales bancarios
- ✅ Webhooks en tiempo real
- ✅ Sandbox para pruebas

### Configuración Wompi

1. Registrarse en https://comercios.wompi.co/
2. Obtener claves de API
3. Configurar webhook: `https://app.tause.pro/webhook/wompi`

## 📊 Monitoreo

- **Health Checks**: Verificación automática de servicios
- **Logs**: Laravel logs + Nginx logs
- **Backups**: Automáticos diarios
- **SSL**: Renovación automática con Let's Encrypt

## 🔐 Seguridad

- Autenticación 2FA
- Rate limiting
- CSRF protection
- SQL injection protection
- XSS protection

## 📝 Licencia

Propietario - Tause Pro

## 🤝 Contribución

Este es un proyecto privado. Para contribuir, contacta al equipo de desarrollo.

---

**Desarrollado con ❤️ para el mercado colombiano**