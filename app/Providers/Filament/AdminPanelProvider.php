<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\EditProfile;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Routing\Route;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('/')
            /*->routes(function ($router) {
                $router
                    ->middleware(['web', 'auth'])
                    ->get('/invoice/preview', function () {
                        $payload = json_decode(base64_decode(request('previewData')), true);

                        return view('invoice-preview', [
                            'activeInvoice' => (object) $payload['invoice'],
                            'invoiceItems'  => $payload['items'],
                        ]);
                    })
                    ->name('invoice.preview');
            })*/
            ->renderHook(
                \Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
                fn () => view('filament.hooks.hide-login-brand')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::HEAD_START,
                fn () => auth()->check()
                    ? view('filament.hooks.user-theme-head', [
                        'themeMode' => auth()->user()->theme_mode ?: 'system',
                    ])
                    : view('filament.hooks.login-theme-head')
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::BODY_END,
                fn () => auth()->check()
                    ? Blade::render("@livewire('user-theme-preference')")
                    : ''
            )
            ->renderHook(
                \Filament\View\PanelsRenderHook::PAGE_START,
                fn () => auth()->user()?->canAccessBooks() && $this->isBooksUiRequest()
                    ? view('filament.hooks.books-navigation')
                    : ''
            )
            ->login(\App\Filament\Pages\Login::class)
            ->profile(EditProfile::class)
            ->userMenuItems([
                'profile' => fn (Action $action) => $action->label('My Profile'),
            ])
            ->navigationGroups([NavigationGroup::make('Admin'),])
            ->colors(['primary' => Color::Amber, 'info' => Color::hex('#a78bfa')]) // purple-400
            // Top Navigation
            ->sidebarWidth('12rem')
            ->maxContentWidth('full')
            ->sidebarFullyCollapsibleOnDesktop() // optional
            ->topNavigation()
            ->topbar(true)
            ->maxContentWidth('full')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                //Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
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
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }

    /**
     * Filament/Livewire actions are posted to /livewire/update, so checking only
     * request()->is('books*') makes the Books sub-navigation disappear after
     * an in-page Livewire update (for example, closing a transaction modal).
     * Fall back to the referring page for those requests.
     */
    protected function isBooksUiRequest(): bool
    {
        if (request()->is('books') || request()->is('books/*')) {
            return true;
        }

        $referer = request()->headers->get('referer');

        if (! $referer) {
            return false;
        }

        $path = trim((string) parse_url($referer, PHP_URL_PATH), '/');

        return $path === 'books' || str_starts_with($path, 'books/');
    }
}
