<?php

namespace App\Policies;

class ProcedureTypePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'mesa_partes_config';
    }
}
