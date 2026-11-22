<?php

namespace App\Policies;

class CitizenPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'mesa_partes';
    }
}
