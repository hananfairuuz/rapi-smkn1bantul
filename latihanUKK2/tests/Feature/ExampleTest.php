<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake([
            'https://calendar.google.com/calendar/ical/*' => Http::response(
                "BEGIN:VCALENDAR\r\nBEGIN:VEVENT\r\nDTSTART;VALUE=DATE:20261103\r\nDESCRIPTION:Hari libur nasional\r\nSUMMARY:Hari Libur Test\r\nEND:VEVENT\r\nEND:VCALENDAR",
                200,
            ),
        ]);
    }

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/calendar');

        $response->assertOk()
            ->assertSee('images/school-logo.png')
            ->assertSee('class="public-login-button"', false)
            ->assertSee('Masuk');
    }

    public function test_logged_in_users_see_calendar_inside_the_application_layout(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::factory()->create(['role_id' => $role->id, 'whatsapp' => '628123456789']);

        $response = $this->actingAs($user)->get('/calendar');

        $response->assertOk()
            ->assertSee('Kalender Agenda Ruangan')
            ->assertSee('images/school-logo.png')
            ->assertSee('sidebar')
            ->assertSee('calendar-watermark', false);
    }

    public function test_login_page_is_available_to_guests_without_google_button(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertDontSee('Masuk dengan Google');
    }

    public function test_public_national_holiday_date_is_marked_red_in_calendar(): void
    {
        Cache::forget('indonesia_public_holidays_2026');

        $response = $this->get('/calendar?month=2026-11');

        $response
            ->assertOk()
            ->assertSee('title="Hari Libur Test"', false)
            ->assertSee('calendar-watermark', false);
        $this->assertMatchesRegularExpression('/class="calendar-day[^"]*\\bholiday\\b/', $response->getContent());
    }

    public function test_calendar_uses_indonesian_month_name_and_has_today_button(): void
    {
        Cache::forget('indonesia_public_holidays_2026');

        $this->get('/calendar?month=2026-11')
            ->assertOk()
            ->assertSee('November 2026')
            ->assertSee('Hari ini')
            ->assertSee(route('calendar', ['month' => today()->format('Y-m')]))
            ->assertSee('class="month-nav-next"', false);
    }

    public function test_calendar_highlights_the_current_date(): void
    {
        $this->travelTo(\Carbon\Carbon::parse('2026-11-04 12:00:00'));
        Cache::forget('indonesia_public_holidays_2026');

        $response = $this->get('/calendar?month=2026-11');

        $response->assertOk();
        $this->assertMatchesRegularExpression(
            '/<div class="calendar-day[^"]*\\btoday\\b[^"]*"\\s*>\\s*<span class="day-number">4<\\/span>/',
            $response->getContent(),
        );
    }
}
