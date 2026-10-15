<?php

namespace App\Domain\Education\Policies;

use App\Domain\Education\Models\Quiz;
use App\Models\User;

class QuizPolicy
{
    public function view(?User $user, Quiz $quiz): bool
    {
        return true;
    }

    public function attempt(User $user, Quiz $quiz): bool
    {
        return true;
    }
}
