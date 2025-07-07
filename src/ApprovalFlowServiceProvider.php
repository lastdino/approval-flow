<?php

namespace Lastdino\ApprovalFlow;

use Illuminate\Support\ServiceProvider;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Detail;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Edit;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\FlowList;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\TaskList;
use Livewire\Livewire;
use Lastdino\ApprovalFlow\Helpers\UserDisplayHelper;
use Lastdino\ApprovalFlow\Console\Commands\SendPendingApprovalNotifications;



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

        $this->publishes([
            __DIR__ . '/../lang' => lang_path('vendor/approval-flow'),
        ],'approvalflow-lang');

        $this->loadLivewireComponents();

        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'approval-flow');
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'approval-flow');

        if ($this->app->runningInConsole()) {
            $this->commands([
                SendPendingApprovalNotifications::class,
            ]);
        }

    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/approval-flow.php',
            'approval-flow'
        );

        $this->app->singleton('approval-flow.user-display', function () {
            return new UserDisplayHelper();
        });

    }

    // custom methods for livewire components
    protected function loadLivewireComponents(): void
    {
        Livewire::component('approval-flow.edit', Edit::class);
        Livewire::component('approval-flow.detail', Detail::class);
        Livewire::component('approval-flow.flow-list', FlowList::class);
        Livewire::component('approval-flow.task-list', TaskList::class);
    }
}
