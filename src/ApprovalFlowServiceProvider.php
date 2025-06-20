<?php

namespace Lastdino\Approvalflow;

use Illuminate\Support\ServiceProvider;
use Lastdino\Approvalflow\Livewire\Approvalflow\Edit;
use Livewire\Livewire;


class ApprovalFlowServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/approval-flow.php' => config_path('approval-flow.php'),
        ],'approvalflow-config');

        $this->publishes([
            __DIR__.'/../resources/css/approval-flow.css' => public_path('vendor/approval-flow/approval-flow.css'),
        ], 'approvalflow-assets');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/approval-flow'),
        ], 'approvalflow-views');

        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'approvalflow-migrations');

        $this->loadLivewireComponents();

        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'approval-flow');

    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/approval-flow.php',
            'approval-flow'
        );
    }

    // custom methods for livewire components
    protected function loadLivewireComponents(): void
    {
        Livewire::component('approval-flow.edit', Edit::class);
    }
}
