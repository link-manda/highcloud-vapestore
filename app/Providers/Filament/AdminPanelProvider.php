<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(\App\Filament\Auth\CustomLogin::class)
            ->databaseNotifications()
            ->colors([
                'primary' => Color::Violet,
                'success' => Color::Emerald,
                'info'    => Color::Cyan,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
                'gray'    => Color::Gray,
            ])
            ->renderHook(
                'panels::styles.after',
                fn (): string => \Illuminate\Support\Facades\Blade::render('
                    <style>
                        .fi-sidebar-item-active {
                            background-color: rgba(var(--primary-500), 0.05) !important;
                            border-left: 4px solid rgb(var(--primary-500)) !important;
                        }
                        .fi-sidebar-item-active .fi-sidebar-item-label {
                            color: rgb(var(--primary-600)) !important;
                            font-weight: 800 !important;
                        }
                        .dark .fi-sidebar-item-active {
                            background-color: rgba(var(--primary-400), 0.1) !important;
                        }
                        .dark .fi-sidebar-item-active .fi-sidebar-item-label {
                            color: rgb(var(--primary-400)) !important;
                        }
                    </style>
                '),
            )
            ->brandName('Highcloud Inventory')
            ->navigationGroups([ // Ini untuk mengelompokkan menu
                'Data Master',
                'Transaksi Inventori',
                'Laporan',
                'Manajemen Sistem',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}
