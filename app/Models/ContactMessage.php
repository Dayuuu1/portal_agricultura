<?php

namespace App\Models;

use App\Enums\ContactMessageType;
use Database\Factories\ContactMessageFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type', 'full_name', 'document_number', 'email', 'phone', 'subject', 'message', 'ip_address', 'read_at',
])]
class ContactMessage extends Model
{
    /** @use HasFactory<ContactMessageFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => ContactMessageType::class,
            'read_at' => 'datetime',
        ];
    }

    #[Scope]
    protected function unread(Builder $query): void
    {
        $query->whereNull('read_at');
    }
}
