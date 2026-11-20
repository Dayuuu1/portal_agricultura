<?php

namespace App\Policies;

class PostPolicy extends PublishablePolicy
{
    protected function module(): string
    {
        return 'noticias';
    }
}
