<?php

namespace Tests\Feature;

use App\Mail\PasswordResetCodeMail;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class PasswordResetWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_registered_email_receives_a_reset_code_and_can_reset_password(): void
    {
        Mail::fake();
        $user = $this->userWithRole('guru', 'guru@example.test');
        config(['mail.mailers.smtp.transport' => 'array']);

        $this->post(route('password.email'), ['email' => 'GURU@example.test'])
            ->assertRedirect(route('password.reset', ['email' => 'guru@example.test']))
            ->assertSessionHas('success', 'Server email menerima permintaan pengiriman kode OTP. Jika belum terlihat, periksa folder Spam atau Promosi. Kode berlaku selama 10 menit.');

        $code = null;
        Mail::assertSent(PasswordResetCodeMail::class, function (PasswordResetCodeMail $mail) use (&$code) {
            $code = $mail->code;

            return true;
        });
        $this->assertNotNull($code);
        $this->get(route('password.reset', ['email' => $user->email]))
            ->assertOk()
            ->assertSee('Verifikasi email')
            ->assertSee('name="code"', false)
            ->assertDontSee('name="password"', false);

        $this->post(route('password.verify'), [
            'email' => $user->email,
            'code' => $code,
        ])->assertRedirect(route('password.reset', ['email' => $user->email]))
            ->assertSessionHas('success', 'Email berhasil diverifikasi. Silakan buat kata sandi baru.');

        $this->get(route('password.reset', ['email' => $user->email]))
            ->assertOk()
            ->assertSee('Buat kata sandi baru')
            ->assertSee('name="password"', false)
            ->assertDontSee('name="code"', false);

        $this->post(route('password.update'), [
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('new-password-123', $user->fresh()->password));
        $this->assertDatabaseMissing('password_reset_codes', ['email' => $user->email]);
    }

    public function test_unregistered_email_cannot_access_password_reset(): void
    {
        $this->post(route('password.email'), ['email' => 'missing@example.test'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->get(route('password.reset', ['email' => 'missing@example.test']))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->post(route('password.update'), [
            'email' => 'missing@example.test',
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->post(route('password.verify'), ['email' => 'missing@example.test', 'code' => '123456'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->post(route('password.update'), ['email' => 'missing@example.test'])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertSame(0, DB::table('password_reset_codes')->count());
    }

    public function test_password_cannot_be_changed_before_email_is_verified(): void
    {
        $user = $this->userWithRole('guru', 'guru@example.test');

        $this->post(route('password.update'), [
            'email' => $user->email,
            'password' => 'new-password-123',
            'password_confirmation' => 'new-password-123',
        ])->assertRedirect(route('password.reset', ['email' => $user->email]))
            ->assertSessionHas('reset_error', 'Verifikasi email terlebih dahulu sebelum mengganti kata sandi.');

        $this->assertFalse(Hash::check('new-password-123', $user->fresh()->password));
    }

    public function test_wrong_otp_does_not_advance_to_password_change(): void
    {
        $user = $this->userWithRole('guru', 'guru@example.test');
        DB::table('password_reset_codes')->insert([
            'email' => $user->email,
            'code' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('password.verify'), [
            'email' => $user->email,
            'code' => '654321',
        ])->assertRedirect(route('password.reset', ['email' => $user->email]))
            ->assertSessionHas('reset_error', 'Kode verifikasi salah.');

        $this->get(route('password.reset', ['email' => $user->email]))
            ->assertOk()
            ->assertSee('Verifikasi email')
            ->assertDontSee('name="password"', false);

        $this->assertDatabaseHas('password_reset_codes', ['email' => $user->email, 'attempts' => 1]);
    }

    public function test_registered_user_can_resend_otp_without_entering_email_again(): void
    {
        Mail::fake();
        $user = $this->userWithRole('guru', 'guru@example.test');
        config(['mail.mailers.smtp.transport' => 'array']);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset', ['email' => $user->email]));

        $this->get(route('password.reset', ['email' => $user->email]))
            ->assertOk()
            ->assertSee('Kirim ulang kode OTP')
            ->assertSee('name="email" value="guru@example.test"', false);

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset', ['email' => $user->email]))
            ->assertSessionHas('success');

        Mail::assertSent(PasswordResetCodeMail::class, 2);
        $this->assertDatabaseHas('password_reset_codes', ['email' => $user->email]);
    }

    public function test_password_reset_throttle_shows_an_indonesian_retry_message_after_five_requests(): void
    {
        foreach (range(1, 5) as $_) {
            $this->post(route('password.email'), ['email' => 'missing@example.test'])
                ->assertRedirect(route('login'));
        }

        $this->post(route('password.email'), ['email' => 'missing@example.test'])
            ->assertStatus(429)
            ->assertSee('Terlalu banyak permintaan')
            ->assertSee('Silakan tunggu');
    }

    private function userWithRole(string $role, string $email): User
    {
        $roleModel = Role::firstOrCreate(['name' => $role]);

        return User::factory()->create(['role_id' => $roleModel->id, 'email' => $email]);
    }
}
