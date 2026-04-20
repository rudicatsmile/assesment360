# Parent Dashboard

<cite>
**Referenced Files in This Document**
- [ParentDashboard.php](file://app/Livewire/Fill/ParentDashboard.php)
- [parent-dashboard.blade.php](file://resources/views/livewire/fill/parent-dashboard.blade.php)
- [HasEvaluatorDashboardMetrics.php](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php)
- [AvailableQuestionnaires.php](file://app/Livewire/Fill/AvailableQuestionnaires.php)
- [available-questionnaires.blade.php](file://resources/views/livewire/fill/available-questionnaires.blade.php)
- [QuestionnaireFill.php](file://app/Livewire/Fill/QuestionnaireFill.php)
- [rbac.php](file://config/rbac.php)
- [web.php](file://routes/web.php)
- [EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
- [RedirectByRole.php](file://app/Http/Middleware/RedirectByRole.php)
- [Questionnaire.php](file://app/Models/Questionnaire.php)
- [Response.php](file://app/Models/Response.php)
- [Question.php](file://app/Models/Question.php)
- [Answer.php](file://app/Models/Answer.php)
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

## Introduction
This document describes the Parent Dashboard interface and its associated child assessment and feedback system. It explains how parents access available questionnaires, manage submissions, track completed assessments, and view educational evaluation metrics tailored to their role. The documentation covers dashboard components, navigation patterns, parent permissions, child-specific evaluation categories, and the end-to-end educational assessment workflow.

## Project Structure
The Parent Dashboard is part of the Fill module within a Livewire-driven frontend. It integrates with role-based access control (RBAC) configuration and leverages shared dashboard metrics logic. The key files include:
- ParentDashboard controller and Blade view
- Shared metrics trait used across evaluator dashboards
- Available questionnaires listing and submission history
- Questionnaire filling component and supporting models
- RBAC configuration and routing for evaluator dashboards

```mermaid
graph TB
subgraph "Routes"
RWEB["routes/web.php<br/>Defines fill.* routes"]
end
subgraph "Livewire Controllers"
PARENT_DASH["ParentDashboard.php<br/>Parent dashboard controller"]
AVAIL_Q["AvailableQuestionnaires.php<br/>List available & history"]
Q_FILL["QuestionnaireFill.php<br/>Questionnaire form & submission"]
end
subgraph "Shared Logic"
METRICS["HasEvaluatorDashboardMetrics.php<br/>Dashboard metrics by role"]
end
subgraph "Views"
VIEW_PARENT["parent-dashboard.blade.php"]
VIEW_AVAIL["available-questionnaires.blade.php"]
end
subgraph "RBAC & Middleware"
RBAC["config/rbac.php"]
ROLE_REDIRECT["RedirectByRole.php"]
ROLE_GUARD["EnsureUserHasRole.php"]
end
subgraph "Models"
MODEL_Q["Questionnaire.php"]
MODEL_R["Response.php"]
MODEL_QUE["Question.php"]
MODEL_A["Answer.php"]
MODEL_U["User.php"]
end
RWEB --> PARENT_DASH
RWEB --> AVAIL_Q
RWEB --> Q_FILL
PARENT_DASH --> METRICS
AVAIL_Q --> MODEL_Q
AVAIL_Q --> MODEL_R
Q_FILL --> MODEL_Q
Q_FILL --> MODEL_R
Q_FILL --> MODEL_QUE
Q_FILL --> MODEL_A
PARENT_DASH --> VIEW_PARENT
AVAIL_Q --> VIEW_AVAIL
RBAC --> ROLE_REDIRECT
RBAC --> ROLE_GUARD
RBAC --> PARENT_DASH
RBAC --> AVAIL_Q
RBAC --> Q_FILL
MODEL_U --> PARENT_DASH
MODEL_U --> AVAIL_Q
MODEL_U --> Q_FILL
```

**Diagram sources**
- [web.php:149-160](file://routes/web.php#L149-L160)
- [ParentDashboard.php:10-22](file://app/Livewire/Fill/ParentDashboard.php#L10-L22)
- [AvailableQuestionnaires.php:12-63](file://app/Livewire/Fill/AvailableQuestionnaires.php#L12-L63)
- [QuestionnaireFill.php:19-514](file://app/Livewire/Fill/QuestionnaireFill.php#L19-L514)
- [HasEvaluatorDashboardMetrics.php:9-72](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L9-L72)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [parent-dashboard.blade.php:1-55](file://resources/views/livewire/fill/parent-dashboard.blade.php#L1-L55)
- [available-questionnaires.blade.php:1-85](file://resources/views/livewire/fill/available-questionnaires.blade.php#L1-L85)
- [Questionnaire.php:13-131](file://app/Models/Questionnaire.php#L13-L131)
- [Response.php:11-42](file://app/Models/Response.php#L11-L42)
- [Question.php:11-43](file://app/Models/Question.php#L11-L43)
- [Answer.php:10-44](file://app/Models/Answer.php#L10-L44)
- [User.php:12-94](file://app/Models/User.php#L12-L94)

**Section sources**
- [web.php:149-160](file://routes/web.php#L149-L160)
- [rbac.php:12-24](file://config/rbac.php#L12-L24)

## Core Components
- ParentDashboard: Renders the parent’s dashboard, computes metrics via a shared trait, and displays available questionnaires and completed submissions.
- AvailableQuestionnaires: Lists active questionnaires targeted to the parent role, draft and submitted histories.
- QuestionnaireFill: Handles the interactive questionnaire form, autosave/draft persistence, and final submission with scoring.
- Shared metrics: Provides reusable logic to compute stats and lists for evaluator dashboards.
- RBAC and routing: Defines role slugs, aliases, dashboard paths, and middleware gates for evaluator roles.

Key capabilities:
- Role-aware filtering of questionnaires based on target groups and aliases.
- Metrics: Active questionnaires, available to fill, and total completed.
- Navigation: From dashboard to questionnaire forms and back to history.
- Submission lifecycle: Draft, autosave, review, and final submission.

**Section sources**
- [ParentDashboard.php:10-22](file://app/Livewire/Fill/ParentDashboard.php#L10-L22)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [AvailableQuestionnaires.php:14-62](file://app/Livewire/Fill/AvailableQuestionnaires.php#L14-L62)
- [QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)

## Architecture Overview
The Parent Dashboard follows a layered pattern:
- Routes define evaluator-accessible areas and dashboard endpoints.
- Controllers fetch data and delegate rendering to Blade views.
- Shared traits encapsulate dashboard metrics computation.
- Models represent questionnaires, responses, questions, and answers.
- RBAC configuration and middleware enforce role-based access and redirects.

```mermaid
sequenceDiagram
participant U as "User"
participant MW as "RedirectByRole.php"
participant RT as "routes/web.php"
participant PD as "ParentDashboard.php"
participant TR as "HasEvaluatorDashboardMetrics.php"
participant V as "parent-dashboard.blade.php"
U->>MW : "GET /dashboard"
MW->>RT : "Redirect to fill dashboard by role"
RT->>PD : "GET /fill/dashboard/parent"
PD->>TR : "getDashboardMetricsByRole('orang_tua')"
TR-->>PD : "metrics payload"
PD->>V : "Render dashboard view"
V-->>U : "Dashboard with stats and lists"
```

**Diagram sources**
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)
- [web.php:57-59](file://routes/web.php#L57-L59)
- [web.php:150-154](file://routes/web.php#L150-L154)
- [ParentDashboard.php:14-21](file://app/Livewire/Fill/ParentDashboard.php#L14-L21)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [parent-dashboard.blade.php:1-55](file://resources/views/livewire/fill/parent-dashboard.blade.php#L1-L55)

## Detailed Component Analysis

### Parent Dashboard Controller and View
- Controller responsibilities:
  - Reads the parent role slug from configuration.
  - Computes dashboard metrics using shared logic.
  - Passes metrics to the view for rendering.
- View responsibilities:
  - Displays three summary cards: active questionnaires, available to fill, and completed total.
  - Lists available questionnaires with quick action buttons.
  - Shows completed submission history with timestamps.

```mermaid
flowchart TD
Start(["ParentDashboard render"]) --> GetRole["Read role slug from config"]
GetRole --> Compute["Call getDashboardMetricsByRole(role)"]
Compute --> Stats["Build stats: active, available, completed"]
Stats --> ListAvail["Fetch available questionnaires"]
Stats --> ListDone["Fetch completed responses"]
ListAvail --> Render["Pass payload to view"]
ListDone --> Render
Render --> End(["Blade renders dashboard"])
```

**Diagram sources**
- [ParentDashboard.php:16-21](file://app/Livewire/Fill/ParentDashboard.php#L16-L21)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [parent-dashboard.blade.php:7-53](file://resources/views/livewire/fill/parent-dashboard.blade.php#L7-L53)

**Section sources**
- [ParentDashboard.php:10-22](file://app/Livewire/Fill/ParentDashboard.php#L10-L22)
- [parent-dashboard.blade.php:1-55](file://resources/views/livewire/fill/parent-dashboard.blade.php#L1-L55)

### Available Questionnaires Listing and History
- Filters active questionnaires targeting the parent role and its alias.
- Prevents duplicate submissions by excluding previously submitted responses.
- Provides quick actions to start or resume filling.
- Maintains draft and submitted history for the current user.

```mermaid
sequenceDiagram
participant U as "Parent User"
participant C as "AvailableQuestionnaires.php"
participant Q as "Questionnaire.php"
participant R as "Response.php"
participant V as "available-questionnaires.blade.php"
U->>C : "GET /fill/questionnaires"
C->>Q : "Query active questionnaires by target groups"
C->>R : "Exclude submitted responses for user"
C->>R : "Load latest draft and submitted responses"
C->>V : "Render list and history"
V-->>U : "Display available, draft, and submitted entries"
```

**Diagram sources**
- [AvailableQuestionnaires.php:24-55](file://app/Livewire/Fill/AvailableQuestionnaires.php#L24-L55)
- [available-questionnaires.blade.php:8-83](file://resources/views/livewire/fill/available-questionnaires.blade.php#L8-L83)
- [Questionnaire.php:37-50](file://app/Models/Questionnaire.php#L37-L50)
- [Response.php:27-40](file://app/Models/Response.php#L27-L40)

**Section sources**
- [AvailableQuestionnaires.php:12-63](file://app/Livewire/Fill/AvailableQuestionnaires.php#L12-L63)
- [available-questionnaires.blade.php:1-85](file://resources/views/livewire/fill/available-questionnaires.blade.php#L1-L85)

### Questionnaire Filling Workflow
- Access control:
  - Requires authentication and active questionnaire status.
  - Validates that the questionnaire targets the user’s role or alias.
  - Prevents resubmission of the same questionnaire.
- Data model:
  - Creates or loads a draft response per user-questionnaire pair.
  - Persists answers incrementally and supports combined/single choice/essay types.
- Submission:
  - Validates required questions before allowing submission.
  - Commits answers and marks the response as submitted with a timestamp.

```mermaid
sequenceDiagram
participant U as "Parent User"
participant RT as "routes/web.php"
participant F as "QuestionnaireFill.php"
participant Q as "Questionnaire.php"
participant R as "Response.php"
participant AN as "Answer.php"
U->>RT : "GET /fill/questionnaires/{id}"
RT->>F : "Instantiate QuestionnaireFill"
F->>Q : "Verify active status and target role"
F->>R : "Load or create draft response"
U->>F : "Navigate between questions"
F->>AN : "Upsert answers (draft)"
U->>F : "Open submit confirmation"
F->>F : "Validate required questions"
F->>R : "Set status=submitted and timestamp"
F->>AN : "Persist final answers with scores"
F-->>U : "Show thank you screen"
```

**Diagram sources**
- [web.php:156-159](file://routes/web.php#L156-L159)
- [QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)
- [QuestionnaireFill.php:193-245](file://app/Livewire/Fill/QuestionnaireFill.php#L193-L245)
- [Questionnaire.php:37-50](file://app/Models/Questionnaire.php#L37-L50)
- [Response.php:27-40](file://app/Models/Response.php#L27-L40)
- [Answer.php:24-42](file://app/Models/Answer.php#L24-L42)

**Section sources**
- [QuestionnaireFill.php:19-514](file://app/Livewire/Fill/QuestionnaireFill.php#L19-L514)

### Role-Based Permissions and Target Groups
- Role slugs and aliases:
  - Parent role slug is mapped to "orang_tua".
  - Aliases map "orang_tua" to "komite" for expanded targeting.
- Dashboard paths:
  - Parent dashboard route resolves to "/fill/dashboard/parent".
- Middleware:
  - EnsureUserHasRole enforces allowed role slugs.
  - RedirectByRole redirects unauthenticated users and routes authenticated users to their role dashboard.

```mermaid
flowchart TD
A["RBAC config"] --> B["Role slug: orang_tua"]
A --> C["Alias: orang_tua -> komite"]
A --> D["Dashboard path: /fill/dashboard/parent"]
E["RedirectByRole"] --> F["Redirect to dashboard path"]
G["EnsureUserHasRole"] --> H["Allow access if user has role slug"]
```

**Diagram sources**
- [rbac.php:7-16](file://config/rbac.php#L7-L16)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)
- [RedirectByRole.php:26-29](file://app/Http/Middleware/RedirectByRole.php#L26-L29)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)

**Section sources**
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [EnsureUserHasRole.php:1-28](file://app/Http/Middleware/EnsureUserHasRole.php#L1-L28)
- [RedirectByRole.php:1-31](file://app/Http/Middleware/RedirectByRole.php#L1-L31)

### Educational Evaluation Tracking
- Metrics computed by role:
  - Active questionnaires count for the parent role.
  - Available-to-fill count excludes previously submitted responses.
  - Completed total counts submitted responses for the parent role.
- Child-specific categories:
  - Questionnaire target groups are derived from roles and configured slugs.
  - Parents see questionnaires explicitly targeted to "orang_tua" or its alias "komite".

```mermaid
classDiagram
class User {
+roleSlug() string
+responses()
}
class Questionnaire {
+targets()
+questions()
+responses()
}
class Response {
+questionnaire()
+user()
+answers()
}
class Question {
+answerOptions()
+answers()
}
class Answer {
+response()
+question()
}
User "1" -- "many" Response : "creates"
Questionnaire "1" -- "many" Response : "has"
Questionnaire "1" -- "many" Question : "contains"
Response "1" -- "many" Answer : "accumulates"
Question "1" -- "many" Answer : "answers"
```

**Diagram sources**
- [User.php:39-62](file://app/Models/User.php#L39-L62)
- [Questionnaire.php:37-50](file://app/Models/Questionnaire.php#L37-L50)
- [Response.php:27-40](file://app/Models/Response.php#L27-L40)
- [Question.php:28-41](file://app/Models/Question.php#L28-L41)
- [Answer.php:24-42](file://app/Models/Answer.php#L24-L42)

**Section sources**
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [Questionnaire.php:88-108](file://app/Models/Questionnaire.php#L88-L108)

## Dependency Analysis
- ParentDashboard depends on:
  - RBAC configuration for role slugs and dashboard paths.
  - Shared metrics trait for computing stats and lists.
  - Blade view for rendering.
- AvailableQuestionnaires depends on:
  - Questionnaire and Response models to filter and list.
  - RBAC aliases to expand target groups.
- QuestionnaireFill depends on:
  - Questionnaire, Response, Question, and Answer models.
  - Scoring service for calculated scores.
  - RBAC for role-based access checks.

```mermaid
graph LR
RBAC["rbac.php"] --> PD["ParentDashboard.php"]
RBAC --> AV["AvailableQuestionnaires.php"]
RBAC --> QF["QuestionnaireFill.php"]
PD --> MET["HasEvaluatorDashboardMetrics.php"]
AV --> QM["Questionnaire.php"]
AV --> RM["Response.php"]
QF --> QM
QF --> RM
QF --> QUM["Question.php"]
QF --> AM["Answer.php"]
```

**Diagram sources**
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [ParentDashboard.php:10-22](file://app/Livewire/Fill/ParentDashboard.php#L10-L22)
- [AvailableQuestionnaires.php:12-63](file://app/Livewire/Fill/AvailableQuestionnaires.php#L12-L63)
- [QuestionnaireFill.php:19-514](file://app/Livewire/Fill/QuestionnaireFill.php#L19-L514)
- [HasEvaluatorDashboardMetrics.php:9-72](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L9-L72)
- [Questionnaire.php:13-131](file://app/Models/Questionnaire.php#L13-L131)
- [Response.php:11-42](file://app/Models/Response.php#L11-L42)
- [Question.php:11-43](file://app/Models/Question.php#L11-L43)
- [Answer.php:10-44](file://app/Models/Answer.php#L10-L44)

**Section sources**
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [web.php:149-160](file://routes/web.php#L149-L160)

## Performance Considerations
- Efficient queries:
  - Use of whereHas and whereDoesntHave to limit datasets early.
  - withCount and with eager loading reduce N+1 queries.
- Autosave strategy:
  - Draft persistence occurs during navigation to minimize data loss and server load.
- Pagination and ordering:
  - Ordering by start_date and latest timestamps ensures relevant items appear first.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and resolutions:
- Access denied when navigating to a questionnaire:
  - Ensure the questionnaire is active and targets the parent role or its alias.
  - Verify that the user has not already submitted the questionnaire.
- No available questionnaires displayed:
  - Confirm that active questionnaires exist and are targeted to "orang_tua" or "komite".
  - Check that the user has not previously submitted all applicable questionnaires.
- Submission errors:
  - Required questions must be answered before submission.
  - Combined types require both a selected option and an essay answer.

**Section sources**
- [QuestionnaireFill.php:53-79](file://app/Livewire/Fill/QuestionnaireFill.php#L53-L79)
- [QuestionnaireFill.php:342-388](file://app/Livewire/Fill/QuestionnaireFill.php#L342-L388)
- [AvailableQuestionnaires.php:24-39](file://app/Livewire/Fill/AvailableQuestionnaires.php#L24-L39)

## Conclusion
The Parent Dashboard provides a streamlined interface for parents to discover, fill, and track educational assessments aligned with their role. Through RBAC-driven targeting, autosave-enabled forms, and role-aware metrics, the system ensures secure, efficient, and transparent participation in the school evaluation process. The modular design allows easy extension to additional evaluation categories and reporting features.