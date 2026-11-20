<?php

namespace App\Policies;

class JobPostingPolicy extends PublishablePolicy
{
    protected function module(): string
    {
        return 'convocatorias';
    }
}
