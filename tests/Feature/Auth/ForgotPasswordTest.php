<?php

namespace Tests\Feature\Auth;

use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Auth\ResetPassword;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Livewire\Livewire;
use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    public function test_request_password_reset_page_is_accessible(): void
    {
        $response = $this->get('/admin/password-reset/request');

        $response->assertStatus(200);
    }

    public function test_request_password_reset_page_contains_submit_action(): void
    {
        $response = $this->get('/admin/password-reset/request');

        $response->assertStatus(200);
        $response->assertSee('Reset Password');
    }

    public function test_request_reset_sends_custom_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => $user->email])
            ->call('request');

        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);

        $token = Password::broker('users')->createToken($user);

        Livewire::test(ResetPassword::class, [
            'email' => $user->email,
            'token' => $token,
        ])
            ->fillForm([
                'password' => 'new-password-123',
                'passwordConfirmation' => 'new-password-123',
            ])
            ->call('resetPassword');

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-123', $user->password));
    }

    public function test_reset_password_fails_with_invalid_token(): void
    {
        $user = User::factory()->create([
            'password' => bcrypt('old-password'),
        ]);

        Livewire::test(ResetPassword::class, [
            'email' => $user->email,
            'token' => 'invalid-token',
        ])
            ->fillForm([
                'password' => 'new-password-123',
                'passwordConfirmation' => 'new-password-123',
            ])
            ->call('resetPassword');

        $user->refresh();
        $this->assertTrue(Hash::check('old-password', $user->password));
    }

    public function test_unknown_email_does_not_send_notification(): void
    {
        Notification::fake();

        Livewire::test(RequestPasswordReset::class)
            ->fillForm(['email' => 'unknown@example.com'])
            ->call('request');

        Notification::assertNothingSent();
    }
}
