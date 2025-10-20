# Project Structure

## Root Directory Organization
```
├── app/                    # Application logic
├── bootstrap/              # Framework bootstrap files
├── config/                 # Configuration files
├── database/               # Migrations, seeders, factories
├── lang/                   # Localization files
├── packages/               # Custom packages
├── public/                 # Web-accessible files
├── resources/              # Views, assets, lang files
├── routes/                 # Route definitions
├── storage/                # File storage, logs, cache
├── tests/                  # Test files
└── vendor/                 # Composer dependencies
```

## App Directory Structure
```
app/
├── Actions/                # Business logic actions
├── Caches/                 # Custom cache implementations
├── Concerns/               # Reusable traits
├── Console/                # Artisan commands
├── Domains/                # Domain-driven design modules
│   ├── Engine/             # AI engine logic
│   ├── Entity/             # Entity management
│   └── Marketplace/        # Marketplace functionality
├── Enums/                  # Enumeration classes
├── Events/                 # Event classes
├── Exceptions/             # Custom exceptions
├── Http/                   # Controllers, middleware, requests
├── Jobs/                   # Queue jobs
├── Listeners/              # Event listeners
├── Livewire/               # Livewire components
├── Mail/                   # Mail classes
├── Models/                 # Eloquent models
├── Notifications/          # Notification classes
├── Packages/               # Third-party integrations
├── Policies/               # Authorization policies
├── Providers/              # Service providers
├── Rules/                  # Validation rules
├── Services/               # Service classes
└── View/                   # View composers and components
```

## Key Architectural Patterns

### Domain-Driven Design
- Core business logic organized in `app/Domains/`
- Each domain contains its own models, services, and logic
- Clear separation of concerns

### Service Layer Pattern
- Business logic encapsulated in service classes
- Located in `app/Services/` with subdirectories by feature
- Services handle complex operations and integrations

### Action Pattern
- Single-purpose action classes in `app/Actions/`
- Handle specific business operations
- Promote code reusability and testability

### Repository Pattern (Implicit)
- Eloquent models act as repositories
- Custom query logic in model scopes and methods

## Configuration Organization
```
config/
├── app.php                 # Main application config
├── database.php            # Database connections
├── services.php            # Third-party services
├── openai.php              # OpenAI configuration
├── gemini.php              # Google Gemini config
├── paypal.php              # PayPal settings
└── [service].php           # Individual service configs
```

## Database Structure
- Migrations follow Laravel conventions
- Extensive use of foreign keys and relationships
- Separate tables for different AI services and billing

## Frontend Organization
```
resources/
├── views/                  # Blade templates
│   ├── layouts/            # Layout templates
│   ├── components/         # Blade components
│   └── [feature]/          # Feature-specific views
├── js/                     # JavaScript files
├── css/                    # Stylesheets
└── lang/                   # Frontend translations
```

## Custom Packages
- Located in `packages/` directory
- Include custom OpenAI client, updater, and installer
- Symlinked for development, packaged for production

## Naming Conventions
- **Models**: PascalCase singular (User, UserOrder)
- **Controllers**: PascalCase with Controller suffix
- **Services**: PascalCase with Service suffix
- **Jobs**: PascalCase describing the action
- **Events**: PascalCase with Event suffix
- **Migrations**: snake_case with descriptive names
- **Routes**: kebab-case for URLs