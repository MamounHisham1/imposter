# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a **Laravel 12 Livewire Starter Kit** application with the following stack:
- **PHP 8.2+** with Laravel 12
- **Livewire 3** for reactive components
- **Flux UI Free Edition** component library
- **Tailwind CSS 4** (CSS-first configuration)
- **Laravel Fortify** for authentication
- **SQLite** database (default)
- **Vite** for frontend bundling

## Development Commands

### Application Setup
```bash
# Initial setup (first time)
composer run setup

# Development server (runs PHP server, queue, logs, and Vite concurrently)
composer run dev

# Alternative: Run Vite dev server only
npm run dev
```

### Building Assets
```bash
# Production build
npm run build

# Development build (watch mode)
npm run dev
```

### Testing
```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Auth/AuthenticationTest.php

# Run tests with filter
php artisan test --filter=testName

# Run unit tests
php artisan test --testsuite=Unit
```

### Code Quality
```bash
# Format code with Laravel Pint
vendor/bin/pint --dirty

# Format all code
vendor/bin/pint
```

### Database
```bash
# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Refresh database (rollback & migrate)
php artisan migrate:refresh
```

## Architecture & Structure

### Laravel 12 Streamlined Structure
- **No `app/Http/Kernel.php`** - Middleware configured in `bootstrap/app.php`
- **No `app/Console/Kernel.php`** - Console commands auto-registered from `app/Console/Commands/`
- **Service Providers** in `bootstrap/providers.php`
- **Minimal configuration** - follows Laravel 12's "slim by default" philosophy

### Livewire-First Architecture
- **Full-page Livewire components** for all interactive pages
- **Components in `App\Livewire`** namespace (not `App\Http\Livewire`)
- **Server-side state management** with validation in Livewire components
- **Flux UI components** used throughout (`<flux:button>`, `<flux:input>`, etc.)
- **Use `wire:model.live`** for real-time updates (deferred by default in Livewire 3)
- **Use `$this->dispatch()`** for events (not `emit` or `dispatchBrowserEvent`)

### Key Directories
- `app/Livewire/` - Livewire components (organized by feature)
- `app/Models/` - Eloquent models
- `app/Actions/Fortify/` - Fortify authentication actions
- `resources/views/` - Blade templates with Flux UI components
- `resources/css/` - Tailwind CSS 4 configuration (`app.css` uses `@import "tailwindcss"`)
- `routes/` - Route definitions (`web.php` for web routes)

### Authentication System
- **Laravel Fortify** with full feature set
- **Two-factor authentication** enabled with confirmation
- **Session driver**: database
- **Settings pages**: Profile, Password, Appearance, 2FA (all Livewire components)

## MCP & AI Tooling

### Laravel Boost MCP Server
This project includes **Laravel Boost** MCP server configured for:
- Claude Code (`.mcp.json`)
- Cursor (`.cursor/mcp.json`)
- VSCode (`.vscode/mcp.json`)

**Always use Boost tools when available:**
- `search-docs` - Version-specific Laravel ecosystem documentation
- `tinker` - Execute PHP for debugging
- `database-query` - Read from database
- `list-artisan-commands` - Check available Artisan commands
- `get-absolute-url` - Generate correct project URLs
- `browser-logs` - Read browser console logs

### Documentation Search
Use `search-docs` with multiple, broad queries:
```php
['rate limiting', 'routing rate limiting', 'routing']
```
**Do not include package names** - Boost automatically filters by installed packages.

## Code Conventions

### PHP Standards
- **PHP 8 constructor property promotion** in `__construct()`
- **Explicit return types** on all methods
- **PHPDoc blocks** over inline comments
- **Array shape type definitions** where appropriate
- **Always use curly braces** for control structures

### Livewire Patterns
- **Single root element** in component views
- **Use `wire:key`** in loops: `wire:key="item-{{ $item->id }}"`
- **Lifecycle hooks**: `mount()`, `updatedFoo()` for initialization and side effects
- **Validation in Livewire actions** (not in controllers)
- **Layout path**: `components.layouts.app` (not `layouts.app`)

### Frontend Patterns
- **Flux UI components** with `flux:` prefix when available
- **Tailwind CSS 4** utility classes
- **CSS custom properties** for theming in `resources/css/app.css`
- **Dark mode support** via `.dark` class and CSS custom properties
- **Use gap utilities** for spacing (not margins)

### Testing
- **PHPUnit** (not Pest) for all tests
- **Feature tests** for most functionality
- **Use model factories** in tests
- **Run minimal tests** with filters before finalizing
- **Test all paths**: happy, failure, and edge cases

## Common Issues & Solutions

### Vite Manifest Error
If you see: `"Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest"`
```bash
npm run build
# or
npm run dev
# or
composer run dev
```

### Frontend Changes Not Reflecting
Run one of:
```bash
npm run build
npm run dev
composer run dev
```

### Code Formatting
Always run before finalizing changes:
```bash
vendor/bin/pint --dirty
```

## GitHub Actions
CI/CD pipelines configured in `.github/workflows/`:
- `tests.yml` - Runs PHPUnit tests on push/pull request
- `lint.yml` - Runs Laravel Pint for code formatting

## Important Notes

1. **Never use `env()` outside config files** - Use `config('app.name')`
2. **Always validate form data** in Livewire actions
3. **Use Eloquent relationships** over raw queries
4. **Prevent N+1 problems** with eager loading
5. **Create tests** for all changes
6. **Check existing components** before creating new ones
7. **Follow existing patterns** in sibling files
8. **Use Artisan commands** with `--no-interaction` flag