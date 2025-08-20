<?php

namespace App\Providers;

use App\Models\Template;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot()
    {
        parent::boot();

        // Custom binding: resolve Template by id OR slug
        Route::bind('template', function ($value) {
            return Template::where('id', $value)
                ->orWhere('slug', $value)
                ->firstOrFail();
        });
    }
}
