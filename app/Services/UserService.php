<?php


namespace App\Services;


use App\Models\User;

class UserService
{
    public function registerTelegramUser(int $telegramID): User
    {
        return User::firstOrCreate([
            'telegram_id' => $telegramID
        ]);
    }
}
