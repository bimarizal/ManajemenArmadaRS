<?php

namespace App\Providers;

use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Jika APP_URL memakai https, semua URL yang dibuat aplikasi ikut https.
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        // Kebijakan password: min 8, huruf besar+kecil, angka. Di production dicek juga ke daftar password bocor.
        Password::defaults(function () {
            $aturan = Password::min(8)->letters()->mixedCase()->numbers();

            return $this->app->isProduction() ? $aturan->uncompromised() : $aturan;
        });

        // Jejak audit autentikasi.
        Event::listen(Login::class, function (Login $event): void {
            activity('auth')->event('login')->causedBy($event->user)
                ->withProperties($this->jejakRequest())->log('Login berhasil');
        });

        Event::listen(Logout::class, function (Logout $event): void {
            if ($event->user) {
                activity('auth')->event('logout')->causedBy($event->user)
                    ->withProperties($this->jejakRequest())->log('Logout');
            }
        });

        Event::listen(Failed::class, function (Failed $event): void {
            activity('auth')->event('login_gagal')
                ->withProperties($this->jejakRequest() + ['email' => $event->credentials['email'] ?? null])
                ->log('Login gagal');
        });
    }

    private function jejakRequest(): array
    {
        return [
            'ip' => request()->ip(),
            'user_agent' => Str::limit((string) request()->userAgent(), 200),
        ];
    }
}
