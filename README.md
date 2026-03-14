# BBC Commercial App

BBC Commercial App is a Symfony 7.4 business workflow platform for managing the full commercial cycle:

- Client and article catalog
- Sales orders (Bon de commande)
- Delivery notes (Bon de livraison)
- Invoicing (Facture)
- Payment allocation and invoice settlement
- Audit logging and reporting dashboards
- Role-based back-office administration

The interface is built with Twig + Bootstrap, with document generation in PDF format using configurable visual themes.

> [!TIP]
> Want to help improve this project? Contributions are welcome: open an issue, suggest an enhancement, or submit a pull request.

## Quick Navigation

- [Highlights](#highlights)
- [Tech Stack](#tech-stack)
- [Main Modules](#main-modules)
- [Quick Start (Local)](#quick-start-local)
- [Seeded Accounts (Development)](#seeded-accounts-development)
- [Useful Commands](#useful-commands)
- [Contributing](#contributing)
- [License](#license)

## Highlights

- End-to-end commercial flow from order to payment
- Fine-grained permissions system (roles + privileges)
- Logical cancellation workflows (orders, deliveries, invoices)
- Partial delivery and partial payment support
- Snapshotting of client and delivery data for document integrity
- PDF generation for delivery notes and invoices
- Configurable PDF themes with anchor-based layout positioning
- KPI dashboard and period-based reports
- Filterable audit logs for traceability
- French and English locale switching

## Tech Stack

- PHP 8.2+
- Symfony 7.4
- Doctrine ORM + Migrations
- Twig + Bootstrap 5
- Dompdf for PDF rendering
- MySQL (default local setup)
- Optional Docker Compose database service (PostgreSQL container)

## Main Modules

- Dashboard: business KPIs and recent activity
- Clients: CRUD, activation/deactivation, search and filters
- Articles: CRUD, activation/deactivation, search and filters
- Orders (`BonCommande`): creation, edition rules, cancellation, lifecycle states
- Deliveries (`BonLivraison`): draft/validated lifecycle, quantity validation, PDF export
- Invoices (`Facture`): invoice creation from validated deliveries, tax/remise/timbre handling, PDF export
- Payments: payment capture with multi-invoice allocation and automatic invoice status updates
- Reports: date-range aggregates (billing, collections, top clients/articles)
- Admin Users/Roles: user lifecycle, password reset, role/privilege management
- PDF Themes: upload/activate/manage branded templates for document output
- Audit Log: filterable activity history with actor and action context

## Project Structure

- `src/Controller`: business and admin controllers
- `src/Entity`: domain entities (users, roles, clients, orders, invoices, payments, etc.)
- `src/Form`: Symfony form types
- `src/Repository`: Doctrine repositories
- `src/Service`: business services (PDF, status sync, formatting)
- `templates/`: Twig views
- `migrations/`: schema migration history
- `public/uploads/pdf-themes/`: uploaded PDF theme backgrounds

## Quick Start (Local)

### 1. Prerequisites

- PHP >= 8.2
- Composer
- MySQL 8+

### 2. Install dependencies

```bash
composer install
```

### 3. Configure environment

Default `.env` uses MySQL:

```dotenv
DATABASE_URL="mysql://root:@127.0.0.1:3306/bbc_commercial?serverVersion=8.0&charset=utf8mb4"
```

Adjust credentials/host/database as needed (prefer `.env.local` for local overrides).

> [!IMPORTANT]
> Change the default application secret before running in any shared or production environment.

Current placeholder value:

```dotenv
APP_SECRET=change-me-in-env-local
```

Recommended setup:

1. Generate a strong secret:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

2. Put the generated value in `.env.local` (preferred, not committed):

```dotenv
APP_SECRET="your-generated-random-secret"
```

3. Keep `.env` and `.env.dev` placeholders unchanged for safety and team onboarding.

### 4. Create database and run migrations

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 5. Load fixtures (sample users, roles, clients, articles)

```bash
php bin/console doctrine:fixtures:load
```

### 6. Run the app

Use one of the following:

```bash
symfony server:start
```

or

```bash
php -S localhost:8000 -t public/
```

Then open: `http://localhost:8000/login`

## Seeded Accounts (Development)

After loading fixtures:

| Email | Password | Role |
| --- | --- | --- |
| `admin@bbc.local` | `admin123` | `ROLE_ADMIN` |
| `commercial@bbc.local` | `commercial123` | `ROLE_COMMERCIAL` |
| `comptable@bbc.local` | `comptable123` | `ROLE_COMPTABLE` |

These are development defaults only. Change/remove them before any shared environment.

## Docker Notes

The repository includes `compose.yaml` with a PostgreSQL service. Current application defaults are MySQL in `.env`.

If you want to use Docker PostgreSQL, update `DATABASE_URL` accordingly (for example in `.env.local`) and run migrations against that database.

## Roles and Access

Core access model:

- `ROLE_ADMIN`: full administrative access
- `ROLE_COMMERCIAL`: commercial workflow operations
- `ROLE_COMPTABLE`: mostly read-oriented accounting visibility

Security uses Symfony form login, user checker, and login throttling.

## Useful Commands

```bash
# List routes
php bin/console debug:router

# Recompute order delivery statuses from delivery notes
php bin/console app:orders:resync-delivery-statuses

# Clear cache
php bin/console cache:clear
```

## Reports and KPIs

The reports module provides date-range analysis, including:

- Orders, deliveries, invoices, payments counts
- Total billed vs collected
- Outstanding amount and collection rate
- Invoice status distribution
- Top clients and top articles

## PDF Theming

Admins can manage PDF themes by document type:

- Upload background images
- Configure field anchors (coordinates and sizes)
- Activate one theme per document type

This allows branded delivery note and invoice output without code changes.

## Current Quality Status

- Functional business modules are implemented and interconnected
- Migration-based schema management is in place
- No automated test suite is currently included in this repository

## Production Readiness Checklist

Before production deployment:

- Replace all default credentials and secrets
- Move secrets to secure environment variables
- Configure HTTPS and trusted proxy settings
- Use production cache and optimized autoload
- Enable backup and monitoring for the database
- Add automated tests and CI validation

## License

This project is licensed under the MIT License. See the LICENSE file for details.

## Contributing

Contributions are welcome and appreciated.

If this project helps you, please contribute by opening an issue, improving documentation, fixing a bug, or proposing a feature.

- Open an issue to discuss bugs, enhancements, or ideas.
- Fork the repository, create a feature branch, and submit a pull request.
- Keep changes focused, include clear commit messages, and describe testing steps in the PR.

If you are unsure where to start, open an issue and request a "good first issue" suggestion.
