<?php

namespace Tests\Support;

trait InteractsWithRoleConfig
{
    protected function adminSlug(): string
    {
        return (string) ((array) config('rbac.admin_slugs', []))[0];
    }

    protected function teacherSlug(): string
    {
        return (string) config('rbac.dashboard_role_slugs.teacher');
    }

    protected function staffSlug(): string
    {
        return (string) config('rbac.dashboard_role_slugs.staff');
    }

    protected function parentSlug(): string
    {
        return (string) config('rbac.dashboard_role_slugs.parent');
    }

    /**
     * @return array<int, string>
     */
    protected function targetGroups(): array
    {
        return array_values(array_unique(array_filter((array) config('rbac.questionnaire_target_slugs', []))));
    }
}

