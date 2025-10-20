# Technology Stack

## Backend Framework
- **Laravel 10.x** - PHP framework
- **PHP 8.2+** - Required minimum version
- **MySQL** - Primary database
- **Redis** - Caching and sessions

## Frontend Technologies
- **Livewire 3.x** - Dynamic frontend components
- **Alpine.js** - Lightweight JavaScript framework
- **TailwindCSS** - Utility-first CSS framework
- **Vite** - Build tool and asset bundling
- **Blade** - Laravel templating engine

## Key Libraries & Packages
- **Laravel Cashier** - Subscription billing
- **Laravel Sanctum** - API authentication
- **Laravel Passport** - OAuth2 server
- **Spatie Permissions** - Role-based access control
- **Intervention Image** - Image processing
- **Laravel Octane** - Performance optimization
- **Laravel Telescope** - Debugging and monitoring

## AI & External Services
- OpenAI PHP Client
- AWS SDK for Bedrock
- Google Cloud Text-to-Speech
- Various payment gateway SDKs

## Development Tools
- **Laravel Pint** - Code formatting
- **Pest** - Testing framework
- **Laravel Debugbar** - Development debugging
- **Laravel Ray** - Debug tool

## Common Commands

### Development
```bash
# Start development server
php artisan serve

# Run Vite dev server
npm run dev

# Watch for changes
npm run watch
```

### Database
```bash
# Run migrations
php artisan migrate

# Seed database
php artisan db:seed

# Fresh migration with seeding
php artisan migrate:fresh --seed
```

### Code Quality
```bash
# Format code
composer run lint
# or
vendor/bin/pint

# Run tests
composer run test
# or
vendor/bin/pest

# Test with coverage
composer run test:coverage
```

### Cache & Optimization
```bash
# Clear all caches
php artisan optimize:clear

# Optimize for production
php artisan optimize

# Queue work
php artisan queue:work
```

### Package Management
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Build for production
npm run build
```