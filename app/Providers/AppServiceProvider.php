<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use App\Observers\AppointmentObserver;
use App\Observers\ScheduleObserver;
use App\Observers\VisitObserver;
use App\Models\Appointment;
use App\Models\Schedule;
use App\Models\Visit;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Appointment::observe(AppointmentObserver::class);
        Visit::observe(VisitObserver::class);
        Schedule::observe(ScheduleObserver::class);

        Blade::if('role', function ($roles) {
        if (!auth()->check()) {
            return false;
        }
        
        $rolesArray = is_array($roles) ? $roles : explode(',', $roles);
        
        return auth()->user()->hasRole($rolesArray);
    });
    }
}
