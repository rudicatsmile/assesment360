# Middleware Pipeline

<cite>
**Referenced Files in This Document**
- [EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
- [EnsureUserIsAdmin.php](file://app/Http/Middleware/EnsureUserIsAdmin.php)
- [EnsureUserIsEvaluator.php](file://app/Http/Middleware/EnsureUserIsEvaluator.php)
- [RedirectByRole.php](file://app/Http/Middleware/RedirectByRole.php)
- [rbac.php](file://config/rbac.php)
- [web.php](file://routes/web.php)
- [api.php](file://routes/api.php)
- [app.php](file://bootstrap/app.php)
- [User.php](file://app/Models/User.php)
- [Role.php](file://app/Models/Role.php)
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

## Introduction
This document explains the middleware pipeline and request processing flow for role-based access control (RBAC) in the application. It covers the middleware chain that enforces authentication and authorization, the order of execution, and how requests are filtered and redirected based on user roles. It also documents the middleware registration process, custom middleware creation patterns, and how middleware integrates with the routing system to enforce security policies.

## Project Structure
The middleware pipeline is configured during application bootstrapping and applied to routes via named aliases. The RBAC configuration centralizes role slugs, middleware aliases, and dashboard redirection paths. Routes define which middleware groups apply to different areas of the application.

```mermaid
graph TB
subgraph "Bootstrap"
APP["bootstrap/app.php"]
RBAC["config/rbac.php"]
end
subgraph "HTTP Kernel"
MW_ALIAS["Middleware Aliases<br/>admin_gate → access.admin<br/>evaluator_gate → access.evaluator<br/>role_gate → access.role<br/>role_redirect → access.role.redirect"]
end
subgraph "Routes"
WEB["routes/web.php"]
API["routes/api.php"]
end
subgraph "Middleware"
ADMIN_MW["EnsureUserIsAdmin"]
EVAL_MW["EnsureUserIsEvaluator"]
ROLE_MW["EnsureUserHasRole"]
REDIRECT_MW["RedirectByRole"]
end
subgraph "Domain Models"
USER["app/Models/User.php"]
ROLE["app/Models/Role.php"]
end
APP --> MW_ALIAS
RBAC --> MW_ALIAS
WEB --> ADMIN_MW
WEB --> EVAL_MW
WEB --> ROLE_MW
WEB --> REDIRECT_MW
API --> ADMIN_MW
API --> ROLE_MW
ADMIN_MW --> USER
EVAL_MW --> USER
ROLE_MW --> USER
REDIRECT_MW --> USER
USER --> ROLE
```

**Diagram sources**
- [app.php:17-33](file://bootstrap/app.php#L17-L33)
- [rbac.php:31-36](file://config/rbac.php#L31-L36)
- [web.php:29-33](file://routes/web.php#L29-L33)
- [api.php:6](file://routes/api.php#L6)
- [EnsureUserIsAdmin.php:12](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12)
- [EnsureUserIsEvaluator.php:12](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12)
- [EnsureUserHasRole.php:11](file://app/Http/Middleware/EnsureUserHasRole.php#L11)
- [RedirectByRole.php:11](file://app/Http/Middleware/RedirectByRole.php#L11)
- [User.php:64](file://app/Models/User.php#L64)
- [Role.php:26](file://app/Models/Role.php#L26)

**Section sources**
- [app.php:17-33](file://bootstrap/app.php#L17-L33)
- [rbac.php:31-36](file://config/rbac.php#L31-L36)
- [web.php:29-33](file://routes/web.php#L29-L33)
- [api.php:6](file://routes/api.php#L6)

## Core Components
This section describes the four middleware components that form the RBAC pipeline and their responsibilities.

- EnsureUserIsAdmin: Enforces admin-only access by verifying the user’s role slug against configured admin slugs.
- EnsureUserIsEvaluator: Enforces evaluator-only access using configured evaluator slugs with a fallback logic for custom role catalogs.
- EnsureUserHasRole: Accepts one or more role slugs as parameters and ensures the user possesses any of them.
- RedirectByRole: Redirects authenticated users to role-specific dashboards based on configuration.

Key implementation characteristics:
- All middleware receive the current Request and a Closure representing the next handler in the pipeline.
- They either abort/throw with appropriate HTTP status codes or call the next middleware/closure to continue processing.
- Role checks delegate to the User model, which reads role slugs from the associated Role record or falls back to a string field.

**Section sources**
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [RedirectByRole.php:11-24](file://app/Http/Middleware/RedirectByRole.php#L11-L24)
- [User.php:64](file://app/Models/User.php#L64)

## Architecture Overview
The middleware pipeline is registered once during application bootstrap and referenced by aliases in route definitions. The execution order depends on the order of middleware in each route group. The typical flow is:
- Authentication middleware validates credentials and populates the request user.
- Role-aware middleware evaluate permissions and either allow or deny access.
- Redirect middleware inspects the current route and redirects authenticated users to role-appropriate dashboards.

```mermaid
sequenceDiagram
participant Client as "Client"
participant Router as "Router"
participant AuthMW as "auth middleware"
participant RoleRedirectMW as "access.role.redirect"
participant RoleGateMW as "access.role"
participant AdminGateMW as "access.admin"
participant EvalGateMW as "access.evaluator"
participant Controller as "Controller/Livewire"
Client->>Router : "HTTP Request"
Router->>AuthMW : "Authenticate user"
AuthMW-->>Router : "Request with user"
Router->>RoleRedirectMW : "Check if user is authenticated"
alt "Authenticated"
RoleRedirectMW->>RoleRedirectMW : "Check route name 'role.dashboard'"
RoleRedirectMW-->>Router : "Redirect to role dashboard"
else "Guest"
RoleRedirectMW-->>Router : "Proceed"
end
Router->>RoleGateMW : "Evaluate allowed role slugs"
RoleGateMW-->>Router : "Allow or deny"
Router->>AdminGateMW : "Admin-only route?"
AdminGateMW-->>Router : "Allow or deny"
Router->>EvalGateMW : "Evaluator-only route?"
EvalGateMW-->>Router : "Allow or deny"
Router->>Controller : "Invoke handler"
Controller-->>Client : "Response"
```

**Diagram sources**
- [web.php:57](file://routes/web.php#L57)
- [web.php:72](file://routes/web.php#L72)
- [web.php:149](file://routes/web.php#L149)
- [api.php:8](file://routes/api.php#L8)
- [app.php:23-28](file://bootstrap/app.php#L23-L28)
- [RedirectByRole.php:11-24](file://app/Http/Middleware/RedirectByRole.php#L11-L24)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)

## Detailed Component Analysis

### Middleware Registration and Aliases
- Middleware aliases are defined in the bootstrap configuration and map to concrete middleware classes.
- The alias names are configurable via RBAC configuration and referenced in route definitions.

```mermaid
classDiagram
class KernelAliases {
+alias "access.admin" : EnsureUserIsAdmin
+alias "access.evaluator" : EnsureUserIsEvaluator
+alias "access.role" : EnsureUserHasRole
+alias "access.role.redirect" : RedirectByRole
}
class EnsureUserIsAdmin {
+handle(request, next) Response
}
class EnsureUserIsEvaluator {
+handle(request, next) Response
}
class EnsureUserHasRole {
+handle(request, next, ...slugs) Response
}
class RedirectByRole {
+handle(request, next) Response
-dashboardPath(role) string
}
KernelAliases --> EnsureUserIsAdmin : "maps to"
KernelAliases --> EnsureUserIsEvaluator : "maps to"
KernelAliases --> EnsureUserHasRole : "maps to"
KernelAliases --> RedirectByRole : "maps to"
```

**Diagram sources**
- [app.php:23-28](file://bootstrap/app.php#L23-L28)
- [rbac.php:31-36](file://config/rbac.php#L31-L36)
- [EnsureUserIsAdmin.php:12](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12)
- [EnsureUserIsEvaluator.php:12](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12)
- [EnsureUserHasRole.php:11](file://app/Http/Middleware/EnsureUserHasRole.php#L11)
- [RedirectByRole.php:11](file://app/Http/Middleware/RedirectByRole.php#L11)

**Section sources**
- [app.php:17-33](file://bootstrap/app.php#L17-L33)
- [rbac.php:31-36](file://config/rbac.php#L31-L36)

### EnsureUserHasRole Middleware
- Purpose: Gate routes to users possessing any of the specified role slugs.
- Execution: If no slugs are provided, allows immediately. Otherwise, denies access if the user lacks any of the specified slugs.
- Parameterization: Accepts variable-length slug arguments; useful for flexible role gates.

```mermaid
flowchart TD
Start(["handle(request, next, ...slugs)"]) --> GetUser["Get authenticated user"]
GetUser --> HasUser{"User exists?"}
HasUser --> |No| Abort401["Abort 401 Unauthorized"]
HasUser --> |Yes| CheckArgs{"Any slugs provided?"}
CheckArgs --> |No| Next["Call next(request)"]
CheckArgs --> |Yes| CheckRole["User has any role slug?"]
CheckRole --> |No| Abort403["Abort 403 Forbidden"]
CheckRole --> |Yes| Next
Abort401 --> End(["Exit"])
Abort403 --> End
Next --> End
```

**Diagram sources**
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)

**Section sources**
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)

### EnsureUserIsAdmin Middleware
- Purpose: Enforce admin-only access by validating the user’s role slug against configured admin slugs.
- Execution: Throws an access denied exception if the user is not authenticated or does not have an admin role.

```mermaid
flowchart TD
Start(["handle(request, next)"]) --> GetUser["Get authenticated user"]
GetUser --> IsAdmin{"User exists and is admin?"}
IsAdmin --> |Yes| Next["Call next(request)"]
IsAdmin --> |No| Throw403["Throw Access Denied Exception"]
Next --> End(["Exit"])
Throw403 --> End
```

**Diagram sources**
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)

**Section sources**
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [User.php:69](file://app/Models/User.php#L69)

### EnsureUserIsEvaluator Middleware
- Purpose: Enforce evaluator-only access using configured evaluator slugs.
- Fallback logic: If evaluator slugs are not configured but the user has a role ID, treat non-admin roles as evaluators.
- Execution: Throws an access denied exception if the user is not authenticated or does not qualify as an evaluator.

```mermaid
flowchart TD
Start(["handle(request, next)"]) --> GetUser["Get authenticated user"]
GetUser --> IsEvaluator{"User exists and is evaluator?"}
IsEvaluator --> |Yes| Next["Call next(request)"]
IsEvaluator --> |No| Throw403["Throw Access Denied Exception"]
Next --> End(["Exit"])
Throw403 --> End
```

**Diagram sources**
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [User.php:74](file://app/Models/User.php#L74)

**Section sources**
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [User.php:74](file://app/Models/User.php#L74)

### RedirectByRole Middleware
- Purpose: Redirect authenticated users visiting the role dashboard route to a role-specific dashboard path.
- Behavior: If the current route is the role dashboard and the user is authenticated, redirect according to the configured mapping; otherwise, pass through.

```mermaid
flowchart TD
Start(["handle(request, next)"]) --> GetUser["Get authenticated user"]
GetUser --> IsGuest{"User authenticated?"}
IsGuest --> |No| Next["Call next(request)"]
IsGuest --> |Yes| IsRoleDash{"Route is 'role.dashboard'?"}
IsRoleDash --> |No| Next
IsRoleDash --> |Yes| GetPath["Resolve dashboard path by role slug"]
GetPath --> Redirect["Redirect to mapped path"]
Next --> End(["Exit"])
Redirect --> End
```

**Diagram sources**
- [RedirectByRole.php:11-24](file://app/Http/Middleware/RedirectByRole.php#L11-L24)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

**Section sources**
- [RedirectByRole.php:11-24](file://app/Http/Middleware/RedirectByRole.php#L11-L24)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

### Middleware Order and Route Integration
- Web routes apply middleware in the order declared in the route definition. Typical order:
  1) auth
  2) access.role.redirect
  3) access.role (and/or access.admin/access.evaluator)
- Admin routes (prefixed with configured admin prefix) use the admin gate alias.
- Evaluator routes (under fill/) use the evaluator gate alias.
- API routes under the admin gate alias restrict administrative endpoints.

```mermaid
sequenceDiagram
participant Client as "Client"
participant Router as "Router"
participant AuthMW as "auth"
participant RedirectMW as "access.role.redirect"
participant RoleGateMW as "access.role"
participant AdminGateMW as "access.admin"
participant EvalGateMW as "access.evaluator"
participant Handler as "Handler"
Client->>Router : "GET /admin/... or /fill/..."
Router->>AuthMW : "Run"
AuthMW-->>Router : "Request with user"
Router->>RedirectMW : "Run"
RedirectMW-->>Router : "Maybe redirect"
Router->>RoleGateMW : "Run (optional)"
RoleGateMW-->>Router : "Allow or deny"
Router->>AdminGateMW : "Run (admin routes)"
AdminGateMW-->>Router : "Allow or deny"
Router->>EvalGateMW : "Run (evaluator routes)"
EvalGateMW-->>Router : "Allow or deny"
Router->>Handler : "Invoke controller/livewire"
Handler-->>Client : "Response"
```

**Diagram sources**
- [web.php:57](file://routes/web.php#L57)
- [web.php:72](file://routes/web.php#L72)
- [web.php:149](file://routes/web.php#L149)
- [api.php:8](file://routes/api.php#L8)
- [app.php:23-28](file://bootstrap/app.php#L23-L28)

**Section sources**
- [web.php:57](file://routes/web.php#L57)
- [web.php:72](file://routes/web.php#L72)
- [web.php:149](file://routes/web.php#L149)
- [api.php:8](file://routes/api.php#L8)

## Dependency Analysis
- Middleware depend on the authenticated user being present on the request.
- Role evaluation depends on the User model’s methods, which in turn depend on the Role model relationship and configuration.
- RedirectByRole depends on RBAC configuration for dashboard paths.

```mermaid
graph TB
WEB_ROUTES["routes/web.php"]
API_ROUTES["routes/api.php"]
BOOTSTRAP_APP["bootstrap/app.php"]
RBAC_CONFIG["config/rbac.php"]
ADMIN_MW["EnsureUserIsAdmin"]
EVAL_MW["EnsureUserIsEvaluator"]
ROLE_MW["EnsureUserHasRole"]
REDIRECT_MW["RedirectByRole"]
USER_MODEL["app/Models/User.php"]
ROLE_MODEL["app/Models/Role.php"]
WEB_ROUTES --> ADMIN_MW
WEB_ROUTES --> EVAL_MW
WEB_ROUTES --> ROLE_MW
WEB_ROUTES --> REDIRECT_MW
API_ROUTES --> ADMIN_MW
API_ROUTES --> ROLE_MW
BOOTSTRAP_APP --> ADMIN_MW
BOOTSTRAP_APP --> EVAL_MW
BOOTSTRAP_APP --> ROLE_MW
BOOTSTRAP_APP --> REDIRECT_MW
RBAC_CONFIG --> REDIRECT_MW
RBAC_CONFIG --> ADMIN_MW
RBAC_CONFIG --> EVAL_MW
ADMIN_MW --> USER_MODEL
EVAL_MW --> USER_MODEL
ROLE_MW --> USER_MODEL
REDIRECT_MW --> USER_MODEL
USER_MODEL --> ROLE_MODEL
```

**Diagram sources**
- [web.php:29-33](file://routes/web.php#L29-L33)
- [api.php:6](file://routes/api.php#L6)
- [app.php:23-28](file://bootstrap/app.php#L23-L28)
- [rbac.php:31-36](file://config/rbac.php#L31-L36)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [EnsureUserIsAdmin.php:12](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12)
- [EnsureUserIsEvaluator.php:12](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12)
- [EnsureUserHasRole.php:11](file://app/Http/Middleware/EnsureUserHasRole.php#L11)
- [RedirectByRole.php:11](file://app/Http/Middleware/RedirectByRole.php#L11)
- [User.php:64](file://app/Models/User.php#L64)
- [Role.php:26](file://app/Models/Role.php#L26)

**Section sources**
- [web.php:29-33](file://routes/web.php#L29-L33)
- [api.php:6](file://routes/api.php#L6)
- [app.php:23-28](file://bootstrap/app.php#L23-L28)
- [rbac.php:31-36](file://config/rbac.php#L31-L36)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [User.php:64](file://app/Models/User.php#L64)
- [Role.php:26](file://app/Models/Role.php#L26)

## Performance Considerations
- Role checks are O(n) in the number of configured slugs per middleware invocation; keep slug lists concise.
- RedirectByRole performs a single lookup from configuration; negligible overhead.
- Minimize repeated role evaluations by consolidating middleware usage and avoiding redundant gates.
- Use caching strategies at the application level if role checks become frequent in hot paths.

## Troubleshooting Guide
Common issues and resolutions:
- Unexpected 403 Forbidden:
  - Verify the user’s role slug matches one of the allowed slugs for the route.
  - Confirm the middleware alias is correctly referenced in the route definition.
- Unexpected 401 Unauthorized:
  - Ensure the auth middleware runs before role gates.
- Incorrect redirect after login:
  - Confirm the role dashboard route name matches the expected route name.
  - Verify the role slug resolves to a configured dashboard path.
- Admin/Evaluator routes accessible unexpectedly:
  - Check that the correct middleware alias is applied to the route group.
  - Review RBAC configuration for admin and evaluator slugs.

**Section sources**
- [web.php:57](file://routes/web.php#L57)
- [web.php:72](file://routes/web.php#L72)
- [web.php:149](file://routes/web.php#L149)
- [api.php:8](file://routes/api.php#L8)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

## Conclusion
The middleware pipeline enforces a layered RBAC policy: authentication precedes role checks, which in turn enforce access to admin, evaluator, or role-gated routes. RedirectByRole ensures authenticated users land on the correct dashboard. The design leverages configurable aliases and centralized RBAC settings, enabling maintainable and flexible security enforcement across web and API routes.