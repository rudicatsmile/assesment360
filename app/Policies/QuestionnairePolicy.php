<?php

namespace App\Policies;

use App\Models\Questionnaire;
use App\Models\User;

class QuestionnairePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function view(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }

    public function create(User $user): bool
    {
        return $user->role === 'admin';
    }

    public function update(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }

    public function delete(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }

    public function restore(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }

    public function forceDelete(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }

    public function publish(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }

    public function close(User $user, Questionnaire $questionnaire): bool
    {
        return $user->role === 'admin';
    }
}
