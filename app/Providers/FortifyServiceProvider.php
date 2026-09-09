<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        Fortify::authenticateUsing(function (Request $request) {
            $user = \App\Models\User::where('email', $request->input(\Laravel\Fortify\Fortify::username()))->first();

            if ($user && \Illuminate\Support\Facades\Hash::check($request->password, $user->password)) {
                // If logging in under a specific tenant context
                if (\Modules\Context\Facades\Context::hasTenant()) {
                    if ($user->isPlatformAdmin() || $user->tenant_id === \Modules\Context\Facades\Context::tenantId()) {
                        return $user;
                    }

                    throw \Illuminate\Validation\ValidationException::withMessages([
                        \Laravel\Fortify\Fortify::username() => ['This account is not authorized to access this store/tenant.'],
                    ]);
                }

                // If logging in centrally, sync user tenant to session
                if ($user->tenant_id) {
                    $request->session()->put('current_tenant_id', $user->tenant_id);
                }

                return $user;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
