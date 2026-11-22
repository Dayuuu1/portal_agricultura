<?php

namespace App\Policies;

class SubmissionPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'mesa_partes';
    }
}
