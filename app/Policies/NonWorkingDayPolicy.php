<?php

namespace App\Policies;

class NonWorkingDayPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'mesa_partes_config';
    }
}
