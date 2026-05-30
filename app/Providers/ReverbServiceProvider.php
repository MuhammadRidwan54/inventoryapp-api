<?php
// app/Providers/ReverbServiceProvider.php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Broadcast;

class ReverbServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Broadcast::routes(['prefix' => 'api', 'middleware' => ['auth:sanctum']]);
        
        require base_path('routes/channels.php');
    }
}