<?php
namespace Fariddomat\AutoCrud;

use Illuminate\Support\ServiceProvider;

class AutoCrudServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // تحميل الـ views
        $this->loadViewsFrom(__DIR__ . '/resources/views', 'autocrud');

        // تحميل الـ migrations
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');

        // تحميل الـ routes
        $this->loadRoutesFrom(__DIR__ . '/routes/web.php');

        // نشر الملفات
        $this->publishes([
            __DIR__.'/resources/views' => resource_path('views/vendor/auto-crud'),
        ], 'autocrud-views');
    }

    public function register()
    {
        // تسجيل الأوامر
        $this->commands([
            \Fariddomat\AutoCrud\Commands\MakeAutoCrud::class,
        ]);
    }
}
