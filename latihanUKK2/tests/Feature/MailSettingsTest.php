<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_settings_page_is_no_longer_available(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get('/settings/mail')->assertNotFound();
        $this->actingAs($admin)->get(route('users.index'))
            ->assertOk()
            ->assertDontSee('Pengaturan Email');
    }

    public function test_add_user_form_has_account_and_individual_login_password_fields(): void
    {
        $admin = $this->userWithRole('admin');
        $this->actingAs($admin)->get(route('users.create'))
            ->assertOk()
            ->assertSee('name="name"', false)
            ->assertSee('name="email"', false)
            ->assertSee('name="password"', false)
            ->assertSee('name="password_confirmation"', false)
            ->assertSeeText('bukan kata sandi akun Google')
            ->assertSee('name="whatsapp"', false)
            ->assertSee('name="role_id"', false)
            ->assertDontSee('name="host"', false)
            ->assertDontSee('name="username"', false);
    }

    public function test_password_reset_sends_code_using_laravel_mail_configuration(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.env-example.test',
            'mail.mailers.smtp.username' => 'env-sender@example.test',
            'mail.mailers.smtp.password' => 'env-test-password',
            'mail.from.address' => 'env-sender@example.test',
        ]);
        config(['mail.mailers.smtp.transport' => 'array']);
        DB::table('mail_settings')->insert([
            'host' => 'smtp.database-example.test',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'database-sender@example.test',
            'password' => 'unused-database-password',
            'from_address' => 'database-sender@example.test',
            'from_name' => 'Database Setting',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $user = $this->userWithRole('guru');

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset', ['email' => $user->email]));

        Mail::assertSent(PasswordResetCodeMail::class);
        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.env-example.test', config('mail.mailers.smtp.host'));
        $this->assertSame('env-sender@example.test', config('mail.from.address'));
    }

    public function test_user_added_by_admin_receives_password_reset_code_at_their_account_email(): void
    {
        Mail::fake();
        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => 'smtp.env-example.test',
            'mail.mailers.smtp.username' => 'env-sender@example.test',
            'mail.mailers.smtp.password' => 'env-test-password',
            'mail.from.address' => 'env-sender@example.test',
            'mail.from.name' => 'RAPI Test',
            'mail.mailers.smtp.transport' => 'array',
        ]);

        $admin = $this->userWithRole('admin');
        $roleId = Role::firstOrCreate(['name' => 'guru'])->id;
        $recipient = 'pengguna.baru@example.test';

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Pengguna Baru',
            'email' => $recipient,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
            'whatsapp' => '628123456789',
            'role_id' => $roleId,
        ])->assertRedirect(route('users.index'));

        $user = User::where('email', $recipient)->firstOrFail();
        auth()->logout();

        $this->post(route('password.email'), ['email' => $recipient])
            ->assertRedirect(route('password.reset', ['email' => $recipient]));

        Mail::assertSent(PasswordResetCodeMail::class, fn (PasswordResetCodeMail $mail) => $mail->hasTo($user->email));
        $this->assertSame('env-sender@example.test', config('mail.from.address'));
        $this->assertSame('RAPI Test', config('mail.from.name'));
    }

    private function userWithRole(string $roleName): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(['role_id' => $role->id]);
    }
}
