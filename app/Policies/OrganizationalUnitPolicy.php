<?php

namespace App\Policies;

class OrganizationalUnitPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'unidades';
    }
}
