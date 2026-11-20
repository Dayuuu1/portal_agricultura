<?php

namespace App\Policies;

/**
 * Claims are only viewed and answered: the module offers no create or delete action,
 * so nobody (administrators included) can remove an official record.
 */
class ComplaintPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'reclamaciones';
    }
}
