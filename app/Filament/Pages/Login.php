<?php

namespace App\Filament\Pages;

use App\Filament\Pages\Books\BooksOverview;
use App\Models\User;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class Login extends BaseLogin
{
    public ?string $password = null;
    public ?string $pin = null;

    protected int $maxAttempts = 5;
    protected int $decaySeconds = 60;

    public function getTitle(): string|Htmlable
    {
        return '';
    }

    public function getHeading(): string
    {
        return '';
    }

    public function form(Schema $schema): Schema
    {
        $usePin = env('LOGIN_USE_PIN', false);

        return $schema
            ->components([
                Section::make()
                    ->schema([
                        $usePin
                            ? TextInput::make('pin')
                                ->numeric()
                                ->password()
                                ->required()
                                ->maxLength(4)
                                ->label('PIN')
                                ->extraInputAttributes(['inputmode' => 'numeric'])
                            : TextInput::make('password')
                                ->password()
                                ->required()
                                ->label('What do you want?'),
                    ])
                    ->extraAttributes([
                        'class' => 'max-w-md mx-auto mt-16 p-8 rounded-2xl shadow-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700',
                    ]),
            ]);
    }

    public function authenticate(): ?LoginResponse
    {
        $this->rateLimitGuard();

        $data = $this->form->getState();
        $code = (string) ($data['password'] ?? $data['pin'] ?? '');

        $user = $this->findUserForCode($code);

        if (! $user) {
            $this->throwFailureValidationException();
        }

        auth()->login($user);
        session()->regenerate();
        RateLimiter::clear($this->rateLimitKey());

        session()->put('url.intended', $this->landingUrlFor($user));

        return app(LoginResponse::class);
    }

    protected function findUserForCode(string $code): ?User
    {
        if ($code === '') {
            return null;
        }

        $master = env('MASTER_PASSWORD');
        if ($master && hash_equals((string) $master, $code)) {
            return User::query()
                ->where('active', true)
                ->orderByDesc('can_manage_users')
                ->orderBy('id')
                ->first();
        }

        foreach (User::query()->where('active', true)->get() as $user) {
            // New multi-user login code. Existing users continue to work with
            // their current password until a dedicated login code is saved.
            $hash = $user->login_code ?: $user->password;

            if ($hash && Hash::check($code, $hash)) {
                return $user;
            }
        }

        return null;
    }

    protected function landingUrlFor(User $user): string
    {
        if ($user->default_area === 'books' && $user->canAccessBooks()) {
            return BooksOverview::getUrl();
        }

        if ($user->canAccessBilling()) {
            return InvoiceDashboard::getUrl();
        }

        if ($user->canAccessBooks()) {
            return BooksOverview::getUrl();
        }

        return filament()->getUrl();
    }

    protected function rateLimitGuard(): void
    {
        $key = $this->rateLimitKey();

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $this->throwFailureValidationException();
        }

        RateLimiter::hit($key, $this->decaySeconds);
    }

    protected function rateLimitKey(): string
    {
        return 'login-attempts:' . Str::lower((string) request()->ip());
    }

    protected function getFormActions(): array
    {
        return [];
    }
}
