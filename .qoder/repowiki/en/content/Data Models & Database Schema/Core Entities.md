# Core Entities

<cite>
**Referenced Files in This Document**
- [User.php](file://app/Models/User.php)
- [Role.php](file://app/Models/Role.php)
- [Departement.php](file://app/Models/Departement.php)
- [create_users_table.php](file://database/migrations/0001_01_01_000000_create_users_table.php)
- [add_is_active_to_users_table.php](file://database/migrations/2026_04_16_135841_add_is_active_to_users_table.php)
- [add_department_to_users_table.php](file://database/migrations/2026_04_16_235040_add_department_to_users_table.php)
- [alter_role_column_type_on_users_table.php](file://database/migrations/2026_04_17_140500_alter_role_column_type_on_users_table.php)
- [create_roles_table.php](file://database/migrations/2026_04_17_093035_create_roles_table.php)
- [add_role_id_to_users_table.php](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php)
- [create_departements_table.php](file://database/migrations/2026_04_17_000821_create_departements_table.php)
- [add_department_id_to_users_table.php](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php)
- [rbac.php](file://config/rbac.php)
- [EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
- [EnsureUserIsAdmin.php](file://app/Http/Middleware/EnsureUserIsAdmin.php)
- [EnsureUserIsEvaluator.php](file://app/Http/Middleware/EnsureUserIsEvaluator.php)
- [RedirectByRole.php](file://app/Http/Middleware/RedirectByRole.php)
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
This document describes the core data models for User, Role, and Department, including their relationships, constraints, indexes, and business logic. It explains how roles and departments are linked to users, how access control is enforced via middleware and configuration, and how the RBAC implementation maps role slugs to dashboards and permissions.

## Project Structure
The core models and their supporting migration files define the relational schema. Middleware enforces access control based on role slugs configured centrally.

```mermaid
graph TB
subgraph "Models"
U["User<br/>app/Models/User.php"]
R["Role<br/>app/Models/Role.php"]
D["Department<br/>app/Models/Departement.php"]
end
subgraph "Migrations"
MU["users table<br/>database/migrations/..._create_users_table.php"]
MR["roles table<br/>database/migrations/..._create_roles_table.php"]
MD["departements table<br/>database/migrations/..._create_departements_table.php"]
MUR["add role_id to users<br/>database/migrations/..._add_role_id_to_users_table.php"]
MUD["add department_id to users<br/>database/migrations/..._add_department_id_to_users_table.php"]
end
subgraph "RBAC Config"
CFG["RBAC config<br/>config/rbac.php"]
end
subgraph "Middleware"
MID1["EnsureUserHasRole<br/>app/Http/Middleware/EnsureUserHasRole.php"]
MID2["EnsureUserIsAdmin<br/>app/Http/Middleware/EnsureUserIsAdmin.php"]
MID3["EnsureUserIsEvaluator<br/>app/Http/Middleware/EnsureUserIsEvaluator.php"]
MID4["RedirectByRole<br/>app/Http/Middleware/RedirectByRole.php"]
end
U --- MU
R --- MR
D --- MD
U --- MUR
U --- MUD
U --- CFG
MID1 --- CFG
MID2 --- CFG
MID3 --- CFG
MID4 --- CFG
```

**Diagram sources**
- [User.php:12-94](file://app/Models/User.php#L12-L94)
- [Role.php:9-31](file://app/Models/Role.php#L9-L31)
- [Departement.php:9-34](file://app/Models/Departement.php#L9-L34)
- [create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [EnsureUserHasRole.php:9-28](file://app/Http/Middleware/EnsureUserHasRole.php#L9-L28)
- [EnsureUserIsAdmin.php:10-23](file://app/Http/Middleware/EnsureUserIsAdmin.php#L10-L23)
- [EnsureUserIsEvaluator.php:10-23](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L10-L23)
- [RedirectByRole.php:9-31](file://app/Http/Middleware/RedirectByRole.php#L9-L31)

**Section sources**
- [User.php:12-94](file://app/Models/User.php#L12-L94)
- [Role.php:9-31](file://app/Models/Role.php#L9-L31)
- [Departement.php:9-34](file://app/Models/Departement.php#L9-L34)
- [create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)

## Core Components
This section documents the three core entities and their relationships.

- User
  - Purpose: Represents an application user with identity, credentials, role, department, and activity status.
  - Key relations: belongs to Role via role_id; belongs to Department via department_id; has many Responses and created Questionnaires.
  - Role resolution: Provides helpers to resolve role slug from either role_id or legacy role column, and to detect admin vs evaluator roles.
  - Access control helpers: canManageRoles delegates to admin role check.

- Role
  - Purpose: Defines roles with unique name and slug, optional description, percentage weight, and activation flag.
  - Key relation: has many Users via role_id.

- Department
  - Purpose: Organizes users and evaluation data by department with ordering and description.
  - Key relations: has many Users, Answers, and AnswerOptions via department_id.

**Section sources**
- [User.php:12-94](file://app/Models/User.php#L12-L94)
- [Role.php:9-31](file://app/Models/Role.php#L9-L31)
- [Departement.php:9-34](file://app/Models/Departement.php#L9-L34)

## Architecture Overview
The data model centers around three entities with foreign keys linking users to roles and departments. Access control is enforced by middleware using role slugs configured centrally.

```mermaid
erDiagram
ROLES {
bigint id PK
string name UK
string slug UK
text description
decimal prosentase
boolean is_active
timestamps timestamps
}
DEPARTEMENTS {
bigint id PK
string name UK
uint urut
text description
timestamps timestamps
}
USERS {
bigint id PK
string name
string email UK
string role
timestamp email_verified_at
string password
string remember_token
boolean is_active
bigint role_id FK
bigint department_id FK
timestamps timestamps
softdeletes deleted_at
}
USERS }o--|| ROLES : "belongs to"
USERS }o--|| DEPARTEMENTS : "belongs to"
```

**Diagram sources**
- [create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [add_is_active_to_users_table.php:14-16](file://database/migrations/2026_04_16_135841_add_is_active_to_users_table.php#L14-L16)
- [alter_role_column_type_on_users_table.php:11-19](file://database/migrations/2026_04_17_140500_alter_role_column_type_on_users_table.php#L11-L19)
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)

## Detailed Component Analysis

### User Model
- Fields and types
  - Identity: id, name, email (unique), role (string), email_verified_at, password, remember_token.
  - Flags: is_active (boolean), soft deletes.
  - Foreign keys: role_id (nullable), department_id (nullable).
  - Timestamps: created_at, updated_at, deleted_at.
- Constraints and indexes
  - role_id is a foreign key to roles.id with on-delete SET NULL.
  - role_id is indexed.
  - email is unique.
  - department_id is a foreign key to departements.id with on-delete SET NULL.
- Relationships
  - belongs to Role via role_id.
  - belongs to Department via department_id.
  - has many Responses via user_id.
  - has many created Questionnaires via created_by.
- Business logic and access control
  - Role slug resolution: resolves slug from role_id if present, otherwise falls back to legacy role column.
  - Admin detection: checks against configured admin slugs.
  - Evaluator detection: checks configured evaluator slugs; if none configured and role_id exists, any non-admin role counts as evaluator.
  - Management capability: canManageRoles returns true for admin roles.
- Validation and normalization
  - Legacy role column normalized to role_id via migration that populates role_id from matching roles.slug and drops the legacy column after migration.

```mermaid
classDiagram
class User {
+responses()
+createdQuestionnaires()
+departmentRef()
+roleRef()
+roleSlug() string
+hasAnyRoleSlug(slugs) bool
+isAdminRole() bool
+isEvaluatorRole() bool
+canManageRoles() bool
}
class Role {
+users()
}
class Departement {
+users()
+answers()
+answerOptions()
}
User --> Role : "belongsTo(role_id)"
User --> Departement : "belongsTo(department_id)"
```

**Diagram sources**
- [User.php:39-57](file://app/Models/User.php#L39-L57)
- [Role.php:26-29](file://app/Models/Role.php#L26-L29)
- [Departement.php:19-32](file://app/Models/Departement.php#L19-L32)

**Section sources**
- [User.php:16-37](file://app/Models/User.php#L16-L37)
- [User.php:39-57](file://app/Models/User.php#L39-L57)
- [User.php:59-92](file://app/Models/User.php#L59-L92)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)
- [alter_role_column_type_on_users_table.php:11-19](file://database/migrations/2026_04_17_140500_alter_role_column_type_on_users_table.php#L11-L19)

### Role Model
- Fields and types
  - Identity: id, name (unique), slug (unique), description (nullable), prosentase (decimal with precision 5, scale 2), is_active (boolean), timestamps.
- Relationships
  - has many Users via role_id.
- Notes
  - The prosentase field is cast to decimal with two decimals for consistent numeric handling.

```mermaid
classDiagram
class Role {
+users()
}
class User {
+roleRef()
}
Role <.. User : "inverse"
```

**Diagram sources**
- [Role.php:26-29](file://app/Models/Role.php#L26-L29)
- [User.php:54-57](file://app/Models/User.php#L54-L57)

**Section sources**
- [Role.php:13-24](file://app/Models/Role.php#L13-L24)
- [Role.php:26-29](file://app/Models/Role.php#L26-L29)
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)

### Department Model
- Fields and types
  - Identity: id, name (unique), urut (unsigned integer, default 0), description (nullable), timestamps.
- Indexes
  - urut is indexed for ordering.
- Relationships
  - has many Users via department_id.
  - has many Answers via department_id.
  - has many AnswerOptions via department_id.
- Notes
  - Department name is unique; ordering stored in urut; answers and answer options also scoped by department_id.

```mermaid
classDiagram
class Departement {
+users()
+answers()
+answerOptions()
}
class User {
+departmentRef()
}
Departement <.. User : "inverse"
```

**Diagram sources**
- [Departement.php:19-32](file://app/Models/Departement.php#L19-L32)
- [User.php:49-52](file://app/Models/User.php#L49-L52)

**Section sources**
- [Departement.php:13-17](file://app/Models/Departement.php#L13-L17)
- [Departement.php:19-32](file://app/Models/Departement.php#L19-L32)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)

### RBAC Implementation and Access Control
- Role slugs and configuration
  - Admin slugs and evaluator slugs are defined in configuration.
  - Dashboard paths are mapped per role slug.
  - Aliases and labels support role normalization and presentation.
- Middleware enforcement
  - EnsureUserHasRole: checks that the authenticated user’s role slug matches any provided slug(s); denies unauthorized access otherwise.
  - EnsureUserIsAdmin: denies access unless the user’s role is considered admin.
  - EnsureUserIsEvaluator: denies access unless the user qualifies as an evaluator.
  - RedirectByRole: redirects authenticated users to a dashboard route based on their current role slug.
- User-level helpers
  - roleSlug(): resolves slug from role_id or legacy role column.
  - hasAnyRoleSlug(): checks membership in a set of slugs.
  - isAdminRole(): checks admin slugs.
  - isEvaluatorRole(): checks evaluator slugs or treats any non-admin role as evaluator when applicable.

```mermaid
sequenceDiagram
participant Client as "Client"
participant MW as "EnsureUserHasRole"
participant User as "User"
participant RBAC as "RBAC Config"
Client->>MW : "HTTP request with user"
MW->>User : "hasAnyRoleSlug(slugs)?"
User->>RBAC : "resolve roleSlug()"
RBAC-->>User : "slug value"
User-->>MW : "bool result"
alt "Authorized"
MW-->>Client : "allow"
else "Unauthorized"
MW-->>Client : "403 Forbidden"
end
```

**Diagram sources**
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [User.php:59-67](file://app/Models/User.php#L59-L67)
- [rbac.php:4-6](file://config/rbac.php#L4-L6)

**Section sources**
- [rbac.php:4-63](file://config/rbac.php#L4-L63)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [RedirectByRole.php:19-29](file://app/Http/Middleware/RedirectByRole.php#L19-L29)
- [User.php:59-92](file://app/Models/User.php#L59-L92)

## Dependency Analysis
- Primary keys
  - roles.id
  - departements.id
  - users.id
- Foreign keys
  - users.role_id → roles.id (ON DELETE SET NULL)
  - users.department_id → departements.id (ON DELETE SET NULL)
- Indexes
  - users.role_id (indexed)
  - departements.urut (indexed)
  - users.email (unique)
  - roles.name/slug (unique)
  - departements.name (unique)
- Constraints
  - role_id and department_id are nullable in users.
  - Legacy role column was migrated to role_id and later dropped via type change migration.

```mermaid
flowchart TD
Start(["Schema Initialization"]) --> Roles["Create roles<br/>unique name/slug"]
Start --> Depts["Create departements<br/>unique name, index urut"]
Start --> Users["Create users<br/>unique email, indexes"]
Users --> FK_Role["Add role_id FK to roles<br/>index + null-on-delete"]
Users --> FK_Dept["Add department_id FK to departements<br/>null-on-delete"]
Users --> Legacy["Legacy role column migration<br/>populate role_id from slug"]
Legacy --> DropLegacy["Alter role column type<br/>drop legacy column"]
Roles --> RBAC["RBAC config defines slugs and permissions"]
Users --> MW["Middleware enforce slugs"]
RBAC --> MW
```

**Diagram sources**
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)
- [create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)
- [alter_role_column_type_on_users_table.php:11-19](file://database/migrations/2026_04_17_140500_alter_role_column_type_on_users_table.php#L11-L19)
- [rbac.php:4-63](file://config/rbac.php#L4-L63)

**Section sources**
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)
- [create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)
- [alter_role_column_type_on_users_table.php:11-19](file://database/migrations/2026_04_17_140500_alter_role_column_type_on_users_table.php#L11-L19)

## Performance Considerations
- Indexes
  - role_id and department_id are indexed on users to speed up joins and filtering.
  - departements.urut is indexed to support ordered queries.
  - users.email is unique, aiding fast lookups and preventing duplicates.
- Casting
  - Boolean and decimal casts reduce ORM overhead and ensure consistent handling.
- Nullable FKs
  - Allowing null enables graceful handling of unassigned roles/departments during onboarding or cleanup.

## Troubleshooting Guide
- Unauthorized access errors
  - Ensure the user’s role slug is included in the middleware guard’s slug list.
  - Verify RBAC configuration for admin and evaluator slugs.
- Role mismatch after migration
  - Confirm that role_id was populated from legacy role values and that the migration executed successfully.
- Unexpected evaluator/admin classification
  - Check RBAC evaluator fallback logic when evaluator slugs are not configured but role_id exists.

**Section sources**
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)
- [EnsureUserIsAdmin.php:12-21](file://app/Http/Middleware/EnsureUserIsAdmin.php#L12-L21)
- [EnsureUserIsEvaluator.php:12-21](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L12-L21)
- [rbac.php:4-63](file://config/rbac.php#L4-L63)
- [add_role_id_to_users_table.php:24-29](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L24-L29)

## Conclusion
The User, Role, and Department models form a clean RBAC foundation. Users are linked to roles and departments via foreign keys, with indexes and constraints ensuring referential integrity and query performance. Access control is enforced through middleware using a centralized RBAC configuration, enabling flexible role-based navigation and permission checks.

## Appendices

### Sample Data Structures
- Role
  - name: string (unique)
  - slug: string (unique)
  - description: text (nullable)
  - prosentase: decimal (e.g., 5-digit total with 2 decimals)
  - is_active: boolean
- Department
  - name: string (unique)
  - urut: unsigned integer (indexed)
  - description: text (nullable)
- User
  - name: string
  - email: string (unique)
  - role: string (legacy; migrated to role_id)
  - role_id: bigint (nullable, FK)
  - department_id: bigint (nullable, FK)
  - is_active: boolean
  - timestamps and soft deletes

**Section sources**
- [create_roles_table.php:14-22](file://database/migrations/2026_04_17_093035_create_roles_table.php#L14-L22)
- [create_departements_table.php:14-20](file://database/migrations/2026_04_17_000821_create_departements_table.php#L14-L20)
- [create_users_table.php:13-23](file://database/migrations/0001_01_01_000000_create_users_table.php#L13-L23)
- [add_is_active_to_users_table.php:14-16](file://database/migrations/2026_04_16_135841_add_is_active_to_users_table.php#L14-L16)
- [add_role_id_to_users_table.php:15-22](file://database/migrations/2026_04_17_093235_add_role_id_to_users_table.php#L15-L22)
- [add_department_id_to_users_table.php:14-20](file://database/migrations/2026_04_17_000854_add_department_id_to_users_table.php#L14-L20)