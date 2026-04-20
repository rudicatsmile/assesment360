# Evaluation Workflows

<cite>
**Referenced Files in This Document**
- [routes/web.php](file://routes/web.php)
- [config/rbac.php](file://config/rbac.php)
- [app/Http/Middleware/EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
- [app/Http/Middleware/EnsureUserIsEvaluator.php](file://app/Http/Middleware/EnsureUserIsEvaluator.php)
- [app/Http/Middleware/RedirectByRole.php](file://app/Http/Middleware/RedirectByRole.php)
- [app/Livewire/Fill/AvailableQuestionnaires.php](file://app/Livewire/Fill/AvailableQuestionnaires.php)
- [app/Livewire/Fill/QuestionnaireFill.php](file://app/Livewire/Fill/QuestionnaireFill.php)
- [app/Livewire/Fill/TeacherDashboard.php](file://app/Livewire/Fill/TeacherDashboard.php)
- [app/Livewire/Fill/StaffDashboard.php](file://app/Livewire/Fill/StaffDashboard.php)
- [app/Livewire/Fill/ParentDashboard.php](file://app/Livewire/Fill/ParentDashboard.php)
- [app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php)
- [app/Services/QuestionnaireScorer.php](file://app/Services/QuestionnaireScorer.php)
- [app/Services/DepartmentAnalyticsService.php](file://app/Services/DepartmentAnalyticsService.php)
- [app/Models/User.php](file://app/Models/User.php)
- [app/Models/Questionnaire.php](file://app/Models/Questionnaire.php)
- [app/Models/Question.php](file://app/Models/Question.php)
- [app/Models/Response.php](file://app/Models/Response.php)
- [app/Exports/QuestionnaireReportExport.php](file://app/Exports/QuestionnaireReportExport.php)
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
This document describes the evaluation and assessment workflow system, covering the end-to-end lifecycle from questionnaire assignment to completion, scoring, and analytics. It explains how different user roles (teacher, staff, parent) access dashboards, how the questionnaire filling interface works, and how scoring and result aggregation are performed. It also provides scenario-based walkthroughs of typical user interactions.

## Project Structure
The evaluation workflow spans routing, middleware-driven role gating, Livewire components for dashboards and forms, Eloquent models for domain entities, and services for scoring and analytics. The routes define the entry points for evaluators and administrators, while middleware ensures only authorized users can access evaluator pages.

```mermaid
graph TB
subgraph "Routing"
RWEB["routes/web.php"]
end
subgraph "Middleware"
M1["EnsureUserHasRole.php"]
M2["EnsureUserIsEvaluator.php"]
M3["RedirectByRole.php"]
end
subgraph "Livewire - Dashboards"
D1["TeacherDashboard.php"]
D2["StaffDashboard.php"]
D3["ParentDashboard.php"]
DC["HasEvaluatorDashboardMetrics.php"]
end
subgraph "Livewire - Forms"
F1["AvailableQuestionnaires.php"]
F2["QuestionnaireFill.php"]
end
subgraph "Domain Models"
U["User.php"]
QZ["Questionnaire.php"]
Q["Question.php"]
RSP["Response.php"]
end
subgraph "Services"
S1["QuestionnaireScorer.php"]
S2["DepartmentAnalyticsService.php"]
end
subgraph "Exports"
E1["QuestionnaireReportExport.php"]
end
RWEB --> M3
RWEB --> M2
RWEB --> M1
RWEB --> D1
RWEB --> D2
RWEB --> D3
D1 --> DC
D2 --> DC
D3 --> DC
RWEB --> F1
RWEB --> F2
D1 --> U
D2 --> U
D3 --> U
F1 --> U
F2 --> U
F2 --> QZ
F2 --> Q
F2 --> RSP
F1 --> QZ
F1 --> RSP
S1 --> Q
S1 --> RSP
S2 --> RSP
E1 --> S1
```

**Diagram sources**
- [routes/web.php:149-160](file://routes/web.php#L149-L160)
- [app/Http/Middleware/EnsureUserHasRole.php:1-28](file://app/Http/Middleware/EnsureUserHasRole.php#L1-L28)
- [app/Http/Middleware/EnsureUserIsEvaluator.php:1-23](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L1-L23)
- [app/Http/Middleware/RedirectByRole.php:1-31](file://app/Http/Middleware/RedirectByRole.php#L1-L31)
- [app/Livewire/Fill/TeacherDashboard.php:1-23](file://app/Livewire/Fill/TeacherDashboard.php#L1-L23)
- [app/Livewire/Fill/StaffDashboard.php:1-23](file://app/Livewire/Fill/StaffDashboard.php#L1-L23)
- [app/Livewire/Fill/ParentDashboard.php:1-23](file://app/Livewire/Fill/ParentDashboard.php#L1-L23)
- [app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php:1-73](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L1-L73)
- [app/Livewire/Fill/AvailableQuestionnaires.php:1-64](file://app/Livewire/Fill/AvailableQuestionnaires.php#L1-L64)
- [app/Livewire/Fill/QuestionnaireFill.php:1-515](file://app/Livewire/Fill/QuestionnaireFill.php#L1-L515)
- [app/Models/User.php:1-94](file://app/Models/User.php#L1-L94)
- [app/Models/Questionnaire.php:1-131](file://app/Models/Questionnaire.php#L1-L131)
- [app/Models/Question.php:1-43](file://app/Models/Question.php#L1-L43)
- [app/Models/Response.php:1-42](file://app/Models/Response.php#L1-L42)
- [app/Services/QuestionnaireScorer.php:1-139](file://app/Services/QuestionnaireScorer.php#L1-L139)
- [app/Services/DepartmentAnalyticsService.php:1-279](file://app/Services/DepartmentAnalyticsService.php#L1-L279)
- [app/Exports/QuestionnaireReportExport.php:1-29](file://app/Exports/QuestionnaireReportExport.php#L1-L29)

**Section sources**
- [routes/web.php:149-160](file://routes/web.php#L149-L160)
- [config/rbac.php:1-64](file://config/rbac.php#L1-L64)

## Core Components
- Routing and role redirection: Routes under the evaluator prefix and role redirect middleware guide users to appropriate dashboards.
- Middleware: Role-based gates ensure only eligible users access evaluator pages; evaluator-only gate prevents non-evaluators from entering.
- Dashboard components: Teacher, staff, and parent dashboards share a common metrics trait to compute available, completed, and summary counts.
- Questionnaire assignment and history: Users see active questionnaires targeting their role (and aliases), draft and submitted histories.
- Questionnaire filling: Interactive form with navigation, autosave on transitions, validation per question type, and final submission.
- Scoring and analytics: Per-answer score lookup via question option scores; summary aggregates by overall, per-group, question averages, and distributions; department-level analytics; exportable reports.

**Section sources**
- [routes/web.php:149-160](file://routes/web.php#L149-L160)
- [app/Http/Middleware/EnsureUserHasRole.php:1-28](file://app/Http/Middleware/EnsureUserHasRole.php#L1-L28)
- [app/Http/Middleware/EnsureUserIsEvaluator.php:1-23](file://app/Http/Middleware/EnsureUserIsEvaluator.php#L1-L23)
- [app/Http/Middleware/RedirectByRole.php:1-31](file://app/Http/Middleware/RedirectByRole.php#L1-L31)
- [app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php:1-73](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L1-L73)
- [app/Livewire/Fill/AvailableQuestionnaires.php:1-64](file://app/Livewire/Fill/AvailableQuestionnaires.php#L1-L64)
- [app/Livewire/Fill/QuestionnaireFill.php:1-515](file://app/Livewire/Fill/QuestionnaireFill.php#L1-L515)
- [app/Services/QuestionnaireScorer.php:1-139](file://app/Services/QuestionnaireScorer.php#L1-L139)
- [app/Services/DepartmentAnalyticsService.php:1-279](file://app/Services/DepartmentAnalyticsService.php#L1-L279)
- [app/Exports/QuestionnaireReportExport.php:1-29](file://app/Exports/QuestionnaireReportExport.php#L1-L29)

## Architecture Overview
The system separates concerns across routing, middleware, Livewire components, domain models, and services. Role slugs and aliases from configuration drive visibility and access. The questionnaire lifecycle is enforced by model relations and controller-less Livewire flows.

```mermaid
sequenceDiagram
participant Browser as "Browser"
participant Routes as "routes/web.php"
participant RBAC as "RedirectByRole.php"
participant Dash as "Teacher/Staff/Parent Dashboard"
participant Trait as "HasEvaluatorDashboardMetrics.php"
participant Models as "Questionnaire/User/Response"
Browser->>Routes : GET /dashboard
Routes->>RBAC : Apply redirect middleware
RBAC-->>Browser : Redirect to role-specific dashboard
Browser->>Dash : GET /fill/dashboard/{role}
Dash->>Trait : getDashboardMetricsByRole(roleSlug)
Trait->>Models : Query active questionnaires and responses
Models-->>Trait : Collections and counts
Trait-->>Dash : Metrics payload
Dash-->>Browser : Render dashboard
```

**Diagram sources**
- [routes/web.php:57-59](file://routes/web.php#L57-L59)
- [app/Http/Middleware/RedirectByRole.php:19-29](file://app/Http/Middleware/RedirectByRole.php#L19-L29)
- [app/Livewire/Fill/TeacherDashboard.php:16-21](file://app/Livewire/Fill/TeacherDashboard.php#L16-L21)
- [app/Livewire/Fill/StaffDashboard.php:16-21](file://app/Livewire/Fill/StaffDashboard.php#L16-L21)
- [app/Livewire/Fill/ParentDashboard.php:16-21](file://app/Livewire/Fill/ParentDashboard.php#L16-L21)
- [app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [app/Models/Questionnaire.php:37-50](file://app/Models/Questionnaire.php#L37-L50)
- [app/Models/Response.php:27-40](file://app/Models/Response.php#L27-L40)
- [app/Models/User.php:59-62](file://app/Models/User.php#L59-L62)

## Detailed Component Analysis

### Dashboard Views by Role
Each evaluator dashboard computes the same metrics for a specific role slug:
- Available questionnaires: active, targeted at the role or alias, and not yet submitted by the current user.
- Completed questionnaires: submitted responses for the current user and matching target groups.
- Stats: counts of active questionnaires, available to fill, and total completed.

```mermaid
flowchart TD
Start(["Load Dashboard"]) --> GetRole["Resolve roleSlug from user"]
GetRole --> Aliases["Compute targetGroups (role + alias)"]
Aliases --> QueryActive["Query active questionnaires by targetGroups"]
QueryActive --> ExcludeSubmitted["Exclude questionnaires already submitted by user"]
ExcludeSubmitted --> BuildPayload["Build available/completed collections and stats"]
BuildPayload --> End(["Render view"])
```

**Diagram sources**
- [app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [config/rbac.php:7-16](file://config/rbac.php#L7-L16)
- [app/Models/Questionnaire.php:37-50](file://app/Models/Questionnaire.php#L37-L50)
- [app/Models/Response.php:27-40](file://app/Models/Response.php#L27-L40)
- [app/Models/User.php:59-62](file://app/Models/User.php#L59-L62)

**Section sources**
- [app/Livewire/Fill/TeacherDashboard.php:16-21](file://app/Livewire/Fill/TeacherDashboard.php#L16-L21)
- [app/Livewire/Fill/StaffDashboard.php:16-21](file://app/Livewire/Fill/StaffDashboard.php#L16-L21)
- [app/Livewire/Fill/ParentDashboard.php:16-21](file://app/Livewire/Fill/ParentDashboard.php#L16-L21)
- [app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [config/rbac.php:12-16](file://config/rbac.php#L12-L16)

### Questionnaire Assignment and History
- Active questionnaires visible to the user are filtered by questionnaire targets aligned with the user’s role and configured aliases.
- Draft and submitted histories are fetched for the current user and matching target groups, ordered by date.

**Section sources**
- [app/Livewire/Fill/AvailableQuestionnaires.php:24-55](file://app/Livewire/Fill/AvailableQuestionnaires.php#L24-L55)
- [config/rbac.php:7-16](file://config/rbac.php#L7-L16)

### Questionnaire Filling Interface and Navigation
The filling component orchestrates:
- Initialization: validates login, active status, and role targeting; loads questions and existing draft answers.
- Navigation: previous, next, and direct jump to a question index; autosave queued on transitions.
- Validation: per-question rules vary by type (single choice, essay, combined).
- Submission: finalizes answers, persists calculated scores, marks response as submitted, and shows completion.

```mermaid
sequenceDiagram
participant User as "Evaluator User"
participant LW as "QuestionnaireFill.php"
participant DB as "DB (Responses/Answers)"
participant Score as "QuestionnaireScorer.php"
User->>LW : Open questionnaire
LW->>LW : Load questionnaire, questions, and draft answers
User->>LW : Navigate (previous/next/goTo)
LW->>LW : Mark dirty, dispatch autosave
User->>LW : Open submit confirmation
LW->>LW : Persist draft for dirty questions
LW->>LW : Validate required questions
alt Valid
User->>LW : Confirm submit
LW->>DB : Update response status=submitted
loop For each question
LW->>Score : calculateScoreForAnswer(question, optionId)
Score-->>LW : score|null
LW->>DB : Upsert answer with calculated_score
end
LW-->>User : Show thank you screen
else Invalid
LW-->>User : Highlight first invalid question
end
```

**Diagram sources**
- [app/Livewire/Fill/QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)
- [app/Livewire/Fill/QuestionnaireFill.php:124-186](file://app/Livewire/Fill/QuestionnaireFill.php#L124-L186)
- [app/Livewire/Fill/QuestionnaireFill.php:193-245](file://app/Livewire/Fill/QuestionnaireFill.php#L193-L245)
- [app/Livewire/Fill/QuestionnaireFill.php:301-388](file://app/Livewire/Fill/QuestionnaireFill.php#L301-L388)
- [app/Livewire/Fill/QuestionnaireFill.php:408-470](file://app/Livewire/Fill/QuestionnaireFill.php#L408-L470)
- [app/Services/QuestionnaireScorer.php:14-23](file://app/Services/QuestionnaireScorer.php#L14-L23)

**Section sources**
- [app/Livewire/Fill/QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)
- [app/Livewire/Fill/QuestionnaireFill.php:124-186](file://app/Livewire/Fill/QuestionnaireFill.php#L124-L186)
- [app/Livewire/Fill/QuestionnaireFill.php:193-245](file://app/Livewire/Fill/QuestionnaireFill.php#L193-L245)
- [app/Livewire/Fill/QuestionnaireFill.php:301-388](file://app/Livewire/Fill/QuestionnaireFill.php#L301-L388)
- [app/Livewire/Fill/QuestionnaireFill.php:408-470](file://app/Livewire/Fill/QuestionnaireFill.php#L408-L470)
- [app/Services/QuestionnaireScorer.php:14-23](file://app/Services/QuestionnaireScorer.php#L14-L23)

### Scoring Algorithm and Result Aggregation
- Per-answer scoring: The service returns the score associated with the selected answer option; missing option yields null.
- Summarization: Computes overall average, per-group averages, question-level averages, and distribution with counts and percentages.
- Distribution percentage: Derived from counts per option within each question, normalized by total responses per question.

```mermaid
flowchart TD
A["Summarize Questionnaire"] --> B["Collect roles from config"]
B --> C["Filter responses: submitted"]
C --> D["Respondent breakdown by role"]
C --> E["Overall average score"]
C --> F["Per-group averages"]
C --> G["Question averages (ordered desc)"]
C --> H["Distribution rows (counts)"]
H --> I["Convert to distribution with percentages"]
D --> J["Return payload"]
E --> J
F --> J
G --> J
I --> J
```

**Diagram sources**
- [app/Services/QuestionnaireScorer.php:33-112](file://app/Services/QuestionnaireScorer.php#L33-L112)
- [app/Services/QuestionnaireScorer.php:118-137](file://app/Services/QuestionnaireScorer.php#L118-L137)

**Section sources**
- [app/Services/QuestionnaireScorer.php:14-23](file://app/Services/QuestionnaireScorer.php#L14-L23)
- [app/Services/QuestionnaireScorer.php:33-112](file://app/Services/QuestionnaireScorer.php#L33-L112)
- [app/Services/QuestionnaireScorer.php:118-137](file://app/Services/QuestionnaireScorer.php#L118-L137)

### Department-Level Analytics and Export
- Department analytics: Computes average scores and participation rates per department, optionally filtered by date range and department.
- Role-level summaries: Participation rate and average score per role within a department.
- User-level summaries: Submission counts and average score per user within a department and role.
- Export: Generates a multi-sheet Excel report containing summary and raw answers, powered by the scorer’s analytics.

**Section sources**
- [app/Services/DepartmentAnalyticsService.php:20-95](file://app/Services/DepartmentAnalyticsService.php#L20-L95)
- [app/Services/DepartmentAnalyticsService.php:109-189](file://app/Services/DepartmentAnalyticsService.php#L109-L189)
- [app/Services/DepartmentAnalyticsService.php:199-255](file://app/Services/DepartmentAnalyticsService.php#L199-L255)
- [app/Exports/QuestionnaireReportExport.php:19-27](file://app/Exports/QuestionnaireReportExport.php#L19-L27)

## Dependency Analysis
The following diagram highlights key dependencies among components involved in the evaluation workflow.

```mermaid
classDiagram
class User {
+roleSlug() string
+isEvaluatorRole() bool
}
class Questionnaire {
+targets()
+questions()
+responses()
+syncTargetGroups(groups)
+targetGroups()
}
class Question {
+answerOptions()
+answers()
}
class Response {
+questionnaire()
+user()
+answers()
}
class QuestionnaireScorer {
+calculateScoreForAnswer(question, optionId) int|null
+summarizeQuestionnaire(questionnaire) array
}
class AvailableQuestionnaires {
+render() void
}
class QuestionnaireFill {
+mount(questionnaire) void
+previousQuestion() void
+nextQuestion() void
+submitFinal() void
}
class TeacherDashboard
class StaffDashboard
class ParentDashboard
AvailableQuestionnaires --> Questionnaire : "filters"
AvailableQuestionnaires --> Response : "draft/submitted history"
QuestionnaireFill --> Questionnaire : "loads"
QuestionnaireFill --> Question : "loads"
QuestionnaireFill --> Response : "creates/updates"
QuestionnaireFill --> QuestionnaireScorer : "uses"
QuestionnaireScorer --> Question : "reads options"
QuestionnaireScorer --> Response : "aggregates"
TeacherDashboard --> User : "metrics"
StaffDashboard --> User : "metrics"
ParentDashboard --> User : "metrics"
```

**Diagram sources**
- [app/Models/User.php:59-87](file://app/Models/User.php#L59-L87)
- [app/Models/Questionnaire.php:37-83](file://app/Models/Questionnaire.php#L37-L83)
- [app/Models/Question.php:33-41](file://app/Models/Question.php#L33-L41)
- [app/Models/Response.php:27-40](file://app/Models/Response.php#L27-L40)
- [app/Services/QuestionnaireScorer.php:14-112](file://app/Services/QuestionnaireScorer.php#L14-L112)
- [app/Livewire/Fill/AvailableQuestionnaires.php:24-55](file://app/Livewire/Fill/AvailableQuestionnaires.php#L24-L55)
- [app/Livewire/Fill/QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)
- [app/Livewire/Fill/TeacherDashboard.php:16-21](file://app/Livewire/Fill/TeacherDashboard.php#L16-L21)
- [app/Livewire/Fill/StaffDashboard.php:16-21](file://app/Livewire/Fill/StaffDashboard.php#L16-L21)
- [app/Livewire/Fill/ParentDashboard.php:16-21](file://app/Livewire/Fill/ParentDashboard.php#L16-L21)

**Section sources**
- [app/Models/User.php:59-87](file://app/Models/User.php#L59-L87)
- [app/Models/Questionnaire.php:37-83](file://app/Models/Questionnaire.php#L37-L83)
- [app/Models/Question.php:33-41](file://app/Models/Question.php#L33-L41)
- [app/Models/Response.php:27-40](file://app/Models/Response.php#L27-L40)
- [app/Services/QuestionnaireScorer.php:14-112](file://app/Services/QuestionnaireScorer.php#L14-L112)
- [app/Livewire/Fill/AvailableQuestionnaires.php:24-55](file://app/Livewire/Fill/AvailableQuestionnaires.php#L24-L55)
- [app/Livewire/Fill/QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)

## Performance Considerations
- Autosave strategy: Draft persistence occurs on navigation transitions rather than continuous polling, reducing write load.
- Aggregation queries: Summary computations use grouped selects and joins; consider indexing on frequently filtered columns (e.g., responses.status, answers.calculated_score).
- Pagination: Department analytics paginates results to limit memory usage for large datasets.
- Caching: Role and user analytics are cached for short periods to reduce repeated heavy queries.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and remedies:
- Access denied during questionnaire fill:
  - Ensure the user is logged in and the questionnaire is active and targeted to the user’s role.
  - Prevented by guards in the mounting logic and middleware.
- Duplicate submission:
  - The system checks for an existing submitted response and redirects if found.
- Validation failures:
  - Required single-choice, essay, or combined answers trigger immediate focus on the invalid question.
- Autosave not persisting:
  - Autosave triggers on navigation; ensure navigation actions are used rather than page refreshes.

**Section sources**
- [app/Livewire/Fill/QuestionnaireFill.php:49-79](file://app/Livewire/Fill/QuestionnaireFill.php#L49-L79)
- [app/Livewire/Fill/QuestionnaireFill.php:172-186](file://app/Livewire/Fill/QuestionnaireFill.php#L172-L186)
- [app/Livewire/Fill/QuestionnaireFill.php:342-388](file://app/Livewire/Fill/QuestionnaireFill.php#L342-L388)
- [app/Livewire/Fill/QuestionnaireFill.php:156-159](file://app/Livewire/Fill/QuestionnaireFill.php#L156-L159)

## Conclusion
The evaluation workflow integrates role-aware routing, robust middleware, and Livewire-driven dashboards and forms. It enforces assignment rules, supports guided navigation with autosave, validates inputs per question type, and computes accurate scores and analytics. Administrators can export consolidated reports for deeper insights.

[No sources needed since this section summarizes without analyzing specific files]

## Appendices

### Workflow Scenarios and User Interactions
- Teacher dashboard:
  - Loads available and completed questionnaires based on the teacher role slug and aliases; shows summary statistics.
- Staff dashboard:
  - Mirrors teacher dashboard for staff role.
- Parent dashboard:
  - Mirrors teacher dashboard for parent role.
- Filling a questionnaire:
  - User opens an available questionnaire, navigates through questions, autosaves on transitions, reviews progress, and submits when ready.
- Scoring and reporting:
  - After submission, answers carry calculated scores; administrators can generate analytics and export reports.

**Section sources**
- [app/Livewire/Fill/TeacherDashboard.php:16-21](file://app/Livewire/Fill/TeacherDashboard.php#L16-L21)
- [app/Livewire/Fill/StaffDashboard.php:16-21](file://app/Livewire/Fill/StaffDashboard.php#L16-L21)
- [app/Livewire/Fill/ParentDashboard.php:16-21](file://app/Livewire/Fill/ParentDashboard.php#L16-L21)
- [app/Livewire/Fill/AvailableQuestionnaires.php:24-55](file://app/Livewire/Fill/AvailableQuestionnaires.php#L24-L55)
- [app/Livewire/Fill/QuestionnaireFill.php:124-186](file://app/Livewire/Fill/QuestionnaireFill.php#L124-L186)
- [app/Services/QuestionnaireScorer.php:33-112](file://app/Services/QuestionnaireScorer.php#L33-L112)
- [app/Exports/QuestionnaireReportExport.php:19-27](file://app/Exports/QuestionnaireReportExport.php#L19-L27)