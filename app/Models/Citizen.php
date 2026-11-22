<?php

namespace App\Models;

use App\Enums\IdentityDocumentType;
use App\Enums\PersonType;
use App\Models\Concerns\LogsActivity;
use App\Notifications\MesaPartes\ResetCitizenPassword;
use App\Notifications\MesaPartes\VerifyCitizenEmail;
use Database\Factories\CitizenFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Person or organization with an account in the Mesa de Partes Virtual. Kept apart from the panel users:
 * a citizen can never sign in to the administration panel.
 */
#[Fillable([
    'person_type', 'document_type', 'document_number', 'first_name', 'last_name', 'business_name',
    'representative_name', 'representative_document_number', 'email', 'phone', 'department', 'province', 'district', 'address',
    'password', 'privacy_accepted_at', 'truthfulness_declared_at', 'e_notifications_authorized_at', 'is_active', 'last_login_at',
])]
#[Hidden(['password', 'remember_token'])]
class Citizen extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<CitizenFactory> */
    use HasFactory, LogsActivity, Notifiable;

    protected $attributes = [
        'person_type' => 'natural',
        'is_active' => true,
    ];

    protected function casts(): array
    {
        return [
            'person_type' => PersonType::class,
            'document_type' => IdentityDocumentType::class,
            'email_verified_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'truthfulness_declared_at' => 'datetime',
            'e_notifications_authorized_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    /**
     * @return HasMany<CitizenNotification, $this>
     */
    public function inbox(): HasMany
    {
        return $this->hasMany(CitizenNotification::class)->latest('deposited_at')->latest('id');
    }

    public function fullName(): string
    {
        return $this->person_type === PersonType::Juridica
            ? (string) $this->business_name
            : trim($this->first_name.' '.$this->last_name);
    }

    public function documentLabel(): string
    {
        return $this->document_type->getLabel().' '.$this->document_number;
    }

    /**
     * The citizen accepted to be notified by electronic means (TUO de la Ley N° 27444, art. 20.4).
     */
    public function acceptsElectronicNotifications(): bool
    {
        return $this->e_notifications_authorized_at !== null;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyCitizenEmail);
    }

    /**
     * @param  string  $token
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetCitizenPassword($token));
    }

    public function activityLogLabel(): string
    {
        return $this->fullName().' ('.$this->documentLabel().')';
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcluded(): array
    {
        return ['password', 'remember_token', 'last_login_at'];
    }
}
