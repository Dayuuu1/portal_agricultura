<?php

namespace App\Policies;

class SettingPolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'datos_portal';
    }
}
