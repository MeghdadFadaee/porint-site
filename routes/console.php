<?php

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

Artisan::command('porint:staff {email}', function (string $email): int {
    $name = $this->ask('Name');
    $password = $this->secret('Password (at least 12 characters)');
    $data = compact('name', 'email', 'password');
    $validator = Validator::make($data, [
        'name' => 'required|string|max:120',
        'email' => 'required|email|max:255|unique:users,email',
        'password' => 'required|string|min:12',
    ]);

    if ($validator->fails()) {
        $this->error($validator->errors()->first());

        return 1;
    }

    $user = new User($data);
    $user->forceFill(['is_staff' => true])->save();
    $this->info('Staff account ready.');

    return 0;
})->purpose('Create a PORINT staff account');
