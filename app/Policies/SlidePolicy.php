<?php

namespace App\Policies;

class SlidePolicy extends ModulePolicy
{
    protected function module(): string
    {
        return 'banners';
    }
}
