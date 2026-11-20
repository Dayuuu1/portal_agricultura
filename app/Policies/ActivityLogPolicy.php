<?php

namespace App\Policies;

/**
 * The audit trail only offers the "view" action, so it is read-only for everyone.
 */
class ActivityLogPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'actividad';
    }
}
