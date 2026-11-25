<?php

namespace Tests\Feature;

use App\Filament\Resources\Announcements\Widgets\AnnouncementActivityChart;
use App\Filament\Resources\Announcements\Widgets\AnnouncementStatsOverview;
use App\Models\Announcement;
use App\Models\AnnouncementStat;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AnnouncementStatsTest extends TestCase
{
    use RefreshDatabase;

    private function report(Announcement $announcement, string $event): TestResponse
    {
        return $this->post(route('announcements.track', $announcement), ['event' => $event]);
    }

    public function test_the_window_reports_add_up_in_one_row_per_day(): void
    {
        $announcement = Announcement::factory()->create();

        $this->report($announcement, 'view')->assertNoContent();
        $this->report($announcement, 'view')->assertNoContent();
        $this->report($announcement, 'click')->assertNoContent();
        $this->report($announcement, 'dismiss')->assertNoContent();

        $stat = AnnouncementStat::query()->sole();

        $this->assertTrue($stat->date->isToday());
        $this->assertSame([2, 1, 1], [$stat->views, $stat->clicks, $stat->dismissals]);
    }

    public function test_each_day_gets_its_own_row(): void
    {
        $announcement = Announcement::factory()->create();

        $this->travelTo(now()->subDay());
        $this->report($announcement, 'view');
        $this->travelBack();
        $this->report($announcement, 'view');

        $this->assertSame(2, AnnouncementStat::query()->count());
        $this->assertSame(['views' => 2, 'clicks' => 0, 'dismissals' => 0], AnnouncementStat::totals($announcement));
    }

    #[TestWith(['is_active', false])]
    #[TestWith(['ends_at', '2000-01-01 00:00:00'])]
    public function test_announcements_no_longer_visible_are_not_counted(string $attribute, mixed $value): void
    {
        $announcement = Announcement::factory()->create([$attribute => $value]);

        $this->report($announcement, 'view')->assertNoContent();

        $this->assertSame(0, AnnouncementStat::query()->count());
    }

    public function test_unknown_events_are_rejected(): void
    {
        $announcement = Announcement::factory()->create();

        $this->report($announcement, 'views = views + 1000')->assertSessionHasErrors('event');

        $this->assertSame(0, AnnouncementStat::query()->count());
    }

    public function test_a_visitor_cannot_inflate_the_figures(): void
    {
        $announcement = Announcement::factory()->create();

        for ($attempt = 0; $attempt < 30; $attempt++) {
            $this->report($announcement, 'view')->assertNoContent();
        }

        $this->report($announcement, 'view')->assertTooManyRequests();
        $this->assertSame(30, AnnouncementStat::query()->sole()->views);
    }

    public function test_the_daily_series_includes_quiet_days(): void
    {
        $announcement = Announcement::factory()->create();
        AnnouncementStat::factory()->for($announcement)->create(['date' => today(), 'views' => 40, 'clicks' => 4, 'dismissals' => 20]);
        AnnouncementStat::factory()->for($announcement)->create(['date' => today()->subDays(2), 'views' => 10, 'clicks' => 1, 'dismissals' => 5]);
        AnnouncementStat::factory()->for($announcement)->create(['date' => today()->subDays(30), 'views' => 999, 'clicks' => 0, 'dismissals' => 0]);

        $series = AnnouncementStat::series($announcement, 7);

        $this->assertCount(7, $series);
        $this->assertSame([0, 0, 0, 0, 10, 0, 40], array_column($series, 'views'));
        $this->assertSame(['views' => 50, 'clicks' => 5, 'dismissals' => 25], AnnouncementStat::totals($announcement, 7));
        $this->assertSame(10.0, AnnouncementStat::rate(5, 50));
        $this->assertNull(AnnouncementStat::rate(0, 0));
    }

    public function test_the_results_page_shows_the_key_figures(): void
    {
        $this->actingAs(User::factory()->withPermissions(['anuncios' => ['view']])->create());
        $announcement = Announcement::factory()->create(['title' => 'Feria del cacao']);
        AnnouncementStat::factory()->for($announcement)->create(['views' => 1200, 'clicks' => 54, 'dismissals' => 700]);

        $this->get('/admin/anuncios/'.$announcement->id.'/resultados')
            ->assertOk()
            ->assertSee('Resultados del anuncio')
            ->assertSee('«Feria del cacao»', false);

        Livewire::withoutLazyLoading()
            ->test(AnnouncementStatsOverview::class, ['record' => $announcement])
            ->assertSee('1.200')
            ->assertSee('54')
            ->assertSee('4,5 %')
            ->assertSee('58,3 % de las vistas');

        Livewire::withoutLazyLoading()
            ->test(AnnouncementActivityChart::class, ['record' => $announcement])
            ->assertSee('1.200 vistas y 54 clics');
    }

    public function test_the_list_shows_views_and_click_rate(): void
    {
        $this->actingAs(User::factory()->admin()->create());
        $announcement = Announcement::factory()->create();
        AnnouncementStat::factory()->for($announcement)->create(['date' => today(), 'views' => 800, 'clicks' => 20]);
        AnnouncementStat::factory()->for($announcement)->create(['date' => today()->subDay(), 'views' => 700, 'clicks' => 10]);

        $this->get('/admin/anuncios')
            ->assertOk()
            ->assertSee('1.500')
            ->assertSee('2,0 %');
    }

    public function test_the_results_require_access_to_announcements(): void
    {
        $announcement = Announcement::factory()->create();

        $this->actingAs(User::factory()->withPermissions(['noticias' => ['view']])->create())
            ->get('/admin/anuncios/'.$announcement->id.'/resultados')
            ->assertForbidden();
    }
}
