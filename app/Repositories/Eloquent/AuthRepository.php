<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\AuthRepositoryInterface;
use Illuminate\Support\Facades\DB;

class AuthRepository implements AuthRepositoryInterface
{
    /**
     * Validate user credentials using SIMRS Khanza legacy encryption.
     */
    public function validateCredentials($id_user, $password)
    {
        // Key standard Khanza is usually 'nur', but some hospitals change it.
        // We maintain the logic from the controller: 'nur' for id_user, 'windi' for password.
        return DB::table('user')
            ->whereRaw("AES_DECRYPT(id_user, 'nur') = ?", [$id_user])
            ->whereRaw("AES_DECRYPT(password, 'windi') = ?", [$password])
            ->selectRaw("AES_DECRYPT(id_user, 'nur') as id_user")
            ->first();
    }

    /**
     * Create a new user with legacy encryption.
     */
    public function createUser($id_user, $password)
    {
        return DB::table('user')->insert([
            'id_user' => $id_user,
            'password' => DB::raw("AES_ENCRYPT('{$password}', 'windi')") // Using 'windi' to match login
        ]);
    }
}
