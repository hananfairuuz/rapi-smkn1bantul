<?php

namespace Tests\Feature;

use App\Models\Complaint;
use App\Models\Event;
use App\Models\Role;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_quick_actions_offer_useful_shortcuts_for_each_role(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola agenda')
            ->assertSee('Pantau pengaduan')
            ->assertSee('Kelola pengguna')
            ->assertSee('Laporan jadwal ruangan');

        $pic = $this->userWithRole('pic');
        $this->actingAs($pic)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kelola pengaduan')
            ->assertSee('Lihat ruangan')
            ->assertSee('Laporan barang rusak');

        foreach (['guru', 'organisasi'] as $role) {
            $user = $this->userWithRole($role);
            $this->actingAs($user)->get(route('dashboard'))
                ->assertOk()
                ->assertSee('Pantau agenda saya')
                ->assertSee('Lihat pengaduan')
                ->assertSee('Laporkan kerusakan');
        }
    }

    public function test_admin_can_open_complaint_submission_from_facility_complaints(): void
    {
        $admin = $this->userWithRole('admin');

        $this->actingAs($admin)->get(route('complaints.index'))
            ->assertOk()
            ->assertSee('+ Buat Pengaduan');
        $this->actingAs($admin)->get(route('complaints.create'))
            ->assertOk()
            ->assertSee('Agenda terkait')
            ->assertSee('Catat Barang Rusak');
    }

    public function test_only_guru_and_organisasi_can_submit_complaints(): void
    {
        $guru = $this->userWithRole('guru');
        $organisasi = $this->userWithRole('organisasi');
        $pic = $this->userWithRole('pic');

        $this->actingAs($guru)->get(route('complaints.create'))->assertOk();
        $this->actingAs($organisasi)->get(route('complaints.create'))->assertOk();
        $this->actingAs($pic)->get(route('complaints.create'))->assertForbidden();
    }

    public function test_only_admin_can_manage_rooms_while_pic_guru_organisasi_cannot(): void
    {
        $admin = $this->userWithRole('admin');
        $pic = $this->userWithRole('pic');
        $guru = $this->userWithRole('guru');
        $organisasi = $this->userWithRole('organisasi');

        $room = Room::create(['name' => 'Lab Komputer']);

        // PIC cannot access agenda management
        $this->actingAs($pic)->get(route('events.index'))->assertForbidden();

        // Admin can access create and edit rooms
        $this->actingAs($admin)->get(route('rooms.create'))->assertOk();
        $this->actingAs($admin)->get(route('rooms.edit', $room))->assertOk();

        // PIC, Guru, Organisasi are forbidden from adding, editing, and deleting rooms
        foreach ([$pic, $guru, $organisasi] as $user) {
            $this->actingAs($user)->get(route('rooms.create'))->assertForbidden();
            $this->actingAs($user)->post(route('rooms.store'), ['name' => 'Ruang Baru'])->assertForbidden();
            $this->actingAs($user)->get(route('rooms.edit', $room))->assertForbidden();
            $this->actingAs($user)->put(route('rooms.update', $room), ['name' => 'Update'])->assertForbidden();
            $this->actingAs($user)->delete(route('rooms.destroy', $room))->assertForbidden();
        }

        // PIC, Guru, Organisasi can access rooms.index but cannot see manage buttons
        foreach ([$pic, $guru, $organisasi] as $user) {
            $this->actingAs($user)->get(route('rooms.index'))
                ->assertOk()
                ->assertDontSee('+ Tambah Ruangan')
                ->assertDontSee('Hapus');
        }

        // Admin sees manage buttons on rooms.index
        $this->actingAs($admin)->get(route('rooms.index'))
            ->assertOk()
            ->assertSee('+ Tambah Ruangan')
            ->assertSee('Edit')
            ->assertSee('Hapus');
    }

    public function test_guru_only_sees_its_own_agendas(): void
    {
        $guru = $this->userWithRole('guru');
        $otherGuru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Bahasa']);
        Event::create(['user_id' => $guru->id, 'room_id' => $room->id, 'title' => 'Agenda Saya', 'date' => '2026-08-20', 'start_time' => '08:00', 'end_time' => '09:00']);
        Event::create(['user_id' => $otherGuru->id, 'room_id' => $room->id, 'title' => 'Agenda Orang Lain', 'date' => '2026-08-21', 'start_time' => '08:00', 'end_time' => '09:00']);

        $this->actingAs($guru)->get(route('events.index'))->assertOk()->assertSee('Agenda Saya')->assertDontSee('Agenda Orang Lain');
    }

    public function test_admin_can_create_users_and_user_management_remains_admin_only(): void
    {
        foreach (['admin', 'pic', 'guru', 'organisasi'] as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
        $admin = $this->userWithRole('admin');
        $guru = $this->userWithRole('guru');
        $this->get('/register')->assertNotFound();
        $this->get('/verify-account')->assertNotFound();

        $this->actingAs($guru)->get(route('users.index'))->assertForbidden();
        $this->actingAs($guru)->get(route('users.create'))->assertForbidden();
        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertSee('+ Tambah Pengguna')
            ->assertSeeText('Pengguna & Peran');

        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSee('name="whatsapp"', false)
            ->assertSee('>Waka / Admin</option>', false)
            ->assertSee('>PIC Ruang</option>', false)
            ->assertSee('>Guru</option>', false)
            ->assertSee('>Organisasi</option>', false);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Guru Baru',
            'email' => 'guru.baru@example.test',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
            'whatsapp' => '628123456789',
            'role_id' => Role::where('name', 'guru')->value('id'),
        ])->assertRedirect(route('users.index'))->assertSessionHas('success');

        $createdUser = User::where('email', 'guru.baru@example.test')->firstOrFail();
        $this->assertSame('Guru Baru', $createdUser->name);
        $this->assertSame(Role::where('name', 'guru')->value('id'), $createdUser->role_id);
        $this->assertNull($createdUser->google_id);
        $this->assertTrue(Hash::check('password-baru-123', $createdUser->password));
        $this->post(route('login.store'), [
            'email' => 'guru.baru@example.test',
            'password' => 'password-baru-123',
        ])->assertRedirect(route('dashboard'));
    }

    public function test_user_creation_requires_a_valid_whatsapp_number_for_every_role(): void
    {
        $admin = $this->userWithRole('admin');
        $adminRoleId = Role::where('name', 'admin')->value('id');

        $this->actingAs($admin)->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Admin Baru',
            'email' => 'admin.baru@example.test',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
            'role_id' => $adminRoleId,
        ])->assertRedirect(route('users.create'))->assertSessionHasErrors('whatsapp');

        $this->assertDatabaseMissing('users', ['email' => 'admin.baru@example.test']);

        $organisasiRoleId = Role::firstOrCreate(['name' => 'organisasi'])->id;
        $this->actingAs($admin)->from(route('users.create'))->post(route('users.store'), [
            'name' => 'Organisasi Baru',
            'email' => 'organisasi.baru@example.test',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
            'role_id' => $organisasiRoleId,
        ])->assertRedirect(route('users.create'))->assertSessionHasErrors('whatsapp');

        $this->assertDatabaseMissing('users', ['email' => 'organisasi.baru@example.test']);
    }

    public function test_user_creation_requires_a_confirmed_individual_login_password(): void
    {
        $admin = $this->userWithRole('admin');
        $roleId = Role::firstOrCreate(['name' => 'guru'])->id;
        $userData = [
            'name' => 'Guru Baru',
            'email' => 'guru.password@example.test',
            'whatsapp' => '628123456789',
            'role_id' => $roleId,
        ];

        $this->actingAs($admin)->from(route('users.create'))->post(route('users.store'), $userData)
            ->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'guru.password@example.test']);

        $this->actingAs($admin)->from(route('users.create'))->post(route('users.store'), [
            ...$userData,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-lain-123',
        ])->assertRedirect(route('users.create'))
            ->assertSessionHasErrors('password');
        $this->assertDatabaseMissing('users', ['email' => 'guru.password@example.test']);
    }

    public function test_google_oauth_authenticates_a_registered_verified_email(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
        $user = $this->userWithRole('guru');
        $user->update(['email' => 'guru.oauth@example.test']);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-sub-123',
                'email' => 'GURU.OAUTH@example.test',
                'email_verified' => true,
            ], 200),
        ]);

        $state = $this->beginGoogleLogin();
        $this->get(route('google.callback', ['state' => $state, 'code' => 'authorization-code']))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('google-sub-123', $user->fresh()->google_id);
        Http::assertSent(fn (Request $request) => $request->url() === 'https://openidconnect.googleapis.com/v1/userinfo'
            && $request->hasHeader('Authorization', 'Bearer google-access-token'));
    }

    public function test_google_oauth_rejects_unregistered_or_unverified_accounts(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'http://localhost/auth/google/callback',
        ]);
        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-sub-456',
                'email' => 'not.registered@example.test',
                'email_verified' => true,
            ], 200),
        ]);

        $state = $this->beginGoogleLogin();
        $this->get(route('google.callback', ['state' => $state, 'code' => 'authorization-code']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertGuest();

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'google-access-token'], 200),
            'https://openidconnect.googleapis.com/v1/userinfo' => Http::response([
                'sub' => 'google-sub-456',
                'email' => 'not.registered@example.test',
                'email_verified' => false,
            ], 200),
        ]);
        $state = $this->beginGoogleLogin();
        $this->get(route('google.callback', ['state' => $state, 'code' => 'authorization-code']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        $this->assertGuest();
    }

    public function test_google_oauth_rejects_invalid_state_without_calling_google(): void
    {
        Http::preventStrayRequests();

        $this->get(route('google.callback', ['state' => 'invalid', 'code' => 'authorization-code']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
        Http::assertNothingSent();
    }

    public function test_google_login_reports_missing_oauth_configuration(): void
    {
        config([
            'services.google.client_id' => null,
            'services.google.client_secret' => null,
        ]);

        $this->get(route('google.login'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('google');
    }

    public function test_admin_can_delete_an_unreferenced_user(): void
    {
        $admin = $this->userWithRole('admin');
        $user = $this->userWithRole('guru');

        $this->actingAs($admin)->delete(route('users.destroy', $user))
            ->assertRedirect(route('users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_admin_cannot_delete_a_user_with_agendas(): void
    {
        $admin = $this->userWithRole('admin');
        $guru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab Komputer']);
        Event::create([
            'user_id' => $guru->id,
            'room_id' => $room->id,
            'title' => 'Agenda Guru',
            'borrower_name' => 'Peminjam',
            'borrower_whatsapp' => '628123456789',
            'date' => '2026-08-20',
            'start_time' => '08:00',
            'end_time' => '09:00',
        ]);

        $this->actingAs($admin)->delete(route('users.destroy', $guru))
            ->assertRedirect()
            ->assertSessionHasErrors('user');

        $this->assertDatabaseHas('users', ['id' => $guru->id]);
    }

    public function test_pic_update_form_shows_complaint_details_without_editing_the_report(): void
    {
        $pic = $this->userWithRole('pic');
        $guru = $this->userWithRole('guru');
        $room = Room::create(['name' => 'Lab IPA', 'pic_id' => $pic->id]);
        $complaint = Complaint::create(['room_id' => $room->id, 'reporter_id' => $guru->id, 'facility_name' => 'Proyektor', 'description' => 'Tidak menyala']);

        $this->actingAs($pic)->get(route('complaints.edit', $complaint))->assertOk()->assertSee('Perbarui Status Pengaduan')->assertSee('Proyektor');
    }

    public function test_login_page_has_public_calendar_return_link(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Kembali ke kalender utama')
            ->assertSee(route('calendar'))
            ->assertDontSee('Masuk dengan Google')
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="remember"', false)
            ->assertSee('Lupa kata sandi?')
            ->assertSee('Selamat datang')
            ->assertSee('Masuk')
            ->assertSee('Gunakan akun yang telah dibuat oleh administrator.')
            ->assertSee('class="login-layout"', false)
            ->assertSee('images/school-logo.png')
            ->assertSee(route('login.store'))
            ->assertSee(route('password.request'));
    }

    public function test_login_remembers_user_when_remember_checkbox_is_selected(): void
    {
        $user = $this->userWithRole('guru');
        $user->forceFill(['remember_token' => null])->save();

        $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->remember_token);
    }

    private function beginGoogleLogin(): string
    {
        $response = $this->get(route('google.login'));
        $response->assertRedirect();
        parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
        $this->assertSame('openid email profile', $query['scope'] ?? null);
        $this->assertIsString($query['state'] ?? null);
        $this->assertSame($query['state'], session('google_oauth_state'));

        return $query['state'];
    }

    private function userWithRole(string $role): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id, 'whatsapp' => '628123456789']);
    }
}
