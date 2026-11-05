<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use App\Services\IndonesiaHolidayCalendar;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventBookingWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_room_booking_must_be_requested_at_least_one_day_in_advance(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($user)->post(route('events.store'), $this->bookingData($room, '2026-10-19'))
            ->assertSessionHasErrors('date');
        $this->assertDatabaseCount('events', 0);

        $this->actingAs($user)->post(route('events.store'), $this->bookingData($room, '2026-10-20'))
            ->assertRedirect(route('events.index'));
        $this->assertDatabaseCount('events', 1);
    }

    public function test_teacher_can_request_multi_day_booking_pending_admin_approval(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $guru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($guru)->get(route('events.create'))
            ->assertOk()
            ->assertSee('Tanggal selesai')
            ->assertSee('Pengajuan peminjaman lebih dari satu hari tetap menunggu persetujuan admin.');

        $this->actingAs($guru)->post(route('events.store'), [
            ...$this->bookingData($room, '2026-10-20'),
            'end_date' => '2026-10-22',
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'user_id' => $guru->id,
            'room_id' => $room->id,
            'date' => '2026-10-20 00:00:00',
            'end_date' => '2026-10-22 00:00:00',
            'status' => 'pending',
        ]);
    }

    public function test_multi_day_booking_is_rejected_when_any_day_overlaps_an_existing_agenda(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $guru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);
        Event::create([
            'user_id' => $guru->id,
            'room_id' => $room->id,
            'title' => 'Agenda di tengah rentang',
            'date' => '2026-10-21',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'approved',
        ]);

        $this->actingAs($guru)->post(route('events.store'), [
            ...$this->bookingData($room, '2026-10-20'),
            'end_date' => '2026-10-22',
        ])->assertSessionHasErrors('start_time');
        $this->assertDatabaseCount('events', 1);
    }

    public function test_agenda_form_asks_for_confirmation_before_saving(): void
    {
        $user = $this->userWithRole('guru');

        $this->actingAs($user)->get(route('events.create'))
            ->assertOk()
            ->assertSee('Yakin ingin menyimpan pengajuan agenda ini?');
    }

    public function test_calendar_uses_colors_for_agenda_organizer_roles(): void
    {
        $this->app->instance(
            IndonesiaHolidayCalendar::class,
            \Mockery::mock(IndonesiaHolidayCalendar::class)
                ->shouldReceive('holidaysForYear')
                ->andReturn([])
                ->getMock()
        );
        $admin = $this->userWithRole('admin');
        $guru = $this->userWithRole('guru');
        $organisasi = $this->userWithRole('organisasi');
        $room = Room::create(['name' => 'Aula']);
        foreach ([$admin, $guru, $organisasi] as $user) {
            Event::create(array_merge($this->bookingData($room, now()->format('Y-m-10')), [
                'user_id' => $user->id,
                'title' => 'Agenda '.$user->role->name,
                'status' => 'approved',
            ]));
        }

        $this->actingAs($guru)->get(route('calendar'))
            ->assertOk()
            ->assertSee('calendar-event--guru')
            ->assertSee('calendar-event--organisasi')
            ->assertSee('calendar-event--admin');
    }

    public function test_newly_submitted_agendas_appear_before_older_agendas(): void
    {
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00', 'Asia/Jakarta'));
        Event::create(array_merge($this->bookingData($room, '2026-10-24'), [
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '10:15',
            'title' => 'Agenda lama',
        ]));

        Carbon::setTestNow(Carbon::parse('2026-10-21 10:00:00', 'Asia/Jakarta'));
        Event::create(array_merge($this->bookingData($room, '2026-10-22'), [
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '10:15',
            'title' => 'Agenda baru',
        ]));

        $this->actingAs($user)->get(route('events.index'))
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->pluck('title')->all() === ['Agenda baru', 'Agenda lama']);
    }

    public function test_teacher_can_filter_and_search_agendas_by_schedule_and_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $guru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        $createAgenda = function (string $title, string $date, string $status, string $start, string $end) use ($guru, $room) {
            return Event::create(array_merge($this->bookingData($room, $date), [
                'user_id' => $guru->id,
                'title' => $title,
                'status' => $status,
                'start_time' => $start,
                'end_time' => $end,
            ]));
        };
        $today = $createAgenda('Agenda hari ini', '2026-11-04', 'approved', '08:00', '11:00');
        $completed = $createAgenda('Agenda sudah terlaksana', '2026-11-03', 'approved', '08:00', '09:00');
        $upcoming = $createAgenda('Agenda mendatang', '2026-11-07', 'approved', '18:00', '20:00');
        $tooFar = $createAgenda('Agenda terlalu jauh', '2026-11-08', 'approved', '08:00', '10:00');
        $createAgenda('Agenda ditolak', '2026-11-05', 'rejected', '08:00', '10:00');
        $createAgenda('Agenda menunggu', '2026-11-05', 'pending', '08:00', '10:00');

        $this->actingAs($guru)->get(route('events.index', ['filter' => 'upcoming', 'search' => 'mendatang']))
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->pluck('id')->all() === [$upcoming->id])
            ->assertSee('Pelaksanaan:')
            ->assertSee('Belum terlaksana');

        $this->actingAs($guru)->get(route('events.index', ['filter' => 'current']))
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->pluck('id')->all() === [$tooFar->id, $upcoming->id, $completed->id, $today->id])
            ->assertSee('Terlaksana');

        $this->actingAs($guru)->get(route('events.index', ['filter' => 'today']))
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->pluck('id')->all() === [$today->id]);

        $this->actingAs($guru)->get(route('events.index', ['filter' => 'rejected']))
            ->assertOk()
            ->assertSee('Agenda ditolak')
            ->assertDontSee('Agenda menunggu');

        $this->actingAs($guru)->get(route('events.index', ['filter' => 'pending']))
            ->assertOk()
            ->assertViewHas('events', fn ($events) => $events->pluck('title')->all() === ['Agenda menunggu']);
    }

    public function test_guru_can_delete_rejected_or_three_day_old_agendas_only(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-25 10:00:00', 'Asia/Jakarta'));
        $guru = $this->userWithRole('guru');
        $otherGuru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);
        $eligible = Event::create(array_merge($this->bookingData($room, '2026-10-22'), [
            'user_id' => $guru->id,
            'status' => 'approved',
        ]));
        $rejected = Event::create(array_merge($this->bookingData($room, '2026-11-10'), [
            'user_id' => $guru->id,
            'status' => 'rejected',
        ]));
        $tooRecent = Event::create(array_merge($this->bookingData($room, '2026-10-23'), [
            'user_id' => $guru->id,
            'status' => 'approved',
        ]));
        $otherOwnersEvent = Event::create(array_merge($this->bookingData($room, '2026-10-22'), [
            'user_id' => $otherGuru->id,
            'status' => 'approved',
        ]));

        $this->actingAs($guru)->get(route('events.index'))
            ->assertOk()
            ->assertSee('Hapus');

        $this->actingAs($guru)->delete(route('events.destroy', $eligible))
            ->assertRedirect(route('events.index'));
        $this->assertDatabaseMissing('events', ['id' => $eligible->id]);

        $this->actingAs($guru)->delete(route('events.destroy', $rejected))
            ->assertRedirect(route('events.index'));
        $this->assertDatabaseMissing('events', ['id' => $rejected->id]);

        $this->actingAs($guru)->delete(route('events.destroy', $tooRecent))
            ->assertForbidden();
        $this->actingAs($guru)->delete(route('events.destroy', $otherOwnersEvent))
            ->assertForbidden();
        $this->assertDatabaseHas('events', ['id' => $tooRecent->id]);
        $this->assertDatabaseHas('events', ['id' => $otherOwnersEvent->id]);
    }

    public function test_newly_submitted_complaints_appear_before_older_complaints(): void
    {
        $user = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);

        Carbon::setTestNow(Carbon::parse('2026-10-20 10:00:00', 'Asia/Jakarta'));
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $user->id,
            'facility_name' => 'Barang lama',
            'description' => 'Deskripsi barang lama.',
        ]);

        Carbon::setTestNow(Carbon::parse('2026-10-21 10:00:00', 'Asia/Jakarta'));
        Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $user->id,
            'facility_name' => 'Barang baru',
            'description' => 'Deskripsi barang baru.',
        ]);

        $this->actingAs($user)->get(route('complaints.index'))
            ->assertOk()
            ->assertViewHas('complaints', fn ($complaints) => $complaints->pluck('facility_name')->all() === ['Barang baru', 'Barang lama']);
    }

    public function test_admin_decides_booking_status_from_the_agenda_list(): void
    {
        $admin = $this->userWithRole('admin');
        $room = Room::create(['name' => 'Lab Komputer']);
        $otherRoom = Room::create(['name' => 'Aula']);
        $borrower = $this->userWithRole('guru');
        $event = $this->createEvent($borrower, $room);
        $data = array_merge($this->bookingData($room, '2026-10-20'), [
            'status' => 'rejected',
            'room_id' => $room->id,
        ]);

        $this->actingAs($admin)->get(route('events.edit', $event))
            ->assertOk()
            ->assertDontSee('name="status"', false)
            ->assertDontSee('name="rejection_reason"', false)
            ->assertDontSee('Catat Barang Rusak');

        $data['room_id'] = $otherRoom->id;
        $this->actingAs($admin)->put(route('events.update', $event), $data)
            ->assertSessionHasErrors('move_reason');
        $this->assertDatabaseHas('events', ['id' => $event->id, 'status' => 'pending', 'room_id' => $room->id]);

        $data['room_id'] = $room->id;
        $this->actingAs($admin)->get(route('events.index'))
            ->assertOk()
            ->assertSee('Setujui')
            ->assertSee('Tolak');

        $this->actingAs($admin)->patch(route('events.status.update', $event), [
            'status' => 'rejected',
        ])->assertSessionHasErrors('rejection_reason');

        $this->actingAs($admin)->patch(route('events.status.update', $event), [
            'status' => 'rejected',
            'rejection_reason' => 'Ruangan sedang dalam perbaikan.',
        ])->assertRedirect(route('events.index'));
        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'status' => 'rejected',
            'rejection_reason' => 'Ruangan sedang dalam perbaikan.',
        ]);

        $this->actingAs($borrower)->get(route('events.index'))
            ->assertSee('Ruangan sedang dalam perbaikan.');
        $this->actingAs($admin)->get(route('events.edit', $event))->assertForbidden();
        $this->actingAs($admin)->put(route('events.update', $event), $data)->assertForbidden();
    }

    public function test_admin_can_record_a_damaged_item_for_the_booked_room(): void
    {
        $admin = $this->userWithRole('admin');
        $pic = $this->userWithRole('pic');
        $borrower = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer', 'pic_id' => $pic->id]);
        $event = $this->createEvent($borrower, $room);

        $this->actingAs($admin)->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('+ Buat Pengaduan');
        $this->actingAs($admin)->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Agenda terkait')
            ->assertSee('Praktik');

        $this->actingAs($admin)->get(route('complaints.create', ['event_id' => $event->id]))
            ->assertOk()
            ->assertSee('Lab Komputer')
            ->assertSee('Catat Barang Rusak');

        $this->actingAs($admin)->post(route('complaints.store'), [
            'event_id' => $event->id,
            'room_id' => $room->id,
            'facility_name' => 'Proyektor',
            'description' => 'Proyektor tidak dapat menyala.',
        ])->assertRedirect(route('complaints.index'));

        $this->assertDatabaseHas('complaints', [
            'event_id' => $event->id,
            'room_id' => $room->id,
            'reporter_id' => $admin->id,
            'facility_name' => 'Proyektor',
            'approval_status' => 'pending',
        ]);

        $complaint = Complaint::firstOrFail();
        $this->actingAs($pic)->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('Setujui Pengaduan');
        $this->actingAs($pic)->patch(route('complaints.review', $complaint), [
            'approval_status' => 'approved',
        ])->assertRedirect(route('complaints.index'));
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'approval_status' => 'approved',
        ]);
    }

    public function test_borrower_can_report_damage_and_pic_must_approve_before_repair_updates(): void
    {
        Storage::fake('public');
        $borrower = $this->userWithRole('guru');
        $pic = $this->userWithRole('pic');
        $room = Room::create(['name' => 'Lab Komputer', 'pic_id' => $pic->id]);
        $event = $this->createEvent($borrower, $room);
        $event->update(['status' => 'approved']);
        $otherBorrower = $this->userWithRole('guru');

        $this->actingAs($borrower)->get(route('events.index'))
            ->assertOk()
            ->assertSee('Catat Barang Rusak');
        $this->actingAs($borrower)->get(route('complaints.create', ['event_id' => $event->id]))
            ->assertOk()
            ->assertSee('Agenda:');
        $this->actingAs($otherBorrower)->get(route('complaints.create', ['event_id' => $event->id]))
            ->assertForbidden();
        $this->actingAs($borrower)->post(route('complaints.store'), [
            'event_id' => $event->id,
            'room_id' => $room->id,
            'facility_name' => 'Proyektor',
            'description' => 'Tidak dapat menyala.',
            'photo' => UploadedFile::fake()->image('bukti.jpg'),
        ])->assertRedirect(route('complaints.index'));

        $complaint = Complaint::firstOrFail();
        Storage::disk('public')->assertExists($complaint->photo_path);
        $this->assertLessThanOrEqual(1_000_000, Storage::disk('public')->size($complaint->photo_path));
        $this->assertStringEndsWith('.jpg', $complaint->photo_path);
        $this->assertSame('pending', $complaint->approval_status);

        $this->actingAs($pic)->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('Setujui Pengaduan')
            ->assertSee('Tolak Pengaduan');
        $this->actingAs($pic)->get(route('complaints.edit', $complaint))->assertForbidden();
        $this->actingAs($pic)->put(route('complaints.update', $complaint), [
            'status_repair' => 'Sudah Diperbaiki',
        ])->assertForbidden();

        $this->actingAs($pic)->patch(route('complaints.review', $complaint), [
            'approval_status' => 'approved',
        ])->assertRedirect(route('complaints.index'));
        $this->assertDatabaseHas('complaints', ['id' => $complaint->id, 'approval_status' => 'approved']);

        $this->actingAs($pic)->get(route('complaints.edit', $complaint))
            ->assertOk()
            ->assertSee('Catatan PIC (opsional)')
            ->assertSee('Keterangan Perbaikan (wajib)');

        $this->actingAs($pic)->put(route('complaints.update', $complaint), [
            'status_repair' => 'Sudah Diperbaiki',
        ])->assertSessionHasErrors('repair_notes');

        $this->actingAs($pic)->put(route('complaints.update', $complaint), [
            'status_repair' => 'Sudah Diperbaiki',
            'repair_notes' => 'Lampu proyektor diganti.',
        ])->assertRedirect(route('complaints.index'));
        $this->assertDatabaseHas('complaints', [
            'id' => $complaint->id,
            'status_repair' => 'Sudah Diperbaiki',
            'repair_notes' => 'Lampu proyektor diganti.',
        ]);
        $this->actingAs($pic)->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('Keterangan perbaikan')
            ->assertSee('Lampu proyektor diganti.');

        $rejectedComplaint = Complaint::create([
            'room_id' => $room->id,
            'event_id' => $event->id,
            'reporter_id' => $borrower->id,
            'facility_name' => 'Speaker',
            'description' => 'Tidak ada suara.',
            'approval_status' => 'pending',
        ]);
        $this->actingAs($pic)->patch(route('complaints.review', $rejectedComplaint), [
            'approval_status' => 'rejected',
        ])->assertRedirect(route('complaints.index'));
        $this->assertDatabaseHas('complaints', ['id' => $rejectedComplaint->id, 'approval_status' => 'rejected']);
        $this->actingAs($pic)->get(route('complaints.edit', $rejectedComplaint))->assertForbidden();
    }

    public function test_admin_created_agenda_is_automatically_approved(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $admin = $this->userWithRole('admin');
        $room = Room::create(['name' => 'Lab Komputer']);

        $this->actingAs($admin)->post(route('events.store'), $this->bookingData($room, '2026-10-19'))
            ->assertRedirect(route('events.index'))
            ->assertSessionHas('success', 'Agenda berhasil dibuat dan langsung disetujui.');

        $this->assertDatabaseHas('events', [
            'user_id' => $admin->id,
            'room_id' => $room->id,
            'status' => 'approved',
        ]);
    }

    public function test_admin_can_manage_an_agenda_with_custom_overnight_hours(): void
    {
        $admin = $this->userWithRole('admin');
        $borrower = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);
        $event = $this->createEvent($borrower, $room);

        $this->actingAs($admin)->put(route('events.update', $event), [
            'room_id' => $room->id,
            'title' => 'Agenda malam',
            'borrower_name' => 'Peminjam',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'start_lesson' => 'custom',
            'end_lesson' => 'custom',
            'start_custom_time' => '22:00',
            'end_custom_time' => '06:00',
        ])->assertRedirect(route('events.index'));

        $this->actingAs($admin)->patch(route('events.status.update', $event), [
            'status' => 'approved',
        ])->assertRedirect(route('events.index'));

        $this->assertDatabaseHas('events', [
            'id' => $event->id,
            'start_time' => '22:00',
            'end_time' => '06:00',
            'status' => 'approved',
        ]);

        $this->actingAs($admin)->get(route('events.index'))
            ->assertOk()
            ->assertSee('(+1 hari)');
    }

    public function test_dashboard_counts_an_overnight_agenda_as_ongoing_next_morning(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-21 02:00:00', 'Asia/Jakarta'));
        $admin = $this->userWithRole('admin');
        $room = Room::create(['name' => 'Lab Komputer']);
        Event::create([
            'user_id' => $admin->id,
            'room_id' => $room->id,
            'title' => 'Agenda malam',
            'date' => '2026-10-20',
            'start_time' => '22:00',
            'end_time' => '06:00',
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('dashboard-watermark', false)
            ->assertSee('1 sedang berlangsung');
    }

    private function bookingData(Room $room, string $date): array
    {
        return [
            'room_id' => $room->id,
            'title' => 'Praktik',
            'borrower_name' => 'Peminjam',
            'borrower_whatsapp' => '628123456789',
            'date' => $date,
            'start_time' => '08:00',
            'end_time' => '10:15',
            'start_lesson' => 2,
            'end_lesson' => 4,
        ];
    }

    private function createEvent(User $user, Room $room): Event
    {
        return Event::create(array_merge($this->bookingData($room, '2026-10-20'), [
            'user_id' => $user->id,
            'start_time' => '08:00',
            'end_time' => '10:15',
            'status' => 'pending',
        ]));
    }

    private function userWithRole(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id]);
    }
}
