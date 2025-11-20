<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Mail;
use App\Mail\SparkPostHttpTransport;

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
        // Register SparkPost HTTP transport
        $this->app['mail.manager']->extend('sparkpost_http', function (array $config) {
            // Get API key from config or env
            $apiKey = $config['api_key'] 
                ?? config('mail.mailers.sparkpost_http.api_key')
                ?? config('services.sparkpost.secret')
                ?? env('SPARKPOST_API_KEY')
                ?? env('MAIL_PASSWORD');
            
            \Log::info('SparkPost HTTP Transport initialized', [
                'api_key_length' => strlen($apiKey ?? ''),
                'from_email' => config('mail.from.address'),
                'from_name' => config('mail.from.name'),
            ]);
            
            return new SparkPostHttpTransport(
                $apiKey,
                config('mail.from.address'),
                config('mail.from.name')
            );
        });
    }
}
