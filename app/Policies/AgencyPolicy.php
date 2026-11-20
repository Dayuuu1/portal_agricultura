<?php

namespace App\Policies;

class AgencyPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'agencias';
    }
}
