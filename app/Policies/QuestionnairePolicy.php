<?php

namespace App\Policies;

use App\Models\Questionnaire;
use App\Models\User;

class QuestionnairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdminRole();
    }

    public function view(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }

    public function create(User $user): bool
    {
        return $user->isAdminRole();
    }

    public function update(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }

    public function delete(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }

    public function restore(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }

    public function forceDelete(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }

    public function publish(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }

    public function close(User $user, Questionnaire $questionnaire): bool
    {
        return $user->isAdminRole();
    }
}
