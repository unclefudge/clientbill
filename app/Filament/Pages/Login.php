<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Forms\Components\TextInput;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class Login extends BaseLogin
{
    public ?string $email = 'fudge@jordan.net.au';
    public ?string $password = null;
    public ?string $pin = null;

    protected int $maxAttempts = 5;
    protected int $decaySeconds = 60;

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return '';
    }

    /* Branding */
    public function getHeading(): string
    {
        return '';
    }

    /*public function getSubheading(): ?string
    {
        return 'Fast & secure access';
    }*/

    /* -------------------------------
       FORM SCHEMA (PIN OR PASSWORD)
       -------------------------------*/
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
                            //->required()
                            ->label('What do you want?'),
                    ])
                    ->extraAttributes(['class' => 'max-w-md mx-auto mt-16 p-8 rounded-2xl shadow-xl bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700',]),
            ]);
    }

    /* -------------------------------
       AUTHENTICATION
       -------------------------------*/
    public function authenticate(): ?LoginResponse
    {
        $this->rateLimitGuard();
        $data = $this->form->getState();
        $passwordEntered = $data['password'] ?? $data['pin'] ?? null;

        $user = User::where('email', $this->email)->first();

        if (!$user) {
            $this->throwFailureValidationException();
        }

        /* MASTER PASSWORD */
        $master = env('MASTER_PASSWORD');
        if ($master && $passwordEntered === $master) {
            auth()->login($user);
            session()->regenerate();
            return app(LoginResponse::class);
        }

        /* PIN MODE */
        if (env('LOGIN_USE_PIN', false)) {
            if (!Hash::check($passwordEntered, $user->password)) {
                $this->throwFailureValidationException();
            }
        }

        /* PASSWORD MODE */
        if (!env('LOGIN_USE_PIN', false)) {
            if (!Hash::check($passwordEntered, $user->password)) {
                $this->throwFailureValidationException();
            }
        }

        auth()->login($user);
        session()->regenerate();

        return app(LoginResponse::class);
    }

    /* -------------------------------
       RATE LIMITING
       -------------------------------*/
    protected function rateLimitGuard(): void
    {
        $key = 'login-attempts:' . Str::lower($this->email);

        if (RateLimiter::tooManyAttempts($key, $this->maxAttempts)) {
            $this->throwFailureValidationException();
        }

        RateLimiter::hit($key, $this->decaySeconds);
    }

    /* -------------------------------
       REMOVES BUTTON
       -------------------------------*/
    protected function getFormActions(): array
    {
        // Hide the "Sign in" button completely
        return [];
    }
}
