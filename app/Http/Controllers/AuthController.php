<?php

namespace App\Http\Controllers;

use App\Repositories\Interfaces\AuthRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Inertia\Inertia;

class AuthController extends Controller
{
    protected $repository;

    public function __construct(AuthRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Show the login page.
     */
    public function showLogin()
    {
        if (Session::get('authenticated')) {
            return redirect()->route('dashboard.index');
        }
        
        return Inertia::render('Login');
    }

    /**
     * Handle authentication using SIMRS Khanza structure.
     */
    public function login(Request $request)
    {
        $request->validate([
            'id_user' => 'required',
            'password' => 'required',
        ]);

        $user = $this->repository->validateCredentials($request->id_user, $request->password);

        if ($user) {
            Session::put('user_id', $user->id_user);
            Session::put('authenticated', true);

            return redirect()->route('dashboard.index');
        }

        return back()->withErrors([
            'id_user' => 'ID User atau Password salah.',
        ]);
    }

    /**
     * Script/Method to add a new user safely.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'id_user' => 'required|unique:user,id_user',
            'password' => 'required|min:6',
        ]);

        $this->repository->createUser($request->id_user, $request->password);

        return response()->json([
            'status' => 'success',
            'message' => "User {$request->id_user} has been created successfully."
        ]);
    }

    /**
     * Logout
     */
    public function logout()
    {
        Session::flush();
        return redirect()->route('login');
    }
}
