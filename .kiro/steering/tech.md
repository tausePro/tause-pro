# Technology Stack

## Backend Framework
- **Laravel 10**: PHP framework with Eloquent ORM
- **PHP 8.2+**: Required minimum version
- **MySQL**: Primary database with utf8mb4 collation

## Frontend & Assets
- **Vite**: Modern build tool for asset compilation
- **TailwindCSS**: Utility-first CSS framework with custom theme system
- **Alpine.js**: Lightweight JavaScript framework
- **Livewire 3**: Full-stack framework for dynamic interfaces
- **Blade**: Laravel's templating engine

## Key Dependencies
- **OpenAI PHP Client**: AI model integration
- **AWS SDK**: Cloud services and Bedrock integration
- **Stripe/PayPal SDKs**: Payment processing
- **Intervention Image**: Image manipulation
- **Laravel Sanctum**: API authentication
- **Spatie Packages**: Permissions, health checks, PDF processing

## Development Tools
- **Pest**: Testing framework
- **Laravel Pint**: Code formatting
- **Laravel Telescope**: Debugging and monitoring
- **Supervisor**: Queue worker management

## Common Commands

### Development
```bash
# Install dependencies
composer install
npm install

# Environment setup
cp .env.example .env
php artisan key:generate

# Database
php artisan migrate
php artisan db:seed

# Asset compilation
npm run dev          # Development with hot reload
npm run build        # Production build
npm run watch        # Watch for changes

# Laravel optimization
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize

# Queue management
php artisan queue:work
php artisan queue:restart

# Testing
php artisan test
vendor/bin/pest
```

### Production Deployment
```bash
# Optimize for production
composer install --optimize-autoloader --no-dev
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Infrastructure
- **Nginx**: Web server
- **Redis**: Caching and sessions (optional)
- **Supervisor**: Process management for queues
- **Let's Encrypt**: SSL certificates