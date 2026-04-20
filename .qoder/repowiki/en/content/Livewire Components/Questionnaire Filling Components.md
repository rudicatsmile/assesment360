# Questionnaire Filling Components

<cite>
**Referenced Files in This Document**
- [AvailableQuestionnaires.php](file://app/Livewire/Fill/AvailableQuestionnaires.php)
- [QuestionnaireFill.php](file://app/Livewire/Fill/QuestionnaireFill.php)
- [StaffDashboard.php](file://app/Livewire/Fill/StaffDashboard.php)
- [TeacherDashboard.php](file://app/Livewire/Fill/TeacherDashboard.php)
- [ParentDashboard.php](file://app/Livewire/Fill/ParentDashboard.php)
- [HasEvaluatorDashboardMetrics.php](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php)
- [evaluator.blade.php](file://resources/views/layouts/evaluator.blade.php)
- [available-questionnaires.blade.php](file://resources/views/livewire/fill/available-questionnaires.blade.php)
- [questionnaire-fill.blade.php](file://resources/views/livewire/fill/questionnaire-fill.blade.php)
- [staff-dashboard.blade.php](file://resources/views/livewire/fill/staff-dashboard.blade.php)
- [web.php](file://routes/web.php)
- [rbac.php](file://config/rbac.php)
- [features.php](file://config/features.php)
- [EnsureUserHasRole.php](file://app/Http/Middleware/EnsureUserHasRole.php)
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
This document explains the questionnaire filling and dashboard components used by evaluators (teachers, staff, and parents). It covers:
- Available questionnaires listing and filtering by role
- Interactive questionnaire forms with validation, progress tracking, and submission
- Role-specific dashboards with metrics and history
- Real-time saving behavior and navigation controls
- Lifecycle management, state persistence, and UX patterns
- Responsive design, accessibility, and cross-device considerations
- Examples for customizing dashboard layouts and questionnaire presentation styles

## Project Structure
The questionnaire and dashboard features are implemented with Laravel Livewire components backed by Blade templates. Routing groups separate admin and evaluator spaces, while RBAC configuration defines role slugs, target aliases, and dashboard paths.

```mermaid
graph TB
subgraph "Routes"
RWEB["routes/web.php"]
end
subgraph "Livewire Fill"
AQ["AvailableQuestionnaires.php"]
QF["QuestionnaireFill.php"]
SD["StaffDashboard.php"]
TD["TeacherDashboard.php"]
PD["ParentDashboard.php"]
METRICS["HasEvaluatorDashboardMetrics.php"]
end
subgraph "Blade Templates"
LAYOUT["layouts/evaluator.blade.php"]
LISTVIEW["available-questionnaires.blade.php"]
FORMVIEW["questionnaire-fill.blade.php"]
DSHV_STAFF["staff-dashboard.blade.php"]
end
subgraph "Config"
RBAC["config/rbac.php"]
FEAT["config/features.php"]
end
subgraph "Middleware"
GUARD_EVAL["EnsureUserHasRole.php"]
REDIR_ROLE["RedirectByRole.php"]
end
RWEB --> AQ
RWEB --> QF
RWEB --> SD
RWEB --> TD
RWEB --> PD
SD --> METRICS
TD --> METRICS
PD --> METRICS
AQ --> LISTVIEW
QF --> FORMVIEW
SD --> DSHV_STAFF
TD --> LAYOUT
PD --> LAYOUT
RBAC --> AQ
RBAC --> QF
RBAC --> SD
RBAC --> TD
RBAC --> PD
RBAC --> METRICS
FEAT --> QF
GUARD_EVAL --> RWEB
REDIR_ROLE --> RWEB
```

**Diagram sources**
- [web.php:149-160](file://routes/web.php#L149-L160)
- [AvailableQuestionnaires.php:11-62](file://app/Livewire/Fill/AvailableQuestionnaires.php#L11-L62)
- [QuestionnaireFill.php:18-514](file://app/Livewire/Fill/QuestionnaireFill.php#L18-L514)
- [StaffDashboard.php:9-22](file://app/Livewire/Fill/StaffDashboard.php#L9-L22)
- [TeacherDashboard.php:9-22](file://app/Livewire/Fill/TeacherDashboard.php#L9-L22)
- [ParentDashboard.php:9-22](file://app/Livewire/Fill/ParentDashboard.php#L9-L22)
- [HasEvaluatorDashboardMetrics.php:9-72](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L9-L72)
- [evaluator.blade.php:1-82](file://resources/views/layouts/evaluator.blade.php#L1-L82)
- [available-questionnaires.blade.php:1-85](file://resources/views/livewire/fill/available-questionnaires.blade.php#L1-L85)
- [questionnaire-fill.blade.php:1-402](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L1-L402)
- [staff-dashboard.blade.php:1-55](file://resources/views/livewire/fill/staff-dashboard.blade.php#L1-L55)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [features.php:1-7](file://config/features.php#L1-L7)
- [EnsureUserHasRole.php:9-26](file://app/Http/Middleware/EnsureUserHasRole.php#L9-L26)
- [RedirectByRole.php:9-30](file://app/Http/Middleware/RedirectByRole.php#L9-L30)

**Section sources**
- [web.php:149-160](file://routes/web.php#L149-L160)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)

## Core Components
- AvailableQuestionnaires: Lists active questionnaires targeted to the current user’s role, excludes those already submitted, and shows draft/submitted histories.
- QuestionnaireFill: Interactive form with navigation, validation, autosave triggers, progress tracking, and final submission.
- Dashboards (Staff, Teacher, Parent): Role-specific dashboards rendering metrics and history via a shared trait.
- Layout: Evaluator layout with header navigation, theme toggle, and responsive container.

Key capabilities:
- Role-aware filtering and access control
- Draft persistence per response
- Validation per question type and required fields
- Progress percentage and answered counts
- Single-question vs page-mode rendering
- Confirmation modal before submission

**Section sources**
- [AvailableQuestionnaires.php:14-62](file://app/Livewire/Fill/AvailableQuestionnaires.php#L14-L62)
- [QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)
- [QuestionnaireFill.php:247-299](file://app/Livewire/Fill/QuestionnaireFill.php#L247-L299)
- [QuestionnaireFill.php:301-388](file://app/Livewire/Fill/QuestionnaireFill.php#L301-L388)
- [QuestionnaireFill.php:408-470](file://app/Livewire/Fill/QuestionnaireFill.php#L408-L470)
- [StaffDashboard.php:14-21](file://app/Livewire/Fill/StaffDashboard.php#L14-L21)
- [TeacherDashboard.php:14-21](file://app/Livewire/Fill/TeacherDashboard.php#L14-L21)
- [ParentDashboard.php:14-21](file://app/Livewire/Fill/ParentDashboard.php#L14-L21)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [evaluator.blade.php:19-76](file://resources/views/layouts/evaluator.blade.php#L19-L76)

## Architecture Overview
The evaluator flow connects routes to Livewire components and Blade views, with RBAC configuration controlling role-based access and dashboard paths.

```mermaid
sequenceDiagram
participant U as "User"
participant RT as "routes/web.php"
participant MW as "Middleware"
participant LC as "Livewire Component"
participant BL as "Blade View"
participant CFG as "RBAC Config"
U->>RT : Request "/fill/dashboard/guru"
RT->>MW : Apply auth + evaluator gate + role redirect
MW-->>U : Redirect to configured dashboard path
RT->>LC : Resolve TeacherDashboard
LC->>CFG : Load dashboard role slug
LC->>BL : Render teacher-dashboard.blade.php
BL-->>U : Display metrics and history
```

**Diagram sources**
- [web.php:149-154](file://routes/web.php#L149-L154)
- [TeacherDashboard.php:10-21](file://app/Livewire/Fill/TeacherDashboard.php#L10-L21)
- [rbac.php:49-62](file://config/rbac.php#L49-L62)

## Detailed Component Analysis

### AvailableQuestionnaires Component
Responsibilities:
- Build target groups from user role and aliases
- Fetch active questionnaires matching targets and not yet submitted by the user
- Preload draft and submitted histories for quick access
- Pass data to the list view template

```mermaid
flowchart TD
Start(["Mount AvailableQuestionnaires"]) --> GetUser["Get Authenticated User"]
GetUser --> GetRole["Resolve Role Slug"]
GetRole --> BuildTargets["Build Target Groups (role + alias)"]
BuildTargets --> QueryActive["Query Active Questionnaires by Targets"]
QueryActive --> FilterSubmitted["Exclude Already Submitted Responses"]
FilterSubmitted --> LoadCounts["Load Questions Count"]
LoadCounts --> LoadDraft["Load Latest Draft Responses"]
LoadDraft --> LoadSubmitted["Load Latest Submitted Responses"]
LoadSubmitted --> Render["Render Template with Data"]
Render --> End(["Done"])
```

**Diagram sources**
- [AvailableQuestionnaires.php:16-59](file://app/Livewire/Fill/AvailableQuestionnaires.php#L16-L59)

**Section sources**
- [AvailableQuestionnaires.php:14-62](file://app/Livewire/Fill/AvailableQuestionnaires.php#L14-L62)
- [available-questionnaires.blade.php:1-85](file://resources/views/livewire/fill/available-questionnaires.blade.php#L1-L85)

### QuestionnaireFill Component
Responsibilities:
- Validate access by status, target group, and submission state
- Initialize questions and answers collection
- Manage navigation (previous/next/goTo), dirty tracking, and autosave triggers
- Validate per-question and all-required rules
- Persist drafts and final submission atomically
- Compute progress and answered counts

```mermaid
classDiagram
class QuestionnaireFill {
+Questionnaire questionnaire
+Response response
+Collection questions
+int currentIndex
+array answers
+bool showSubmitConfirmation
+bool showThankYou
+string lastDraftSavedAt
+array dirtyQuestionIds
+mount(questionnaire)
+previousQuestion()
+nextQuestion()
+goToQuestion(index)
+openSubmitConfirmation()
+closeSubmitConfirmation()
+submitFinal()
+getCurrentQuestionProperty()
+getAnsweredCountProperty()
+getProgressPercentProperty()
+getRequiredQuestionCountProperty()
+getAnsweredRequiredCountProperty()
-validateCurrentQuestion()
-validateAllRequiredQuestions() bool
-persistDraftForQuestions(questionIds)
-markCurrentQuestionDirty()
-normalizeOptionId(question, optionId) int?
-scorer() QuestionnaireScorer
+render()
}
```

**Diagram sources**
- [QuestionnaireFill.php:19-514](file://app/Livewire/Fill/QuestionnaireFill.php#L19-L514)

```mermaid
sequenceDiagram
participant U as "User"
participant VF as "QuestionnaireFill"
participant DB as "Database"
participant SC as "QuestionnaireScorer"
U->>VF : Click "Next"
VF->>VF : markCurrentQuestionDirty()
VF->>VF : currentIndex++
VF->>VF : dispatch("queue-autosave")
U->>VF : Click "Submit"
VF->>VF : persistDraftForQuestions(ids)
VF->>VF : validateAllRequiredQuestions()
VF-->>U : Show confirmation modal
U->>VF : Confirm submit
VF->>DB : Begin transaction
VF->>DB : Update response status to submitted
loop For each question
VF->>SC : calculateScoreForAnswer(question, optionId)
VF->>DB : upsert answer (score, department_id)
end
VF->>DB : Commit
VF-->>U : Show thank you screen
```

**Diagram sources**
- [QuestionnaireFill.php:124-144](file://app/Livewire/Fill/QuestionnaireFill.php#L124-L144)
- [QuestionnaireFill.php:172-191](file://app/Livewire/Fill/QuestionnaireFill.php#L172-L191)
- [QuestionnaireFill.php:193-245](file://app/Livewire/Fill/QuestionnaireFill.php#L193-L245)
- [QuestionnaireFill.php:408-470](file://app/Livewire/Fill/QuestionnaireFill.php#L408-L470)
- [QuestionnaireFill.php:495-498](file://app/Livewire/Fill/QuestionnaireFill.php#L495-L498)

**Section sources**
- [QuestionnaireFill.php:44-122](file://app/Livewire/Fill/QuestionnaireFill.php#L44-L122)
- [QuestionnaireFill.php:124-191](file://app/Livewire/Fill/QuestionnaireFill.php#L124-L191)
- [QuestionnaireFill.php:193-245](file://app/Livewire/Fill/QuestionnaireFill.php#L193-L245)
- [QuestionnaireFill.php:247-299](file://app/Livewire/Fill/QuestionnaireFill.php#L247-L299)
- [QuestionnaireFill.php:301-388](file://app/Livewire/Fill/QuestionnaireFill.php#L301-L388)
- [QuestionnaireFill.php:408-470](file://app/Livewire/Fill/QuestionnaireFill.php#L408-L470)
- [questionnaire-fill.blade.php:1-402](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L1-L402)

### Role-Specific Dashboards
Each dashboard resolves its role slug from configuration and delegates metric computation to a shared trait. The evaluator layout provides a unified header and navigation.

```mermaid
classDiagram
class StaffDashboard {
+render()
}
class TeacherDashboard {
+render()
}
class ParentDashboard {
+render()
}
class HasEvaluatorDashboardMetrics {
+getDashboardMetricsByRole(role) array
}
StaffDashboard ..> HasEvaluatorDashboardMetrics : "uses trait"
TeacherDashboard ..> HasEvaluatorDashboardMetrics : "uses trait"
ParentDashboard ..> HasEvaluatorDashboardMetrics : "uses trait"
```

**Diagram sources**
- [StaffDashboard.php:10-21](file://app/Livewire/Fill/StaffDashboard.php#L10-L21)
- [TeacherDashboard.php:10-21](file://app/Livewire/Fill/TeacherDashboard.php#L10-L21)
- [ParentDashboard.php:10-21](file://app/Livewire/Fill/ParentDashboard.php#L10-L21)
- [HasEvaluatorDashboardMetrics.php:9-72](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L9-L72)

**Section sources**
- [StaffDashboard.php:14-21](file://app/Livewire/Fill/StaffDashboard.php#L14-L21)
- [TeacherDashboard.php:14-21](file://app/Livewire/Fill/TeacherDashboard.php#L14-L21)
- [ParentDashboard.php:14-21](file://app/Livewire/Fill/ParentDashboard.php#L14-L21)
- [HasEvaluatorDashboardMetrics.php:11-71](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L11-L71)
- [evaluator.blade.php:19-76](file://resources/views/layouts/evaluator.blade.php#L19-L76)

### Navigation and Access Control
- Routes define evaluator space under a dedicated prefix and named groups.
- Middleware ensures authenticated users and restricts by role gates.
- Redirect middleware sends users to their role-specific dashboard path.

```mermaid
flowchart TD
A["Route: /fill/dashboard/*"] --> B["Auth Middleware"]
B --> C["Evaluator Gate Middleware"]
C --> D["RedirectByRole Middleware"]
D --> E{"Is role.dashboard?"}
E --> |Yes| F["Redirect to configured dashboard path"]
E --> |No| G["Proceed to controller/view"]
```

**Diagram sources**
- [web.php:149-154](file://routes/web.php#L149-L154)
- [RedirectByRole.php:11-29](file://app/Http/Middleware/RedirectByRole.php#L11-L29)
- [EnsureUserHasRole.php:11-25](file://app/Http/Middleware/EnsureUserHasRole.php#L11-L25)

**Section sources**
- [web.php:149-160](file://routes/web.php#L149-L160)
- [EnsureUserHasRole.php:9-26](file://app/Http/Middleware/EnsureUserHasRole.php#L9-L26)
- [RedirectByRole.php:9-30](file://app/Http/Middleware/RedirectByRole.php#L9-L30)

## Dependency Analysis
- Components depend on RBAC configuration for role slugs, target aliases, and dashboard paths.
- QuestionnaireFill depends on QuestionnaireScorer for scoring during submission.
- Views rely on Livewire directives and Alpine.js for client-side validation and UX.

```mermaid
graph LR
AQ["AvailableQuestionnaires"] --> RBAC["rbac.php"]
QF["QuestionnaireFill"] --> RBAC
SD["StaffDashboard"] --> RBAC
TD["TeacherDashboard"] --> RBAC
PD["ParentDashboard"] --> RBAC
QF --> SC["QuestionnaireScorer"]
QF --> FEAT["features.php"]
SD --> LAYOUT["evaluator.blade.php"]
TD --> LAYOUT
PD --> LAYOUT
```

**Diagram sources**
- [AvailableQuestionnaires.php:18-22](file://app/Livewire/Fill/AvailableQuestionnaires.php#L18-L22)
- [QuestionnaireFill.php:495-498](file://app/Livewire/Fill/QuestionnaireFill.php#L495-L498)
- [StaffDashboard.php:16-20](file://app/Livewire/Fill/StaffDashboard.php#L16-L20)
- [TeacherDashboard.php:16-20](file://app/Livewire/Fill/TeacherDashboard.php#L16-L20)
- [ParentDashboard.php:16-20](file://app/Livewire/Fill/ParentDashboard.php#L16-L20)
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [features.php:4](file://config/features.php#L4)
- [evaluator.blade.php:19](file://resources/views/layouts/evaluator.blade.php#L19)

**Section sources**
- [rbac.php:1-64](file://config/rbac.php#L1-L64)
- [features.php:1-7](file://config/features.php#L1-L7)

## Performance Considerations
- Efficient queries: eager load counts and relations to minimize N+1 issues.
- Transactional writes: batch upserts for answers and single delete for empty answers reduce DB round trips.
- Debounce input: textarea updates are debounced to limit server load.
- Conditional rendering: single-question mode reduces DOM size and re-renders.

Recommendations:
- Add pagination for long questionnaire lists if needed.
- Consider caching frequently accessed metrics for dashboards.
- Monitor autosave frequency and adjust debounce timing based on device performance.

[No sources needed since this section provides general guidance]

## Troubleshooting Guide
Common issues and resolutions:
- Access denied: Ensure user role matches questionnaire target groups and questionnaire is active.
- Already submitted: Users cannot re-open a questionnaire after submission; they are redirected to the listing.
- Validation errors: Required fields trigger immediate validation; errors highlight invalid questions and scroll to the validation panel.
- Autosave not triggering: Navigation actions dispatch a queue event; ensure Livewire and Alpine bindings are intact.

**Section sources**
- [QuestionnaireFill.php:49-79](file://app/Livewire/Fill/QuestionnaireFill.php#L49-L79)
- [QuestionnaireFill.php:172-191](file://app/Livewire/Fill/QuestionnaireFill.php#L172-L191)
- [questionnaire-fill.blade.php:103-115](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L103-L115)
- [questionnaire-fill.blade.php:69-75](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L69-L75)

## Conclusion
The questionnaire and dashboard system provides a cohesive, role-aware experience for evaluators. It emphasizes robust validation, progress visibility, and reliable persistence. The modular design allows easy customization of dashboards and questionnaire presentation modes.

[No sources needed since this section summarizes without analyzing specific files]

## Appendices

### Form Validation Rules and Behavior
- Single choice: required integer when question is required.
- Essay: required string with min/max length constraints.
- Combined: requires both selected option and essay text.
- On submit: validates all required questions; focuses first invalid question.

**Section sources**
- [QuestionnaireFill.php:301-335](file://app/Livewire/Fill/QuestionnaireFill.php#L301-L335)
- [QuestionnaireFill.php:342-388](file://app/Livewire/Fill/QuestionnaireFill.php#L342-L388)
- [questionnaire-fill.blade.php:15-68](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L15-L68)

### Progress Tracking and Metrics
- Progress percent computed from answered count vs total questions.
- Required vs answered required counts enable completion indicators.
- Metrics include active questionnaires, available to fill, and completed totals.

**Section sources**
- [QuestionnaireFill.php:247-299](file://app/Livewire/Fill/QuestionnaireFill.php#L247-L299)
- [HasEvaluatorDashboardMetrics.php:57-70](file://app/Livewire/Fill/Concerns/HasEvaluatorDashboardMetrics.php#L57-L70)

### Real-Time Saving and Navigation Controls
- Autosave triggered on navigation; heartbeat event handled via Alpine.
- Quick-access buttons for each question aid navigation.
- Single-question mode displays one question at a time with Previous/Next controls.

**Section sources**
- [questionnaire-fill.blade.php:69-75](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L69-L75)
- [questionnaire-fill.blade.php:117-135](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L117-L135)
- [questionnaire-fill.blade.php:289-320](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L289-L320)
- [features.php:4](file://config/features.php#L4)

### Responsive Design and Accessibility
- Layout uses a centered container with responsive padding and grid layouts.
- Buttons and inputs use accessible sizes and states; validation messages are announced via status region.
- Theme toggle persists user preference in local storage.

**Section sources**
- [evaluator.blade.php:26-76](file://resources/views/layouts/evaluator.blade.php#L26-L76)
- [questionnaire-fill.blade.php:387-401](file://resources/views/livewire/fill/questionnaire-fill.blade.php#L387-L401)

### Customization Examples
- Dashboard layout: Modify the grid columns and card content in the dashboard Blade templates.
- Questionnaire presentation: Toggle single-question mode via feature flag to change rendering behavior.
- Validation messaging: Adjust localized messages in the validation methods to reflect domain-specific terminology.

**Section sources**
- [staff-dashboard.blade.php:7-20](file://resources/views/livewire/fill/staff-dashboard.blade.php#L7-L20)
- [features.php:4](file://config/features.php#L4)
- [QuestionnaireFill.php:301-335](file://app/Livewire/Fill/QuestionnaireFill.php#L301-L335)