<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Create storage symlink if it doesn't exist
        $publicPath = $this->app->basePath('public/storage');
        $storagePath = $this->app->storagePath('app/public');

        if (!file_exists($publicPath)) {
            if (!file_exists($storagePath)) {
                mkdir($storagePath, 0755, true);
            }
            symlink($storagePath, $publicPath);
        }
    }

    public function register()
    {
        //
    }
}
