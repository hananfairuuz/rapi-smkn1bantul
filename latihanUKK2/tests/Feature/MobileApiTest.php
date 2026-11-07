<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\ApiToken;
use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MobileApiTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_mobile_user_can_sign_in_read_dashboard_and_revoke_token(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Mobile',
            'email' => 'guru.mobile@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'whatsapp' => '628123456789',
        ]);
        Room::create(['name' => 'Lab Komputer']);

        $login = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Guru Mobile')
            ->assertJsonPath('user.role', 'guru');

        $token = $login->json('token');
        $this->withToken($token)->getJson('/api/user')
            ->assertOk()
            ->assertJsonPath('user.email', $user->email);
        $this->withToken($token)->getJson('/api/dashboard')
            ->assertOk()
            ->assertJsonPath('stats.total_rooms', 1);
        $this->withToken($token)->postJson('/api/logout')
            ->assertOk();
        $this->withToken($token)->getJson('/api/user')
            ->assertUnauthorized();
    }

    public function test_mobile_api_rejects_invalid_login_and_missing_bearer_token(): void
    {
        $this->postJson('/api/login', [
            'email' => 'missing@example.test',
            'password' => 'incorrect',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('email');

        $this->getJson('/api/dashboard')->assertUnauthorized();
    }

    public function test_calendar_is_available_before_mobile_login(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Kalender Publik',
            'email' => 'public.calendar@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Aula']);
        $organisasiRole = Role::create(['name' => 'organisasi']);
        $organisasi = User::create([
            'name' => 'Organisasi Kalender',
            'email' => 'organisasi.public.calendar@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $organisasiRole->id,
        ]);
        $event = Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda Terbuka',
            'date' => now()->format('Y-m-10'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'approved',
        ]);
        Event::create([
            'user_id' => $organisasi->id,
            'room_id' => $room->id,
            'title' => 'Agenda Organisasi',
            'date' => now()->format('Y-m-11'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'approved',
        ]);

        $this->getJson('/api/calendar?month='.now()->format('Y-m'))
            ->assertOk()
            ->assertJsonCount(2, 'events')
            ->assertJsonPath('events.0.id', $event->id)
            ->assertJsonPath('events.0.organizer_role', 'guru')
            ->assertJsonPath('events.1.organizer_role', 'organisasi');
    }

    public function test_admin_cannot_sign_in_or_use_a_previously_issued_mobile_token(): void
    {
        $role = Role::create(['name' => 'admin']);
        $admin = User::create([
            'name' => 'Admin Desktop',
            'email' => 'admin.mobile@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);

        $this->postJson('/api/login', [
            'email' => $admin->email,
            'password' => 'password123',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);

        $token = str_repeat('a', 80);
        ApiToken::create([
            'user_id' => $admin->id,
            'name' => 'legacy-mobile',
            'token' => hash('sha256', $token),
            'expires_at' => now()->addDay(),
        ]);

        $this->withToken($token)->getJson('/api/user')->assertUnauthorized();
        $this->assertDatabaseMissing('api_tokens', ['user_id' => $admin->id]);
    }

    public function test_mobile_password_reset_requires_a_verified_email_code(): void
    {
        Mail::fake();
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Reset',
            'email' => 'guru.reset@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $mobileToken = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');

        $this->postJson('/api/password/forgot', ['email' => $user->email])
            ->assertOk();
        Mail::assertSent(PasswordResetCodeMail::class);
        $mail = Mail::sent(PasswordResetCodeMail::class)->first();
        $this->assertNotNull($mail);
        $code = $mail->code;

        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertUnprocessable();

        $this->postJson('/api/password/verify-code', [
            'email' => $user->email,
            'code' => $code,
        ])->assertOk();
        $this->postJson('/api/password/reset', [
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])->assertOk();

        $this->assertTrue(Hash::check('newpassword123', $user->fresh()->password));
        $this->withToken($mobileToken)->getJson('/api/user')->assertUnauthorized();
    }

    public function test_mobile_profile_can_be_updated(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Lama',
            'email' => 'guru.profile@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)->putJson('/api/profile', [
            'name' => 'Guru Baru',
            'email' => 'guru.profile@example.test',
            'whatsapp' => '+628123456789',
        ])->assertOk()
            ->assertJsonPath('user.name', 'Guru Baru')
            ->assertJsonPath('user.whatsapp', '+628123456789');
    }

    public function test_pic_can_review_and_update_repairs_only_for_assigned_rooms(): void
    {
        $picRole = Role::create(['name' => 'pic']);
        $reporterRole = Role::create(['name' => 'guru']);
        $pic = User::create([
            'name' => 'PIC Ruang',
            'email' => 'pic.repair@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $picRole->id,
        ]);
        $reporter = User::create([
            'name' => 'Guru Pelapor',
            'email' => 'guru.reporter@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $reporterRole->id,
        ]);
        $room = Room::create(['name' => 'Lab', 'pic_id' => $pic->id]);
        $complaint = Complaint::create([
            'room_id' => $room->id,
            'reporter_id' => $reporter->id,
            'facility_name' => 'Proyektor',
            'description' => 'Tidak menyala.',
            'approval_status' => 'pending',
        ]);
        $token = $this->postJson('/api/login', [
            'email' => $pic->email,
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)->patchJson("/api/complaints/{$complaint->id}/review", [
            'approval_status' => 'approved',
        ])->assertOk()
            ->assertJsonPath('complaint.approval_status', 'approved');
        $this->withToken($token)->patchJson("/api/complaints/{$complaint->id}/repair", [
            'status_repair' => 'Sudah Diperbaiki',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('repair_notes');
        $this->withToken($token)->patchJson("/api/complaints/{$complaint->id}/repair", [
            'status_repair' => 'Sudah Diperbaiki',
            'repair_notes' => 'Selesai diperbaiki.',
        ])->assertOk()
            ->assertJsonPath('complaint.status_repair', 'Sudah Diperbaiki')
            ->assertJsonPath('complaint.repair_notes', 'Selesai diperbaiki.');

        Room::create(['name' => 'Ruangan bukan tugas PIC']);
        $this->withToken($token)->getJson('/api/rooms')
            ->assertOk()
            ->assertJsonCount(1, 'rooms')
            ->assertJsonPath('rooms.0.id', $room->id);
        $this->withToken($token)->getJson('/api/reports/damaged-goods?month='.now()->format('Y-m'))
            ->assertOk()
            ->assertJsonPath('total', 1)
            ->assertJsonPath('complaints.0.id', $complaint->id);

        $this->flushHeaders();
        $this->get('/api/reports/damaged-goods/docx?month='.now()->month.'&year='.now()->year)
            ->assertUnauthorized();
        $docx = $this->withToken($token)->get(
            '/api/reports/damaged-goods/docx?month='.now()->month.'&year='.now()->year
        );
        $docx->assertOk()
            ->assertHeader(
                'Content-Type',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            );
        $this->assertStringStartsWith('PK', $docx->streamedContent());
    }

    public function test_mobile_calendar_returns_only_approved_events(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Mobile',
            'email' => 'guru.calendar@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Lab Komputer']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');

        $approved = Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda Mobile',
            'date' => now()->format('Y-m-10'),
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'approved',
        ]);
        Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda Pending',
            'date' => now()->format('Y-m-11'),
            'start_time' => '10:00',
            'end_time' => '11:00',
            'status' => 'pending',
        ]);

        $this->withToken($token)->getJson('/api/calendar?month='.now()->format('Y-m'))
            ->assertOk()
            ->assertJsonCount(1, 'events')
            ->assertJsonPath('events.0.id', $approved->id);
    }

    public function test_mobile_pic_can_view_daily_and_weekly_damaged_goods_reports(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 12:00:00', 'Asia/Jakarta'));
        $picRole = Role::create(['name' => 'pic']);
        $reporterRole = Role::create(['name' => 'guru']);
        $pic = User::create([
            'name' => 'PIC Laporan',
            'email' => 'pic.report@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $picRole->id,
        ]);
        $reporter = User::create([
            'name' => 'Guru Pelapor',
            'email' => 'guru.period.report@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $reporterRole->id,
        ]);
        $room = Room::create(['name' => 'Lab Laporan', 'pic_id' => $pic->id]);
        foreach ([
            ['Pengaduan Senin', '2026-11-02 12:00:00'],
            ['Pengaduan Rabu', '2026-11-04 12:00:00'],
            ['Pengaduan Minggu', '2026-11-08 12:00:00'],
            ['Pengaduan Senin Depan', '2026-11-09 12:00:00'],
        ] as [$facility, $createdAt]) {
            $complaint = Complaint::create([
                'room_id' => $room->id,
                'reporter_id' => $reporter->id,
                'facility_name' => $facility,
                'description' => 'Rusak.',
            ]);
            $complaint->created_at = $createdAt;
            $complaint->save();
        }
        $token = $this->postJson('/api/login', [
            'email' => $pic->email,
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)->getJson('/api/reports/damaged-goods?period_type=week&date=2026-11-04')
            ->assertOk()
            ->assertJsonPath('period', '02/11/2026 – 08/11/2026')
            ->assertJsonCount(3, 'complaints');

        $this->withToken($token)->getJson('/api/reports/damaged-goods?period_type=day&date=2026-11-04')
            ->assertOk()
            ->assertJsonCount(1, 'complaints')
            ->assertJsonPath('complaints.0.facility_name', 'Pengaduan Rabu');
    }

    public function test_mobile_agenda_response_includes_automatic_execution_status(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-04 10:00:00', 'Asia/Jakarta'));
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Agenda',
            'email' => 'guru.agenda@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Lab Komputer']);
        Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda selesai',
            'date' => '2026-11-04',
            'start_time' => '08:00',
            'end_time' => '09:00',
            'status' => 'approved',
        ]);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');

        $this->withToken($token)->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('events.0.execution_status', 'Terlaksana');
    }

    public function test_mobile_user_can_request_an_event_and_room_collisions_are_rejected(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Mobile',
            'email' => 'guru.booking@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
            'whatsapp' => '628123456789',
        ]);
        $room = Room::create(['name' => 'Lab Komputer']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');
        $booking = [
            'room_id' => $room->id,
            'title' => 'Rapat Guru',
            'borrower_name' => $user->name,
            'borrower_whatsapp' => $user->whatsapp,
            'date' => now('Asia/Jakarta')->addDay()->format('Y-m-d'),
            'start_lesson' => '1',
            'end_lesson' => '2',
        ];

        $this->withToken($token)->postJson('/api/events', $booking)
            ->assertCreated()
            ->assertJsonPath('event.status', 'pending');
        $this->withToken($token)->postJson('/api/events', [
            ...$booking,
            'title' => 'Rapat Bentrok',
            'end_lesson' => '1',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors('start_lesson');
    }

    public function test_mobile_guru_can_request_multi_day_booking_pending_admin_approval(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-19 10:00:00', 'Asia/Jakarta'));
        $role = Role::create(['name' => 'organisasi']);
        $user = User::create([
            'name' => 'Organisasi Mobile',
            'email' => 'organisasi.multiday@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Aula']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');
        $booking = [
            'room_id' => $room->id,
            'title' => 'Kegiatan Organisasi',
            'borrower_name' => $user->name,
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-10-20',
            'end_date' => '2026-10-22',
            'start_lesson' => '1',
            'end_lesson' => '2',
        ];

        $response = $this->withToken($token)->postJson('/api/events', $booking)
            ->assertCreated()
            ->assertJsonPath('event.status', 'pending')
            ->assertJsonPath('event.date', '2026-10-20T00:00:00.000000Z')
            ->assertJsonPath('event.end_date', '2026-10-22T00:00:00.000000Z');
        $this->assertDatabaseHas('events', [
            'id' => $response->json('event.id'),
            'end_date' => '2026-10-22 00:00:00',
            'status' => 'pending',
        ]);
    }

    public function test_mobile_agenda_can_only_be_deleted_by_owner_when_rejected_or_three_days_old(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-10-25 10:00:00', 'Asia/Jakarta'));
        $role = Role::create(['name' => 'organisasi']);
        $user = User::create([
            'name' => 'Organisasi Mobile',
            'email' => 'organisasi.delete@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $otherUser = User::create([
            'name' => 'Organisasi Lain',
            'email' => 'organisasi.other@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Aula']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');
        $eligible = Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda lampau',
            'date' => '2026-10-22',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'approved',
        ]);
        $rejected = Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda ditolak',
            'date' => '2026-11-10',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'rejected',
        ]);
        $tooRecent = Event::create([
            'user_id' => $user->id,
            'room_id' => $room->id,
            'title' => 'Agenda baru',
            'date' => '2026-10-23',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'approved',
        ]);
        $otherOwnersEvent = Event::create([
            'user_id' => $otherUser->id,
            'room_id' => $room->id,
            'title' => 'Agenda milik organisasi lain',
            'date' => '2026-10-22',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'status' => 'approved',
        ]);

        $this->withToken($token)->getJson('/api/events')
            ->assertOk()
            ->assertJsonPath('events.0.can_delete', true);
        $this->withToken($token)->deleteJson("/api/events/{$eligible->id}")
            ->assertOk();
        $this->withToken($token)->deleteJson("/api/events/{$rejected->id}")
            ->assertOk();
        $this->withToken($token)->deleteJson("/api/events/{$tooRecent->id}")
            ->assertForbidden();
        $this->withToken($token)->deleteJson("/api/events/{$otherOwnersEvent->id}")
            ->assertForbidden();
        $this->assertDatabaseMissing('events', ['id' => $eligible->id]);
        $this->assertDatabaseMissing('events', ['id' => $rejected->id]);
        $this->assertDatabaseHas('events', ['id' => $tooRecent->id]);
        $this->assertDatabaseHas('events', ['id' => $otherOwnersEvent->id]);
    }

    public function test_mobile_user_can_submit_a_required_photo_with_a_complaint(): void
    {
        Storage::fake('public');
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Mobile',
            'email' => 'guru.complaint@example.test',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Lab Komputer']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');
        $fields = [
            'room_id' => $room->id,
            'facility_name' => 'Proyektor',
            'description' => 'Tidak menyala saat digunakan.',
        ];

        $this->withToken($token)->postJson('/api/complaints', $fields)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
        $response = $this->withToken($token)->post('/api/complaints', [
            ...$fields,
            'photo' => UploadedFile::fake()->image('proyektor.jpg'),
        ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('complaint.approval_status', 'pending')
            ->assertJsonPath('complaint.reporter_id', $user->id);
        $photoPath = $response->json('complaint.photo_path');
        Storage::disk('public')->assertExists($photoPath);
        $this->assertLessThanOrEqual(1_000_000, Storage::disk('public')->size($photoPath));
        $this->assertStringEndsWith('.jpg', $photoPath);

        $this->withToken($token)->post('/api/complaints', [
            ...$fields,
            'photo' => UploadedFile::fake()->image('terlalu-besar.jpg')->size(5121),
        ], ['Accept' => 'application/json'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('photo');
    }

    public function test_mobile_api_room_management_is_forbidden_for_non_admin(): void
    {
        $role = Role::create(['name' => 'guru']);
        $user = User::create([
            'name' => 'Guru Penjas',
            'email' => 'guru.penjas@skansaba.sch.id',
            'password' => Hash::make('password123'),
            'role_id' => $role->id,
        ]);
        $room = Room::create(['name' => 'Lab Komputer']);
        $token = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password123',
        ])->json('token');

        // Can view rooms
        $this->withToken($token)->getJson('/api/rooms')->assertOk();

        // Cannot create, update, delete
        $this->withToken($token)->postJson('/api/rooms', ['name' => 'Ruang Baru'])->assertForbidden();
        $this->withToken($token)->putJson("/api/rooms/{$room->id}", ['name' => 'Ruang Ganti'])->assertForbidden();
        $this->withToken($token)->deleteJson("/api/rooms/{$room->id}")->assertForbidden();
    }
}
