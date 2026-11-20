<?php

namespace App\Policies;

class JobPostingFilePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'convocatorias';
    }
}
