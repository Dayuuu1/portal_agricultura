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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
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
            'identity_verified_at' => 'datetime',
            'privacy_accepted_at' => 'datetime',
            'truthfulness_declared_at' => 'datetime',
            'e_notifications_authorized_at' => 'datetime',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password' => 'hashed',
        ];
    }

    /**
     * Staff member who checked that the document belongs to the person behind the account.
     *
     * @return BelongsTo<User, $this>
     */
    public function identityVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identity_verified_by');
    }

    /**
     * The staff checked the identity document. Until then the account rests only on what the person declared.
     */
    public function hasVerifiedIdentity(): bool
    {
        return $this->identity_verified_at !== null;
    }

    public function markIdentityAsVerified(User $verifier): void
    {
        $this->forceFill(['identity_verified_at' => now(), 'identity_verified_by' => $verifier->id])->save();
    }

    public function clearIdentityVerification(): void
    {
        $this->forceFill(['identity_verified_at' => null, 'identity_verified_by' => null])->save();
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

    /**
     * Only the name: the document number is personal data and the history is readable by more people.
     */
    public function activityLogLabel(): string
    {
        return $this->fullName();
    }

    /**
     * @return list<string>
     */
    protected function activityLogExcluded(): array
    {
        return ['password', 'remember_token', 'last_login_at'];
    }

    /**
     * Personal data (Ley N° 29733) stays in the account; the history only records that it changed.
     *
     * @return list<string>
     */
    protected function activityLogMasked(): array
    {
        return [
            'document_number', 'first_name', 'last_name', 'business_name', 'representative_name', 'representative_document_number',
            'email', 'phone', 'department', 'province', 'district', 'address',
        ];
    }
}
