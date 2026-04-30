<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Interfaces\RawatJalanRepositoryInterface;
use App\Repositories\Eloquent\RawatJalanRepository;
use App\Repositories\Interfaces\RawatInapRepositoryInterface;
use App\Repositories\Eloquent\RawatInapRepository;
use App\Repositories\Interfaces\IgdRepositoryInterface;
use App\Repositories\Eloquent\IgdRepository;
use App\Repositories\Interfaces\LabRepositoryInterface;
use App\Repositories\Eloquent\LabRepository;
use App\Repositories\Interfaces\RadiologiRepositoryInterface;
use App\Repositories\Eloquent\RadiologiRepository;
use App\Repositories\Interfaces\HemodialisaRepositoryInterface;
use App\Repositories\Eloquent\HemodialisaRepository;
use App\Repositories\Interfaces\RekamMedisRepositoryInterface;
use App\Repositories\Eloquent\RekamMedisRepository;
use App\Repositories\Interfaces\AuthRepositoryInterface;
use App\Repositories\Eloquent\AuthRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(RawatJalanRepositoryInterface::class, RawatJalanRepository::class);
        $this->app->bind(RawatInapRepositoryInterface::class, RawatInapRepository::class);
        $this->app->bind(IgdRepositoryInterface::class, IgdRepository::class);
        $this->app->bind(LabRepositoryInterface::class, LabRepository::class);
        $this->app->bind(RadiologiRepositoryInterface::class, RadiologiRepository::class);
        $this->app->bind(HemodialisaRepositoryInterface::class, HemodialisaRepository::class);
        $this->app->bind(RekamMedisRepositoryInterface::class, RekamMedisRepository::class);
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
