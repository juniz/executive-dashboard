<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardRawatJalanController;
use App\Http\Controllers\DashboardRawatInapController;
use App\Http\Controllers\DashboardIgdController;
use App\Http\Controllers\DashboardLabController;
use App\Http\Controllers\DashboardRadiologiController;
use App\Http\Controllers\DashboardHemodialisaController;
use App\Http\Controllers\DashboardRekamMedisController;

use App\Http\Controllers\AuthController;

Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
Route::post('/admin/user/add', [AuthController::class, 'storeUser']);

Route::middleware('khanza_auth')->group(function () {
    Route::get('/dashboard/rawat-jalan', [DashboardRawatJalanController::class, 'index'])->name('dashboard.rawat-jalan');
    Route::get('/dashboard/rawat-inap', [DashboardRawatInapController::class, 'index'])->name('dashboard.rawat-inap');
    Route::get('/dashboard/igd', [DashboardIgdController::class, 'index'])->name('dashboard.igd');
    Route::get('/dashboard/lab', [DashboardLabController::class, 'index'])->name('dashboard.lab');
    Route::get('/dashboard/radiologi', [DashboardRadiologiController::class, 'index'])->name('dashboard.radiologi');
    Route::get('/dashboard/hemodialisa', [DashboardHemodialisaController::class, 'index'])->name('dashboard.hemodialisa');
    Route::get('/dashboard/rekam-medis', [DashboardRekamMedisController::class, 'index'])->name('dashboard.rekam-medis');
    Route::inertia('/dashboard', 'DashboardIndex')->name('dashboard.index');
});

