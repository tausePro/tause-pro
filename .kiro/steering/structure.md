# Project Structure & Organization

## Laravel Application Structure

### Core Application (`app/`)
- **Actions/**: Business logic actions (CreateActivity, EmailConfirmation, etc.)
- **Console/Commands/**: Artisan commands
- **Domains/**: Domain-driven design modules (Engine, Entity, Marketplace)
- **Enums/**: Type-safe enumerations for AI engines, permissions, roles
- **Events/**: Event classes for payment webhooks and user activities
- **Exceptions/**: Custom exception handlers
- **Http/**: Controllers, middleware, requests following Laravel conventions
- **Jobs/**: Queue jobs for email sending and payment processing
- **Livewire/**: Full-stack components for dynamic UI
- **Models/**: Eloquent models with relationships and business logic
- **Services/**: Service classes organized by domain (AI, Payment, etc.)

### Key Architectural Patterns

#### Domain Organization
```
app/Domains/
├── Engine/     # AI engine management
├── Entity/     # AI model entities
└── Marketplace/ # Marketplace functionality
```

#### Service Layer Pattern
```
app/Services/
├── Ai/              # AI service integrations
├── Payment/         # Payment processing
├── PaymentGateways/ # Gateway-specific implementations
├── Credits/         # Credit management
└── Common/          # Shared utilities
```

#### Extension System
```
app/Extensions/      # Modular extensions
packages/magicai/    # Custom packages
```

### Frontend Structure (`resources/views/`)
- **Theme-based organization**: Multiple themes with isolated assets
- **Blade components**: Reusable UI components
- **SCSS/JS per theme**: Theme-specific styling and behavior

### Database (`database/`)
- **Migrations**: Chronological database changes (200+ migrations)
- **Seeders**: Initial data population
- **Factories**: Test data generation

### Configuration (`config/`)
- **AI Services**: OpenAI, Gemini, DeepSeek, XAI configurations
- **Payment Gateways**: Multiple payment provider configs
- **Custom configs**: Theme, health checks, localization

## Naming Conventions

### Models
- Singular names (User, Plan, Subscription)
- Eloquent relationships follow Laravel conventions
- Use traits for shared functionality (HasEnumConvert, HasLog)

### Controllers
- Resource controllers for CRUD operations
- API controllers in separate namespace
- Livewire components for interactive features

### Services
- Descriptive names ending in "Service"
- Organized by domain/feature
- Interface-based for testability

### Database
- Snake_case table names
- Descriptive migration names with timestamps
- Foreign key constraints follow Laravel conventions

## File Organization Rules

### Concerns/Traits
- Shared functionality in `app/Concerns/`
- Reusable across multiple models/classes

### Helpers
- Global helper functions in `app/Helpers/helpers.php`
- Class-based helpers in `app/Helpers/Classes/`

### Extensions
- Modular features in `app/Extensions/`
- Self-contained with own resources and logic

### Packages
- Custom packages in `packages/` directory
- Composer autoloading with local repositories

## Asset Organization

### Themes
- Each theme has isolated SCSS/JS files
- Vite automatically discovers theme assets
- TailwindCSS with theme-specific configurations

### Build Process
- Vite handles asset compilation
- Automatic SCSS duplicate removal
- Theme-specific entry points