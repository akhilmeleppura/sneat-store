<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Jetstream\Jetstream;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    /**
     * Validate and create a newly registered user.
     *
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => $this->passwordRules(),
            'terms' => Jetstream::hasTermsAndPrivacyPolicyFeature() ? ['accepted', 'required'] : '',
        ])->validate();

        $userData = [
            'name' => $input['name'],
            'email' => $input['email'],
            'password' => Hash::make($input['password']),
        ];

        if (\Modules\Context\Facades\Context::hasTenant()) {
            $userData['tenant_id'] = \Modules\Context\Facades\Context::tenantId();
            $userData['store_id'] = \Modules\Context\Facades\Context::storeId();
        }

        $user = User::create($userData);

        if (\Spatie\Permission\Models\Role::where('name', 'Customer')->where('guard_name', 'web')->exists()) {
            $user->assignRole('Customer');
        }

        return $user;
    }
}
