<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Document;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_changes_made_by_a_user_are_recorded_with_old_and_new_values(): void
    {
        $editor = User::factory()->admin()->create();
        $this->actingAs($editor);

        $post = Post::factory()->create(['title' => 'Título inicial']);
        $post->update(['title' => 'Título corregido']);
        $post->delete();

        $logs = ActivityLog::query()->where('subject_type', Post::class)->orderBy('id')->get();

        $this->assertSame(['created', 'updated', 'deleted'], $logs->pluck('event')->all());
        $this->assertTrue($logs->every(fn (ActivityLog $log): bool => $log->user_id === $editor->id));
        $this->assertSame(['old' => 'Título inicial', 'new' => 'Título corregido'], $logs[1]->changes['title']);
        $this->assertSame('Título corregido', $logs[2]->subject_label);
    }

    public function test_download_counters_and_passwords_are_never_recorded(): void
    {
        $user = User::factory()->create();
        $document = Document::factory()->create();
        ActivityLog::query()->delete();

        $document->update(['downloads' => 10]);
        $user->update(['password' => 'otra-contraseña-segura']);

        $this->assertSame(0, ActivityLog::query()->count());
    }

    public function test_logins_and_failed_attempts_are_audited(): void
    {
        $user = User::factory()->admin()->create(['email' => 'jefe@dramdd.gob.pe']);

        $this->assertFalse(Auth::attempt(['email' => 'jefe@dramdd.gob.pe', 'password' => 'incorrecta']));
        $this->assertTrue(Auth::attempt(['email' => 'jefe@dramdd.gob.pe', 'password' => 'password']));

        $this->assertDatabaseHas('activity_logs', ['event' => 'login_failed', 'subject_label' => 'jefe@dramdd.gob.pe']);
        $this->assertDatabaseHas('activity_logs', ['event' => 'login', 'user_id' => $user->id]);
        $this->assertNotNull($user->fresh()->last_login_at);
    }

    public function test_logging_can_be_paused_for_bulk_operations(): void
    {
        ActivityLog::withoutLogging(fn () => Post::factory()->count(3)->create());

        $this->assertSame(0, ActivityLog::query()->where('subject_type', Post::class)->count());
    }
}
