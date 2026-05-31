# Forgot Password — Design Spec

**Date:** 2026-05-31
**Feature:** Self-service password reset for the Filament admin panel (`/admin`).
**Approach:** Native Filament password reset (Approach A) — reuse Laravel/Filament built-ins, customize presentation only.

## Goal

Let any authenticated user (admin or staf) reset their own password via an emailed reset link, without admin intervention. Reset pages and the reset email are themed to match the existing "Neon Monsoon" login design.

## Decisions (from brainstorming)

- **Flow:** Self-service via email. User enters email, receives reset link.
- **UI:** Reset pages themed to match login; reset email branded.
- **Email language:** English (consistent with current login page copy).

## Reused infrastructure (already present, unchanged)

| Component | State |
| :--- | :--- |
| `password_reset_tokens` table | Exists (`0001_01_01_000000_create_users_table.php`) |
| Password broker `users` | Configured in `config/auth.php` — expire 60 min, throttle 60 s |
| Mail transport | SMTP Mailtrap sandbox (`.env`) |
| Queue | `database` driver (`.env`) |
| `User` model | Uses `Notifiable` trait — reset notification ready |

## Architecture

Enable Filament's built-in password reset on the panel. Filament registers two auth pages (request + reset) wired to the existing broker and table. We override both page classes to apply custom themed views (mirroring the existing `CustomLogin` pattern) and override the reset-password notification to send a branded English email. No manual routes or controllers.

Each unit has one purpose, communicates through Filament's page/notification interfaces, and is independently testable.

## Components

### New / changed files

| File | Action | Purpose |
| :--- | :--- | :--- |
| `app/Providers/Filament/AdminPanelProvider.php` | edit | Add `->passwordReset(RequestPasswordReset::class, ResetPassword::class)` |
| `app/Filament/Auth/RequestPasswordReset.php` | new | Extends Filament base `RequestPasswordReset`; sets `$view` + `$layout` (same shape as `CustomLogin`) |
| `app/Filament/Auth/ResetPassword.php` | new | Extends Filament base `ResetPassword`; sets `$view` + `$layout` |
| `resources/views/filament/auth/request-password-reset.blade.php` | new | Themed view (Neon Monsoon), mirrors login layout |
| `resources/views/filament/auth/reset-password.blade.php` | new | Themed view (Neon Monsoon) |
| `app/Notifications/ResetPasswordNotification.php` | new | Branded English reset email (logo, colors, 60-min expiry note) |
| `resources/views/filament/auth/login.blade.php` | edit | Add "Forgot password?" link to the request page |

The custom notification is wired by overriding `sendPasswordResetNotification($token)` on the `User` model so it dispatches `ResetPasswordNotification` instead of Laravel's default. This is the single wiring point — the broker calls this method on the user.

## Data flow

```
Login → "Forgot password?" → RequestPasswordReset page
  → enter email → submit
  → Filament validates → broker creates token (password_reset_tokens)
  → User::sendPasswordResetNotification($token) → ResetPasswordNotification
  → branded email sent via Mailtrap (queued)
  → generic success message (no user enumeration; throttled 60 s)

Email → "Reset Password" button → ResetPassword page (token + email prefilled)
  → enter new password + confirmation → submit
  → broker verifies token (expires 60 min) → password hashed & saved
  → token deleted → redirect to login
```

Reset URL is built via Filament's panel URL helper (exact method confirmed at implementation), not a hand-written route.

## Email branding (English)

- Neon Monsoon palette (charcoal `#060e20`, purple `#ba9eff`, cyan `#53ddfc`), logo, fonts Plus Jakarta Sans / Manrope.
- Heading "Reset Your Password", CTA button "Reset Password" → reset URL.
- "This link expires in 60 minutes."
- "If you didn't request this, you can safely ignore this email."

## Security

- **No user enumeration:** success message is identical whether or not the email exists (Filament default). Do not change this to reveal account existence.
- **Token hashing:** tokens stored hashed in DB (Laravel default).
- **Throttling:** 60 s between reset requests (broker config).
- **APP_URL is currently `http://`** — reset links will be HTTP in this env. Production MUST use HTTPS so reset tokens are not exposed in transit. *Operational note, not code.*
- **Queue worker required:** emails are queued (`queue=database`). A worker (`php artisan queue:work`) must run or emails will not send. *Operational note.*

## Testing

- Feature: request reset with valid email → `Notification::fake()` asserts `ResetPasswordNotification` sent to the user.
- Feature: request reset with unknown email → no notification, same generic response (enumeration guard).
- Feature: reset with valid token → user password updated, can log in with new password.
- Feature: reset with expired/invalid token → fails, password unchanged.
- Manual: trigger flow, confirm branded email renders in Mailtrap inbox.

## Out of scope (YAGNI)

- Admin-initiated reset of other users.
- SMS / 2FA reset channels.
- Password strength policy beyond Laravel defaults.
- Email verification / registration (separate Filament features, not enabled here).
