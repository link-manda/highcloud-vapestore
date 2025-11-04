<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;


// 2. IMPLEMENTS KONTRAK FILAMENT USER
class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable, HasRoles;

    // 3. GUNAKAN TRAIT
    use HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'id_cabang', // Tetap ada
        'role', // Kolom ini mungkin tidak diperlukan lagi, tapi biarkan dulu
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
     * Relasi ke Cabang
     */
    public function cabang(): BelongsTo
    {
        return $this->belongsTo(Cabang::class, 'id_cabang');
    }

    /**
     * [WAJIB ADA] Fungsi untuk Filament
     * Kita akan cek role 'Admin'
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Izinkan semua user yang terautentikasi mengakses panel.
        // Pembatasan fitur akan diatur via Resource dan Policy.
        // Jika Anda ingin membatasi staf agar tidak bisa login, 
        // Anda bisa tambahkan logika role di sini.
        // Untuk saat ini, kita izinkan semua.
        return true;
    }
}
