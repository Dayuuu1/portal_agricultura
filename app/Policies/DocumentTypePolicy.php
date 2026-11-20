<?php

namespace App\Policies;

class DocumentTypePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'tipos_documento';
    }
}
