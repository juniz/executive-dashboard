<?php

namespace App\Repositories\Interfaces;

interface AuthRepositoryInterface
{
    public function validateCredentials($id_user, $password);

    public function createUser($id_user, $password);
}
