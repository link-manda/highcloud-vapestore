<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'id_cabang', // Penting untuk Staf
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Implementasi wajib untuk Filament.
     * Memeriksa apakah user dapat mengakses Panel Filament.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Saat ini, kita izinkan semua user (admin dan staf) mengakses panel.
        // Otorisasi lebih lanjut akan diatur di dalam Resource/Page.
        return true;
    }

    /**
     * Relasi: Satu User (Staf) dimiliki oleh satu Cabang.
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    /**
     * Helper function untuk memeriksa role Admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Helper function untuk memeriksa role Staf.
     */
    public function isStaf(): bool
    {
        return $this->role === 'staf';
    }
}
