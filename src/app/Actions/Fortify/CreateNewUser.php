<?php

namespace App\Actions\Fortify;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\CreatesNewUsers;

class CreateNewUser implements CreatesNewUsers
{
    /**
     * @param  array<string, string>  $input
     */
    public function create(array $input): User
    {
        return User::create([
            'name' => $input['name'],
            'email' => mb_strtolower($input['email']),
            'role' => User::ROLE_USER,
            'password' => Hash::make($input['password']),
        ]);
    }
}
