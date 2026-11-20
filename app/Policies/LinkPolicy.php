<?php

namespace App\Policies;

class LinkPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'enlaces';
    }
}
