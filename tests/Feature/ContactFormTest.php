<?php

namespace Tests\Feature;

use App\Enums\ContactMessageType;
use App\Models\ContactMessage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'type' => ContactMessageType::Sugerencia->value,
            'full_name' => 'Rosa Quispe Mamani',
            'document_number' => '45678912',
            'email' => 'rosa@example.com',
            'phone' => '982 123 456',
            'subject' => 'Horario de atención',
            'message' => 'Sugiero ampliar el horario de atención los sábados.',
            'privacy' => '1',
            ...$overrides,
        ];
    }

    public function test_a_citizen_can_send_a_message(): void
    {
        $this->post(route('contact.store'), $this->validPayload())
            ->assertRedirect(route('contact.create'))
            ->assertSessionHas('status');

        $message = ContactMessage::query()->sole();
        $this->assertSame(ContactMessageType::Sugerencia, $message->type);
        $this->assertSame('rosa@example.com', $message->email);
        $this->assertNull($message->read_at);
        $this->assertNotNull($message->ip_address);
    }

    public function test_required_fields_and_privacy_consent_are_validated(): void
    {
        $this->post(route('contact.store'), $this->validPayload([
            'full_name' => '',
            'email' => 'no-es-correo',
            'message' => 'corto',
            'privacy' => null,
            'document_number' => '12AB',
            'type' => 'reclamo',
        ]))->assertSessionHasErrors(['full_name', 'email', 'message', 'privacy', 'document_number', 'type']);

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_submissions_with_the_honeypot_filled_are_rejected(): void
    {
        $this->post(route('contact.store'), $this->validPayload(['website' => 'http://spam.example']))
            ->assertSessionHasErrors('website');

        $this->assertDatabaseCount('contact_messages', 0);
    }

    public function test_the_form_is_rate_limited(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->post(route('contact.store'), $this->validPayload())->assertRedirect();
        }

        $this->post(route('contact.store'), $this->validPayload())->assertTooManyRequests();
    }
}
