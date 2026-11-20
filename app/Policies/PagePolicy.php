<?php

namespace App\Policies;

class PagePolicy extends PublishablePolicy
{
    protected function module(): string
    {
        return 'paginas';
    }
}
