# Dashboard & Routing

<cite>
**Referenced Files in This Document**
- [rbac.php](file://config/rbac.php)
- [web.php](file://routes/web.php)
- [RedirectByRole.php](file://app/Http/Middleware/RedirectByRole.php)
- [EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
- [EnsureUserIsAdmin.php](file://app/Http/Middleware/EnsureUserIsAdmin.php)
- [EnsureUserIsEvaluator.php](file://app/Http/Middleware/EnsureUserIsEvaluator.php)
- [AdminDashboard.php](file://app/Livewire/Admin/AdminDashboard.php)
- [TeacherDashboard.php](file://app/Livewire/Fill/TeacherDashboard.php)
- [StaffDashboard.php](file://app/Livewire/Fill/StaffDashboard.php)
- [ParentDashboard.php](file://app/Livewire/Fill/ParentDashboard.php)
- [HasEvaluatorDashboardMetrics.php](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php)
- [admin.blade.php](file://resources/views/layouts/admin.blade.php)
- [evaluator.blade.php](file://resources/views/layouts/evaluator.blade.php)
- [User.php](file://app/Models/User.php)
</cite>

## Table of Contents
1. [Introduction](#introduction)
2. [Project Structure](#project-structure)
3. [Core Components](#core-components)
4. [Architecture Overview](#architecture-overview)
5. [Detailed Component Analysis](#detailed-component-analysis)
6. [Dependency Analysis](#dependency-analysis)
7. [Performance Considerations](#performance-considerations)
8. [Troubleshooting Guide](#troubleshooting-guide)
9. [Conclusion](#conclusion)
10. [Appendices](#appendices)

## Introduction
This document explains the dashboard routing and role-based navigation system. It covers how roles are mapped to dashboards, how automatic redirection works, and how different roles access their dashboards. It also documents configuration keys such as dashboard_role_slugs, role_aliases, and dashboard_paths, along with route prefixes and naming conventions. Practical examples illustrate access patterns, navigation flows, and role-based content filtering.

## Project Structure
The dashboard and routing system spans configuration, routes, middleware, Livewire dashboards, and Blade layouts:
- Configuration defines role slugs, aliases, dashboard mappings, and route prefixes.
- Routes define admin and evaluator dashboards under prefixed namespaces.
- Middleware enforces access and redirects based on roles.
- Livewire dashboards render role-specific content and metrics.
- Blade layouts provide role-specific navigation and UI.

```mermaid
graph TB
subgraph "Configuration"
CFG["config/rbac.php"]
end
subgraph "Routing"
RW["routes/web.php"]
end
subgraph "Middleware"
RBR["RedirectByRole.php"]
EHR["EnsureUserHasRole.php"]
EIA["EnsureUserIsAdmin.php"]
EIE["EnsureUserIsEvaluator.php"]
end
subgraph "Dashboards"
ADB["AdminDashboard.php"]
TDB["TeacherDashboard.php"]
SDB["StaffDashboard.php"]
PDB["ParentDashboard.php"]
HEDM["HasEvaluatorDashboardMetrics.php"]
end
subgraph "UI Layouts"
AL["layouts/admin.blade.php"]
EL["layouts/evaluator.blade.php"]
end
CFG --> RW
RW --> RBR
RW --> EHR
RW --> EIA
RW --> EIE
RW --> ADB
RW --> TDB
RW --> SDB
RW --> PDB
TDB --> HEDM
SDB --> HEDM
PDB --> HEDM
ADB --> CFG
HEDM --> CFG
AL --> RW
EL --> RW
```

**Diagram sources**
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [web.php:1-161](file://routes/web.php#L1-L161)
- [RedirectByRole.php:1-31](file://app/Http/Middleware/RedirectByRole.php#L1-L31)
- [EnsureUserHasRole.php:1-28](file://app/Http/Middleware/EnsureUserHasRole.php#L1-L28)
- [EnsureUserIsAdmin.php:1-23](file://app/Http/Middleware/EnsureUserIsAdmin.php#L1-L23)
- [EnsureUserIsEvaluator.php:1-23](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L1-L23)
- [AdminDashboard.php:1-137](file://app/Livewire/Admin/AdminDashboard.php#L1-L137)
- [TeacherDashboard.php:1-23](file://app/Livewire/Fill/TeacherDashboard.php#L1-L23)
- [StaffDashboard.php:1-23](file://app/Livewire/Fill/StaffDashboard.php#L1-L23)
- [ParentDashboard.php:1-23](file://app/Livewire/Fill/ParentDashboard.php#L1-L23)
- [HasEvaluatorDashboardMetrics.php:1-73](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L1-L73)
- [admin.blade.php:1-105](file://resources/views/layouts/admin.blade.php#L1-L105)
- [evaluator.blade.php:1-82](file://resources/views/layouts/evaluator.blade.php#L1-L82)

**Section sources**
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [web.php:1-161](file://routes/web.php#L1-L161)

## Core Components
- Role-based dashboard configuration
  - dashboard_role_slugs: maps internal dashboard keys to evaluator role slugs.
  - role_aliases: aliases for administrative roles.
  - dashboard_paths: maps role slugs to absolute or relative dashboard paths.
  - admin_route: route prefix and name prefix for admin routes.
- Route definitions
  - Admin dashboards under a configurable prefix and name.
  - Evaluator dashboards under fill/dashboard/<key>.
  - Role-aware redirect endpoint for /dashboard.
- Middleware
  - RedirectByRole: redirects authenticated users to their dashboard path.
  - EnsureUserHasRole: gatekeeper for allowed role slugs.
  - EnsureUserIsAdmin and EnsureUserIsEvaluator: role-specific access enforcement.
- Livewire dashboards
  - AdminDashboard: overview metrics for administrators.
  - TeacherDashboard, StaffDashboard, ParentDashboard: evaluator dashboards using shared metrics trait.
- Blade layouts
  - Admin layout with navigation and conditional sections.
  - Evaluator layout with dynamic dashboard links and role info.

**Section sources**
- [rbac.php:12-40](file://config/rbac.php#L12-L40)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [web.php:29-33](file://routes/web.php#L29-L33)
- [web.php:57-59](file://routes/web.php#L57-L59)
- [web.php:72-147](file://routes/web.php#L72-L147)
- [web.php:149-160](file://routes/web.php#L149-L160)
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [AdminDashboard.php:25-135](file://app/Livewire/Admin/AdminDashboard.php#L25-L135)
- [TeacherDashboard.php:14-21](file://app/Livewire/Fill/TeacherDashboard.php#L14-L21)
- [StaffDashboard.php:14-21](file://app/Livewire/Fill/StaffDashboard.php#L14-L21)
- [ParentDashboard.php:14-21](file://app/Livewire/Fill/ParentDashboard.php#L14-L21)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [admin.blade.php:31-66](file://resources/views/layouts/admin.blade.php#L31-L66)
- [evaluator.blade.php:20-67](file://resources/views/layouts/evaluator.blade.php#L20-L67)

## Architecture Overview
The system orchestrates role-aware routing and automatic redirection:
- The /dashboard route triggers RedirectByRole middleware, which resolves the user’s role slug and redirects to the configured dashboard path.
- Admin routes are grouped under a configurable prefix and name, while evaluator dashboards are grouped under fill/dashboard/<key>.
- Middleware ensures only authorized roles can access admin or evaluator sections.
- Livewire dashboards compute role-specific metrics and render Blade templates with role-aware layouts.

```mermaid
sequenceDiagram
participant U as "User"
participant RT as "routes/web.php"
participant MW as "RedirectByRole.php"
participant CFG as "config/rbac.php"
participant LD as "Blade Layout"
U->>RT : GET /dashboard
RT->>MW : Invoke middleware chain
MW->>U : Resolve role slug
MW->>CFG : Lookup dashboard_paths[role]
CFG-->>MW : Path string
MW-->>U : 302 Redirect to resolved path
U->>LD : Load layout and dashboard view
```

**Diagram sources**
- [web.php:57-59](file://routes/web.php#L57-L59)
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [evaluator.blade.php:20-24](file://resources/views/layouts/evaluator.blade.php#L20-L24)

## Detailed Component Analysis

### Role-Based Dashboard Configuration
- dashboard_role_slugs
  - Maps internal keys (teacher, staff, parent) to evaluator role slugs (guru, tata_usaha, orang_tua).
  - Used by evaluator dashboards to fetch metrics for the mapped role.
- role_aliases
  - Aliases administrative roles (e.g., super_admin to admin) for simplified access checks.
- dashboard_paths
  - Maps role slugs to dashboard paths. Includes fallback for unknown roles.
  - Admin roles map to admin dashboard; evaluators map to fill/dashboard/<key>; user maps to fill/questionnaires.
- admin_route
  - Defines prefix and name prefix for admin routes, enabling customization of URLs and named routes.

Practical implications:
- Adding a new evaluator role requires adding a mapping in dashboard_role_slugs and a corresponding route in routes/web.php.
- To customize admin URLs, adjust admin_route.prefix and admin_route.name.

**Section sources**
- [rbac.php:12-16](file://config/rbac.php#L12-L16)
- [rbac.php:25-27](file://config/rbac.php#L25-L27)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [rbac.php:37-40](file://config/rbac.php#L37-L40)

### Automatic Redirection Logic
- The /dashboard route is defined with RedirectByRole middleware.
- On access, the middleware:
  - Resolves the authenticated user’s role slug.
  - Looks up the path in dashboard_paths.
  - Redirects to the resolved path; defaults to fill/questionnaires if not found.

```mermaid
flowchart TD
Start(["Access /dashboard"]) --> CheckAuth["Is user authenticated?"]
CheckAuth --> |No| Next["Proceed (handled by guest middleware)"]
CheckAuth --> |Yes| GetRole["Get user role slug"]
GetRole --> Lookup["Lookup dashboard_paths[role]"]
Lookup --> Found{"Path found?"}
Found --> |Yes| Redirect["302 Redirect to path"]
Found --> |No| Fallback["Redirect to /fill/questionnaires"]
Redirect --> End(["Render layout and dashboard"])
Fallback --> End
```

**Diagram sources**
- [web.php:57-59](file://routes/web.php#L57-L59)
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

**Section sources**
- [web.php:57-59](file://routes/web.php#L57-L59)
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)

### Admin Dashboards
- Route group
  - Prefix and name derived from admin_route configuration.
  - Includes dashboard, analytics, exports, departments, users, and roles.
- Layout
  - Admin layout provides navigation and conditional sections for admin-only features.
- Metrics
  - AdminDashboard computes participation rates, counts, and breakdowns by role, using admin_slugs and questionnaire_target_aliases.

Customization tips:
- Adjust admin_route.prefix to change the admin URL base (e.g., admin-panel).
- Add new admin endpoints under the admin prefix and name.

**Section sources**
- [web.php:72-147](file://routes/web.php#L72-L147)
- [rbac.php:37-40](file://config/rbac.php#L37-L40)
- [admin.blade.php:31-66](file://resources/views/layouts/admin.blade.php#L31-L66)
- [AdminDashboard.php:25-135](file://app/Livewire/Admin/AdminDashboard.php#L25-L135)

### Evaluator Dashboards
- Route group
  - Under fill/dashboard/<key>, with routes for teacher, staff, and parent dashboards.
- Layout
  - Evaluator layout displays role and dynamic links to dashboards and questionnaires.
- Metrics
  - TeacherDashboard, StaffDashboard, ParentDashboard use a shared trait to compute:
    - Available questionnaires filtered by target groups and role aliases.
    - Completed responses for the current user.
    - Statistics such as active questionnaires and counts.

Role aliases and target groups:
- Target aliases combine primary and alias slugs to broaden the set of questionnaires considered for a given role.
- The metrics query filters questionnaires by target_group values derived from the user’s role slug and its alias.

**Section sources**
- [web.php:149-160](file://routes/web.php#L149-L160)
- [TeacherDashboard.php:14-21](file://app/Livewire/Fill/TeacherDashboard.php#L14-L21)
- [StaffDashboard.php:14-21](file://app/Livewire/Fill/StaffDashboard.php#L14-L21)
- [ParentDashboard.php:14-21](file://app/Livewire/Fill/ParentDashboard.php#L14-L21)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [rbac.php:7-11](file://config/rbac.php#L7-L11)
- [rbac.php:12-16](file://config/rbac.php#L12-L16)

### Middleware and Access Control
- RedirectByRole
  - Redirects authenticated users to their dashboard path based on role slug.
- EnsureUserHasRole
  - Enforces that the user has any of the allowed role slugs; otherwise aborts with 403.
- EnsureUserIsAdmin and EnsureUserIsEvaluator
  - Guards admin and evaluator sections respectively, throwing access denied exceptions for unauthorized users.

Integration:
- Admin routes use EnsureUserHasRole with admin_slugs.
- Evaluator routes use EnsureUserHasRole with evaluator_slugs.
- The role redirect route uses EnsureUserHasRole with the configured evaluator slugs to ensure only evaluators are redirected.

**Section sources**
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [rbac.php:4-6](file://config/rbac.php#L4-L6)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

### Role Resolution and Content Filtering
- User model
  - roleSlug resolves the user’s role slug from roleRef or legacy role field.
  - hasAnyRoleSlug checks membership against allowed slugs.
  - isAdminRole and isEvaluatorRole leverage configuration arrays for role classification.
- Evaluator metrics
  - The shared trait builds targetGroups from the user’s role slug and its alias, then queries questionnaires and responses accordingly.

Practical examples:
- A user with role slug guru is redirected to /fill/dashboard/guru and sees questionnaires targeted to guru or its alias group.
- A user with role slug orang_tua is redirected to /fill/dashboard/parent and sees questionnaires targeted to orang_tua or its alias group.

**Section sources**
- [User.php:59-87](file://app/Models/User.php#L59-L87)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [rbac.php:7-11](file://config/rbac.php#L7-L11)

## Dependency Analysis
The following diagram shows how routes depend on middleware, configuration, and dashboards.

```mermaid
graph LR
RW["routes/web.php"] --> RBR["RedirectByRole.php"]
RW --> EHR["EnsureUserHasRole.php"]
RW --> EIA["EnsureUserIsAdmin.php"]
RW --> EIE["EnsureUserIsEvaluator.php"]
RW --> ADB["AdminDashboard.php"]
RW --> TDB["TeacherDashboard.php"]
RW --> SDB["StaffDashboard.php"]
RW --> PDB["ParentDashboard.php"]
CFG["config/rbac.php"] --> RW
CFG --> RBR
CFG --> TDB
CFG --> SDB
CFG --> PDB
ADB --> CFG
TDB --> HEDM["HasEvaluatorDashboardMetrics.php"]
SDB --> HEDM
PDB --> HEDM
AL["layouts/admin.blade.php"] --> RW
EL["layouts/evaluator.blade.php"] --> RW
```

**Diagram sources**
- [web.php:1-161](file://routes/web.php#L1-L161)
- [RedirectByRole.php:1-31](file://app/Http/Middleware/RedirectByRole.php#L1-L31)
- [EnsureUserHasRole.php:1-28](file://app/Http/Middleware/EnsureUserHasRole.php#L1-L28)
- [EnsureUserIsAdmin.php:1-23](file://app/Http/Middleware/EnsureUserIsAdmin.php#L1-L23)
- [EnsureUserIsEvaluator.php:1-23](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L1-L23)
- [AdminDashboard.php:1-137](file://app/Livewire/Admin/AdminDashboard.php#L1-L137)
- [TeacherDashboard.php:1-23](file://app/Livewire/Fill/TeacherDashboard.php#L1-L23)
- [StaffDashboard.php:1-23](file://app/Livewire/Fill/StaffDashboard.php#L1-L23)
- [ParentDashboard.php:1-23](file://app/Livewire/Fill/ParentDashboard.php#L1-L23)
- [HasEvaluatorDashboardMetrics.php:1-73](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L1-L73)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [admin.blade.php:1-105](file://resources/views/layouts/admin.blade.php#L1-L105)
- [evaluator.blade.php:1-82](file://resources/views/layouts/evaluator.blade.php#L1-L82)

**Section sources**
- [web.php:1-161](file://routes/web.php#L1-L161)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)

## Performance Considerations
- Caching
  - AdminDashboard caches overview metrics for a fixed duration to reduce database load.
- Query efficiency
  - Evaluator dashboards filter questionnaires and responses using targeted where clauses and joins.
- Middleware overhead
  - RedirectByRole performs a single config lookup per request; keep dashboard_paths minimal and avoid heavy computation in middleware.

Recommendations:
- Monitor cache TTL for AdminDashboard metrics.
- Index database columns used in evaluator queries (e.g., users.role, responses.status, questionnaire targets).
- Keep role slug lists concise to minimize middleware checks.

**Section sources**
- [AdminDashboard.php:27-130](file://app/Livewire/Admin/AdminDashboard.php#L27-L130)
- [HasEvaluatorDashboardMetrics.php:36-55](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L36-L55)

## Troubleshooting Guide
Common issues and resolutions:
- Unexpected redirect after login
  - Verify dashboard_paths contains entries for all active role slugs.
  - Confirm RedirectByRole middleware is applied to /dashboard.
- Access denied on admin or evaluator pages
  - Ensure user role slug matches configured admin_slugs or evaluator_slugs.
  - Check EnsureUserHasRole usage in route groups.
- Evaluator dashboards show empty content
  - Confirm questionnaire targets include the user’s role slug or its alias.
  - Verify target aliases mapping exists in questionnaire_target_aliases.
- Admin URLs not matching expectations
  - Adjust admin_route.prefix and admin_route.name to match desired URL structure.

**Section sources**
- [rbac.php:4-6](file://config/rbac.php#L4-L6)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [web.php:72-147](file://routes/web.php#L72-L147)
- [web.php:149-160](file://routes/web.php#L149-L160)
- [HasEvaluatorDashboardMetrics.php:27-34](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L27-L34)

## Conclusion
The dashboard and routing system leverages a centralized configuration to map roles to dashboards, enforce access via middleware, and provide role-aware navigation. Administrators and evaluators are directed to appropriate dashboards automatically, while evaluator dashboards filter content based on role slugs and aliases. The design supports customization of route prefixes and naming conventions, and includes caching and efficient queries for performance.

## Appendices

### Role-to-Dashboard Mapping Reference
- Internal key to role slug
  - teacher → guru
  - staff → tata_usaha
  - parent → orang_tua
- Role slug to path
  - super_admin, admin → /admin/dashboard
  - guru → /fill/dashboard/guru
  - tata_usaha → /fill/dashboard/staff
  - orang_tua → /fill/dashboard/parent
  - user → /fill/questionnaires

**Section sources**
- [rbac.php:12-16](file://config/rbac.php#L12-L16)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

### Route Prefixes and Naming Conventions
- Admin route prefix and name
  - Prefix: admin_route.prefix
  - Name: admin_route.name
- Evaluator route prefix
  - Base: fill
  - Dashboard subpaths: teacher, staff, parent
- Named routes
  - Admin: admin.dashboard, admin.questionnaires.*, admin.exports.*, admin.users.*, admin.roles.*
  - Evaluator: fill.dashboard.teacher, fill.dashboard.staff, fill.dashboard.parent, fill.questionnaires.*

**Section sources**
- [rbac.php:37-40](file://config/rbac.php#L37-L40)
- [web.php:72-147](file://routes/web.php#L72-L147)
- [web.php:149-160](file://routes/web.php#L149-L160)