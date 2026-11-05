<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Support\LessonSchedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EventLessonScheduleTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_lesson_periods_run_from_seven_fifteen_am_in_forty_five_minute_blocks(): void
    {
        $periods = LessonSchedule::periods();

        $this->assertSame(['start' => '07:15', 'end' => '08:00'], $periods[1]);
        $this->assertSame(['start' => '17:00', 'end' => '17:45'], $periods[14]);
    }

    public function test_agenda_uses_selected_lesson_times_instead_of_submitted_custom_times(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($user)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Praktik',
            'borrower_name' => 'Guru RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'start_lesson' => 2,
            'end_lesson' => 4,
            'start_time' => '13:00',
            'end_time' => '14:00',
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '08:00',
            'end_time' => '10:15',
        ]);
    }

    public function test_agenda_rejects_lesson_numbers_outside_the_available_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($user)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Praktik',
            'borrower_name' => 'Guru RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'start_lesson' => 1,
            'end_lesson' => 15,
        ])->assertSessionHasErrors('end_lesson');

        $this->assertSame(0, Event::count());
    }

    public function test_agenda_can_use_custom_hours_until_six_am_the_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($user)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Agenda malam',
            'borrower_name' => 'Guru RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'start_lesson' => 'custom',
            'end_lesson' => 'custom',
            'start_custom_time' => '22:00',
            'end_custom_time' => '06:00',
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);
    }

    public function test_custom_hours_cannot_end_after_six_am_the_next_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($user)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Agenda malam',
            'borrower_name' => 'Guru RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'start_lesson' => 14,
            'end_lesson' => 'custom',
            'end_custom_time' => '06:01',
        ])->assertSessionHasErrors('end_custom_time');

        $this->assertSame(0, Event::count());
    }

    public function test_custom_hours_check_for_collisions_with_overnight_agendas(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);
        Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda sebelumnya',
            'date' => '2026-10-20',
            'start_time' => '23:00',
            'end_time' => '02:00',
        ]);

        $this->actingAs($user)->get(route('events.availability', [
            'room_id' => $room->id,
            'date' => '2026-10-20',
            'start_lesson' => 'custom',
            'end_lesson' => 'custom',
            'start_custom_time' => '22:00',
            'end_custom_time' => '03:00',
        ]))->assertJsonPath('events.0.title', 'Agenda sebelumnya')
            ->assertJsonPath('collision', true);

        $this->actingAs($user)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Agenda bertabrakan',
            'borrower_name' => 'Guru RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'start_lesson' => 'custom',
            'end_lesson' => 'custom',
            'start_custom_time' => '22:00',
            'end_custom_time' => '03:00',
        ])->assertSessionHasErrors('start_time');

        $this->assertSame(1, Event::count());
    }

    public function test_admin_can_create_a_room_booking_for_multiple_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $admin = $this->userWithRole('admin');
        $room = Room::create(['name' => 'Aula']);

        $this->actingAs($admin)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Kegiatan sekolah',
            'borrower_name' => 'Admin RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'start_lesson' => 4,
            'end_lesson' => 1,
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'date' => '2026-10-20 00:00:00',
            'end_date' => '2026-10-22 00:00:00',
            'status' => 'approved',
        ]);
    }

    public function test_non_admin_can_create_a_multi_day_booking_that_remains_pending(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Aula']);

        $this->actingAs($user)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Kegiatan sekolah',
            'borrower_name' => 'Guru RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'start_lesson' => 1,
            'end_lesson' => 2,
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'room_id' => $room->id,
            'date' => '2026-10-20 00:00:00',
            'end_date' => '2026-10-22 00:00:00',
            'status' => 'pending',
        ]);
    }

    public function test_multi_day_booking_rejects_an_existing_booking_on_an_intermediate_day(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $admin = $this->userWithRole('admin');
        $room = Room::create(['name' => 'Aula']);
        Event::create([
            'user_id' => $admin->id,
            'room_id' => $room->id,
            'title' => 'Peminjaman beberapa hari',
            'date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'start_time' => '09:30',
            'end_time' => '07:15',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)->post(route('events.store'), [
            'room_id' => $room->id,
            'title' => 'Agenda bentrok',
            'borrower_name' => 'Admin RAPI',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-21',
            'start_lesson' => 2,
            'end_lesson' => 4,
        ])->assertSessionHasErrors('start_time');

        $this->assertSame(1, Event::count());
    }

    public function test_availability_checks_the_full_admin_booking_range(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $admin = $this->userWithRole('admin');
        $room = Room::create(['name' => 'Aula']);
        Event::create([
            'user_id' => $admin->id,
            'room_id' => $room->id,
            'title' => 'Agenda sebelumnya',
            'date' => '2026-10-21',
            'start_time' => '09:30',
            'end_time' => '10:15',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)->get(route('events.availability', [
            'room_id' => $room->id,
            'date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'start_lesson' => 1,
            'end_lesson' => 2,
        ]))->assertJsonPath('collision', true);
    }

    private function userWithRole(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }
}
