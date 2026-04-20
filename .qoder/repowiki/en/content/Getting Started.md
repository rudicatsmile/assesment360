# Getting Started

<cite>
**Referenced Files in This Document**
- [README.md](file://README.md)
- [composer.json](file://composer.json)
- [config/app.php](file://config/app.php)
- [config/database.php](file://config/database.php)
- [config/rbac.php](file://config/rbac.php)
- [routes/web.php](file://routes/web.php)
- [database/migrations/2026_04_17_093035_create_roles_table.php](file://database/migrations/2026_04_17_093035_create_roles_table.php)
- [database/migrations/2026_04_17_093235_add_role_id_to_users_table.php](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php)
- [database/migrations/0001_01_01_000000_create_users_table.php](file://database/migrations/0001_01_01_000000_create_users_table.php)
- [database/seeders/RoleSeeder.php](file://database/seeders/RoleSeeder.php)
- [app/Models/User.php](file://app/Models/User.php)
- [app/Models/Role.php](file://app/Models/Role.php)
- [app/Http/Middleware/EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
- [app/Http/Middleware/EnsureUserIsAdmin.php](file://app/Http/Middleware/EnsureUserIsAdmin.php)
- [app/Http/Controllers/Admin/RoleController.php](file://app/Http/Controllers/Admin/RoleController.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Prerequisites](#prerequisites)
3. [Installation](#installation)
4. [Environment Configuration](#environment-configuration)
5. [Database Setup](#database-setup)
6. [Role Management System](#role-management-system)
7. [Basic Usage](#basic-usage)
8. [Admin Panels and Dashboards](#admin-panels-and-dashboards)
9. [First-Time User Guidance](#first-time-user-guidance)
10. [Common Setup Issues and Troubleshooting](#common-setup-issues-and-troubleshooting)
11. [Conclusion](#conclusion)

## Introduction
This guide helps you set up and use the assessment platform quickly. It covers prerequisites, installation, environment configuration, database migrations, role management setup, and basic usage. You will learn how to access admin panels, understand the role-based access control (RBAC) system, and resolve common setup issues.

## Prerequisites
- PHP 8.3 or higher
- Composer (PHP dependency manager)
- Node.js and npm (for asset compilation)
- A web server (Apache/Nginx) or PHP built-in server
- Database client (SQLite, MySQL, MariaDB, PostgreSQL, or SQL Server)
- Git (recommended for version control)

These requirements are reflected in the project configuration and scripts.

**Section sources**
- [composer.json:8-14](file://composer.json#L8-L14)

## Installation
Follow these steps to install and prepare the application:

1. Clone the repository and navigate to the project directory.
2. Install PHP dependencies:
   - Run: `composer install`
3. Prepare the environment file:
   - Copy `.env.example` to `.env` if it does not exist.
   - Generate the application key: `php artisan key:generate`
4. Run database migrations:
   - Apply all migrations: `php artisan migrate`
5. Install frontend dependencies:
   - Install packages: `npm install --ignore-scripts`
6. Build assets:
   - Compile assets: `npm run build`
7. Start the application:
   - Development server: `php artisan serve`
   - Queue listener: `php artisan queue:listen --tries=1 --timeout=0`
   - Frontend hot reload: `npm run dev`

The project includes a convenience script that automates the entire setup process in one command:
- Run: `composer run setup`

Notes:
- The setup script performs migration and asset build automatically.
- The development script runs the Laravel server, queue listener, log tailing, and Vite in parallel.

**Section sources**
- [composer.json:37-44](file://composer.json#L37-L44)
- [composer.json:45-48](file://composer.json#L45-L48)
- [composer.json:66-72](file://composer.json#L66-L72)

## Environment Configuration
Configure your environment variables to match your deployment target. Key areas:

- Application settings:
  - Name, copyright, environment, debug, URL, timezone, locale, encryption key, and maintenance mode are defined in the application config.
- Database selection:
  - Default connection is SQLite by default. Switch to MySQL, MariaDB, PostgreSQL, or SQL Server by setting the appropriate variables in your `.env`.
  - Connection-specific settings (host, port, database, username, password, charset, collation, SSL mode) are configured per connection type.
- Redis:
  - Configure client, cluster, prefix, persistence, and database indices for default and cache clients.

Important defaults and options:
- Default database connection: sqlite
- Redis client: phpredis
- Maintenance driver: file or cache

**Section sources**
- [config/app.php:16](file://config/app.php#L16)
- [config/app.php:42](file://config/app.php#L42)
- [config/app.php:55](file://config/app.php#L55)
- [config/app.php:68](file://config/app.php#L68)
- [config/app.php:81](file://config/app.php#L81)
- [config/app.php:94](file://config/app.php#L94)
- [config/app.php:113](file://config/app.php#L113)
- [config/database.php:20](file://config/database.php#L20)
- [config/database.php:35-115](file://config/database.php#L35-L115)
- [config/database.php:146-182](file://config/database.php#L146-L182)

## Database Setup
The platform uses Laravel migrations and seeders to initialize the database schema and RBAC roles.

- Initial schema:
  - Users table with legacy role field and timestamps.
  - Roles table with unique name and slug, percentage threshold, and activation flag.
  - Migration adds a foreign key `role_id` to users and backfills existing records from the legacy `role` column.
- Seeders:
  - RoleSeeder creates roles from configuration-defined definitions.

Recommended commands:
- Fresh install with seeders: `php artisan migrate:fresh --seed`
- Or run migrations followed by role seeding:
  - `php artisan migrate`
  - `php artisan db:seed --class=RoleSeeder`
  - Clear config cache: `php artisan config:clear`

Notes:
- The users table was initially created with a string role column. A subsequent migration introduces a foreign key to roles and migrates data.
- The role definitions are loaded from configuration.

**Section sources**
- [database/migrations/0001_01_01_000000_create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [database/migrations/2026_04_17_093035_create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [database/migrations/2026_04_17_093235_add_role_id_to_users_table.php:15-29](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L29)
- [database/seeders/RoleSeeder.php:16-23](file://database/seeders/RoleSeeder.php#L16-L23)
- [README.md:68-78](file://README.md#L68-L78)

## Role Management System
The RBAC system is centralized in configuration and enforced by middleware and models.

- Configuration:
  - Role slugs, aliases, labels, and dashboard paths are defined in the RBAC config.
  - Middleware aliases map logical gates to route middleware.
  - Admin route prefix and name are configurable.
- Models:
  - User model resolves current role slug, checks admin/evaluator status, and determines role permissions.
  - Role model defines fillable attributes, casts, and relationship to users.
- Middleware:
  - EnsureUserHasRole: authorizes access based on one or more role slugs.
  - EnsureUserIsAdmin: restricts access to administrators.
- Controllers:
  - RoleController manages CRUD operations for roles with validation and authorization checks.

Key endpoints and pages:
- Admin role management page: `/admin/roles`
- API endpoints:
  - GET `/api/roles`
  - POST `/api/roles`
  - PUT `/api/roles/{id}`
  - DELETE `/api/roles/{id}`

CI guard:
- A Composer script validates that role slugs are only defined in the RBAC configuration file to prevent hardcoded literals elsewhere.

**Section sources**
- [config/rbac.php:4-63](file://config/rbac.php#L4-L63)
- [app/Models/User.php:59-92](file://app/Models/User.php#L59-L92)
- [app/Models/Role.php:13-29](file://app/Models/Role.php#L13-L29)
- [app/Http/Middleware/EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [app/Http/Middleware/EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [app/Http/Controllers/Admin/RoleController.php:14-129](file://app/Http/Controllers/Admin/RoleController.php#L14-L129)
- [routes/web.php:131-146](file://routes/web.php#L131-L146)
- [README.md:80-95](file://README.md#L80-L95)

## Basic Usage
After completing setup:

1. Visit the login page and authenticate.
2. Access your dashboard based on your role:
   - Admin dashboards: `/admin/dashboard`
   - Evaluator dashboards:
     - Teacher: `/fill/dashboard/guru`
     - Staff: `/fill/dashboard/staff`
     - Parent: `/fill/dashboard/parent`
   - General questionnaires: `/fill/questionnaires`
3. Navigate to admin panels:
   - Roles: `/admin/roles`
   - Users: `/admin/users`
   - Departments: `/admin/departments`
   - Questionnaires: `/admin/questionnaires`
4. Use the analytics section to explore department and role metrics.

Routing and middleware:
- Routes are grouped by role gates and admin prefixes.
- Middleware ensures only authorized users can access admin or evaluator sections.

**Section sources**
- [routes/web.php:29-34](file://routes/web.php#L29-L34)
- [routes/web.php:57-59](file://routes/web.php#L57-L59)
- [routes/web.php:72-147](file://routes/web.php#L72-L147)
- [config/rbac.php:49-62](file://config/rbac.php#L49-L62)

## Admin Panels and Dashboards
- Admin Dashboard: `/admin/dashboard`
- Analytics: `/admin/analytics`
- Roles Directory: `/admin/roles`
- Users Directory: `/admin/users`
- Departments Directory: `/admin/departments`
- Questionnaires Management: `/admin/questionnaires`
- Exports:
  - All questionnaires report
  - Per-questionnaire export
  - Department analytics (Excel/PDF)

Navigation flow:
- After login, users are redirected to a role-specific dashboard route.
- Admin routes are protected by admin gate middleware and prefixed according to configuration.

**Section sources**
- [routes/web.php:74-146](file://routes/web.php#L74-L146)
- [config/rbac.php:37-40](file://config/rbac.php#L37-L40)

## First-Time User Guidance
- Set up the database and seed roles using the recommended commands.
- Log in and confirm your role-based dashboard loads correctly.
- Explore the analytics accordion:
  - Click a department row to load role analytics.
  - Expand a role to see assigned users (async loading).
  - Collapse or switch roles as needed.
- Use the admin panels to manage roles, users, departments, and questionnaires.

**Section sources**
- [README.md:74-108](file://README.md#L74-L108)

## Common Setup Issues and Troubleshooting
- Environment file missing:
  - Ensure `.env` exists. The setup script copies `.env.example` automatically.
- Database connection errors:
  - Verify database credentials in `.env`. Default is SQLite; switch to MySQL/MariaDB/PostgreSQL/SQL Server as needed.
- Migrations failing:
  - Run `php artisan migrate:fresh --seed` for a clean slate.
  - Confirm the roles table and users foreign key migration executed.
- Role slugs not recognized:
  - Ensure role slugs are defined only in the RBAC configuration file. Use the CI guard script to validate:
    - `composer ci:check-role-slugs`
- Admin access denied:
  - Confirm your user has an admin role slug. Middleware checks configured admin slugs.
- Assets not loading:
  - Rebuild assets after environment changes: `npm run build`.

**Section sources**
- [composer.json:66-72](file://composer.json#L66-L72)
- [config/database.php:20](file://config/database.php#L20)
- [README.md:68-95](file://README.md#L68-L95)
- [config/rbac.php:4-63](file://config/rbac.php#L4-L63)

## Conclusion
You now have the essentials to install, configure, and operate the assessment platform. Use the role management system to define access levels, leverage admin panels for administration, and follow the troubleshooting tips for common issues. For deeper customization, adjust environment variables, database settings, and RBAC configurations as needed.