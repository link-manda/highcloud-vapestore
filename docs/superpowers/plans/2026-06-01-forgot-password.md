# Forgot Password Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menambahkan alur lupa password self-service pada login panel Filament (`/admin`) dengan email reset ber-branding Neon Monsoon, aman dari user enumeration, dan tervalidasi lewat automated tests.

**Architecture:** Gunakan fitur native Filament password reset agar route, token broker, throttling, dan validasi mengikuti standar Laravel/Filament. Buat dua page auth kustom untuk request/reset password agar UI konsisten dengan halaman login kustom saat ini. Ganti notifikasi reset bawaan dengan notification class aplikasi agar email reset memakai branding dan copy yang disepakati.

**Tech Stack:** Laravel 11, Filament v3.3, Livewire, Laravel Notifications (mail), PHPUnit (Feature tests), Laravel broker `users`.

---

## File Structure Map

- **Modify** `app/Providers/Filament/AdminPanelProvider.php`  
  Mengaktifkan route/password reset pada panel admin.

- **Create** `app/Filament/Auth/RequestPasswordReset.php`  
  Wrapper page Filament request reset dengan custom view + layout.

- **Create** `app/Filament/Auth/ResetPassword.php`  
  Wrapper page Filament reset password dengan custom view + layout.

- **Create** `resources/views/filament/auth/request-password-reset.blade.php`  
  UI halaman kirim link reset dengan tema Neon Monsoon.

- **Create** `resources/views/filament/auth/reset-password.blade.php`  
  UI halaman set password baru dengan tema Neon Monsoon.

- **Create** `app/Notifications/ResetPasswordNotification.php`  
  Email reset ber-branding dan URL reset dari Filament panel.

- **Modify** `app/Models/User.php`  
  Override `sendPasswordResetNotification($token)` untuk pakai notification custom.

- **Modify** `resources/views/filament/auth/login.blade.php` *(jika link lupa password belum tampil otomatis dari field hint)*  
  Menambahkan link “Forgot password?” ke halaman request reset.

- **Create** `tests/Feature/Auth/ForgotPasswordTest.php`  
  Coverage request link, reset password sukses, token invalid/expired, unknown email.

---

### Task 1: Mengaktifkan Password Reset di Panel + Page Wrapper

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `app/Filament/Auth/RequestPasswordReset.php`
- Create: `app/Filament/Auth/ResetPassword.php`

- [ ] **Step 1: Tulis failing test untuk route reset tersedia**

```php
<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

class ForgotPasswordTest extends TestCase
{
    public function test_request_password_reset_page_is_accessible(): void
    {
        $response = $this->get('/admin/password-reset/request');

        $response->assertStatus(200);
    }
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=test_request_password_reset_page_is_accessible`  
Expected: FAIL (404 atau route belum tersedia).

- [ ] **Step 3: Aktifkan password reset di panel provider**

```php
// app/Providers/Filament/AdminPanelProvider.php
use App\Filament\Auth\RequestPasswordReset;
use App\Filament\Auth\ResetPassword;

return $panel
    ->default()
    ->id('admin')
    ->path('admin')
    ->login(\App\Filament\Auth\CustomLogin::class)
    ->passwordReset(RequestPasswordReset::class, ResetPassword::class)
    // ...
;
```

- [ ] **Step 4: Buat wrapper page request reset**

```php
<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\PasswordReset\RequestPasswordReset as BaseRequestPasswordReset;
use Illuminate\Contracts\Support\Htmlable;

class RequestPasswordReset extends BaseRequestPasswordReset
{
    protected static string $view = 'filament.auth.request-password-reset';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function getTitle(): string | Htmlable
    {
        return '';
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }
}
```

- [ ] **Step 5: Buat wrapper page reset password**

```php
<?php

namespace App\Filament\Auth;

use Filament\Pages\Auth\PasswordReset\ResetPassword as BaseResetPassword;
use Illuminate\Contracts\Support\Htmlable;

class ResetPassword extends BaseResetPassword
{
    protected static string $view = 'filament.auth.reset-password';

    protected static string $layout = 'filament-panels::components.layout.base';

    public function getTitle(): string | Htmlable
    {
        return '';
    }

    public function getHeading(): string | Htmlable
    {
        return '';
    }
}
```

- [ ] **Step 6: Jalankan test untuk memastikan route sudah aktif**

Run: `php artisan test --filter=test_request_password_reset_page_is_accessible`  
Expected: PASS.

- [ ] **Step 7: Commit Task 1**

```bash
git add app/Providers/Filament/AdminPanelProvider.php app/Filament/Auth/RequestPasswordReset.php app/Filament/Auth/ResetPassword.php tests/Feature/Auth/ForgotPasswordTest.php
git commit -m "feat(auth): enable filament password reset pages"
```

---

### Task 2: Menambahkan UI Neon Monsoon untuk Request/Reset Password

**Files:**
- Create: `resources/views/filament/auth/request-password-reset.blade.php`
- Create: `resources/views/filament/auth/reset-password.blade.php`
- Modify: `resources/views/filament/auth/login.blade.php` (conditional)

- [ ] **Step 1: Tulis failing test konten halaman request reset**

```php
public function test_request_password_reset_page_contains_submit_action(): void
{
    $response = $this->get('/admin/password-reset/request');

    $response->assertStatus(200);
    $response->assertSee('Reset Password', false);
}
```

- [ ] **Step 2: Jalankan test dan pastikan gagal**

Run: `php artisan test --filter=test_request_password_reset_page_contains_submit_action`  
Expected: FAIL (copy/markup belum sesuai).

- [ ] **Step 3: Implement view request password reset**

```blade
{{-- resources/views/filament/auth/request-password-reset.blade.php --}}
<div class="flex min-h-screen bg-[#060e20]">
    <div class="relative hidden w-0 flex-1 lg:block">
        <img class="absolute inset-0 h-full w-full object-cover" src="{{ asset('login-bg.png') }}" alt="Vape Store Interior">
        <div class="absolute inset-0 bg-gradient-to-r from-transparent to-[#060e20]"></div>
    </div>

    <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:flex-none lg:px-20 xl:px-24 bg-[#060e20] relative overflow-hidden">
        <div class="mx-auto w-full max-w-sm lg:w-96 relative z-10">
            <h2 class="text-3xl font-bold tracking-tight text-[#dee5ff]">Reset Password</h2>
            <p class="mt-2 text-sm text-[#a3aac4]">Enter your email to receive a reset link.</p>

            <form wire:submit="request" class="mt-8 space-y-6">
                {{ $this->form }}
                <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
            </form>
        </div>
    </div>
</div>
```

- [ ] **Step 4: Implement view reset password**

```blade
{{-- resources/views/filament/auth/reset-password.blade.php --}}
<div class="flex min-h-screen bg-[#060e20]">
    <div class="flex flex-1 flex-col justify-center px-4 py-12 sm:px-6 lg:px-20 xl:px-24 bg-[#060e20]">
        <div class="mx-auto w-full max-w-sm lg:w-96">
            <h2 class="text-3xl font-bold tracking-tight text-[#dee5ff]">Set New Password</h2>
            <p class="mt-2 text-sm text-[#a3aac4]">Use a strong password you haven't used before.</p>

            <form wire:submit="resetPassword" class="mt-8 space-y-6">
                {{ $this->form }}
                <x-filament-panels::form.actions :actions="$this->getCachedFormActions()" :full-width="$this->hasFullWidthFormActions()" />
            </form>
        </div>
    </div>
</div>
```

- [ ] **Step 5: Pastikan login punya akses ke forgot-password**

```blade
{{-- resources/views/filament/auth/login.blade.php --}}
{{-- Tambahkan link jika belum tampil via hint bawaan Filament --}}
<div class="mt-3 text-right">
    <a href="{{ filament()->getRequestPasswordResetUrl() }}" class="text-sm text-[#53ddfc] hover:underline">
        Forgot password?
    </a>
</div>
```

- [ ] **Step 6: Jalankan test untuk memastikan konten sudah sesuai**

Run: `php artisan test --filter=test_request_password_reset_page_contains_submit_action`  
Expected: PASS.

- [ ] **Step 7: Commit Task 2**

```bash
git add resources/views/filament/auth/request-password-reset.blade.php resources/views/filament/auth/reset-password.blade.php resources/views/filament/auth/login.blade.php tests/Feature/Auth/ForgotPasswordTest.php
git commit -m "feat(auth): add neon monsoon forgot/reset password views"
```

---

### Task 3: Custom Reset Email Notification + Wiring User Model

**Files:**
- Create: `app/Notifications/ResetPasswordNotification.php`
- Modify: `app/Models/User.php`
- Test: `tests/Feature/Auth/ForgotPasswordTest.php`

- [ ] **Step 1: Tulis failing test notifikasi custom terkirim**

```php
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Support\Facades\Notification;

public function test_request_reset_sends_custom_notification(): void
{
    Notification::fake();

    $user = User::factory()->create();

    $this->post('/livewire/update', [
        // placeholder payload akan diganti dengan helper Livewire test pada implementasi final
    ]);

    Notification::assertSentTo($user, ResetPasswordNotification::class);
}
```

- [ ] **Step 2: Jalankan test untuk memastikan gagal**

Run: `php artisan test --filter=test_request_reset_sends_custom_notification`  
Expected: FAIL (notification custom belum ada/wiring belum ada).

- [ ] **Step 3: Buat custom notification class**

```php
<?php

namespace App\Notifications;

use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\ResetPassword as BaseResetPassword;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends BaseResetPassword implements ShouldQueue
{
    use Queueable;

    public function toMail($notifiable): MailMessage
    {
        /** @var CanResetPassword $notifiable */
        $resetUrl = Filament::getResetPasswordUrl($this->token, $notifiable);

        return (new MailMessage)
            ->subject('Reset Your Password - Highcloud Vapestore')
            ->greeting('Hello!')
            ->line('We received a request to reset your password.')
            ->action('Reset Password', $resetUrl)
            ->line('This link expires in 60 minutes.')
            ->line("If you didn't request this, you can safely ignore this email.");
    }
}
```

- [ ] **Step 4: Override pengiriman notifikasi di User model**

```php
// app/Models/User.php
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements FilamentUser
{
    // ...
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
```

- [ ] **Step 5: Perbaiki test agar memanggil page request reset Filament**

```php
use App\Filament\Auth\RequestPasswordReset;
use Livewire\Livewire;

public function test_request_reset_sends_custom_notification(): void
{
    Notification::fake();

    $user = User::factory()->create();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => $user->email])
        ->call('request');

    Notification::assertSentTo($user, ResetPasswordNotification::class);
}
```

- [ ] **Step 6: Jalankan test dan pastikan lolos**

Run: `php artisan test --filter=test_request_reset_sends_custom_notification`  
Expected: PASS.

- [ ] **Step 7: Commit Task 3**

```bash
git add app/Notifications/ResetPasswordNotification.php app/Models/User.php tests/Feature/Auth/ForgotPasswordTest.php
git commit -m "feat(auth): customize reset password email notification"
```

---

### Task 4: Validasi End-to-End Password Reset Flow (Success + Failure Paths)

**Files:**
- Modify: `tests/Feature/Auth/ForgotPasswordTest.php`

- [ ] **Step 1: Tulis test reset password sukses dengan token valid**

```php
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;

public function test_user_can_reset_password_with_valid_token(): void
{
    $user = User::factory()->create([
        'password' => bcrypt('old-password'),
    ]);

    $token = Password::broker('users')->createToken($user);

    Livewire::test(\App\Filament\Auth\ResetPassword::class, [
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
```

- [ ] **Step 2: Tulis test token invalid/expired ditolak**

```php
public function test_reset_password_fails_with_invalid_token(): void
{
    $user = User::factory()->create([
        'password' => bcrypt('old-password'),
    ]);

    Livewire::test(\App\Filament\Auth\ResetPassword::class, [
        'email' => $user->email,
        'token' => 'invalid-token',
    ])
        ->fillForm([
            'password' => 'new-password-123',
            'passwordConfirmation' => 'new-password-123',
        ])
        ->call('resetPassword');

    $user->refresh();
    $this->assertTrue(\Illuminate\Support\Facades\Hash::check('old-password', $user->password));
}
```

- [ ] **Step 3: Tulis test unknown email tidak leak informasi akun**

```php
public function test_unknown_email_does_not_send_notification(): void
{
    Notification::fake();

    Livewire::test(RequestPasswordReset::class)
        ->fillForm(['email' => 'unknown@example.com'])
        ->call('request');

    Notification::assertNothingSent();
}
```

- [ ] **Step 4: Jalankan file test auth secara penuh**

Run: `php artisan test tests/Feature/Auth/ForgotPasswordTest.php`  
Expected: PASS semua test di file.

- [ ] **Step 5: Jalankan suite test project + formatter**

Run: `php artisan test`  
Expected: PASS.

Run: `./vendor/bin/pint`  
Expected: selesai tanpa error.

- [ ] **Step 6: Commit Task 4**

```bash
git add tests/Feature/Auth/ForgotPasswordTest.php
git commit -m "test(auth): cover forgot password flow end-to-end"
```

---

## Catatan Implementasi

- Gunakan URL helper Filament (`filament()->getRequestPasswordResetUrl()` / `Filament::getResetPasswordUrl(...)`) untuk menghindari hardcode path.
- Pertahankan pesan sukses generik bawaan (jangan bocorkan email terdaftar/tidak).
- Karena queue driver `database`, jalankan worker saat uji manual email:

```bash
php artisan queue:work
```

- Uji manual terakhir (di luar automated test):
  1. Buka `/admin/login`.
  2. Klik **Forgot password?**
  3. Masukkan email user valid.
  4. Buka Mailtrap, klik link reset.
  5. Set password baru dan login ulang.

