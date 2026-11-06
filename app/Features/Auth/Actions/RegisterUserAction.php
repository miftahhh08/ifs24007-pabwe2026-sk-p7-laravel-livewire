<?php

namespace App\Features\Auth\Actions;

use App\Models\User;

class RegisterUserAction
{
    /**
     * @param  array{name: string, username: string, email: string, password: string}  $data
     */
    public function execute(array $data): User
    {
        return User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => 'member',
        ]);
    }
}
