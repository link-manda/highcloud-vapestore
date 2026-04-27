<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class WelcomeWidget extends Widget
{
    protected static string $view = 'filament.widgets.welcome-widget';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    public function getUser()
    {
        return Auth::user();
    }

    public function getCabang()
    {
        return Auth::user()->cabang;
    }

    public function getGreeting(): string
    {
        $hour = date('H');
        if ($hour < 12) return 'Selamat Pagi';
        if ($hour < 15) return 'Selamat Siang';
        if ($hour < 18) return 'Selamat Sore';
        return 'Selamat Malam';
    }
}
