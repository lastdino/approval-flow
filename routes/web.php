<?php

use Illuminate\Support\Facades\Route;
Route::middleware(config('approval-flow.routes.middleware'))
    ->prefix(config('approval-flow.routes.prefix'))
    ->name(config('approval-flow.routes.prefix'). '.')
    ->group(function () {
        Route::livewire('/edit', 'approval-flow::Edit')->name('edit');
        Route::livewire('/detail/{task}', 'approval-flow::Detail')->name('detail');
        Route::livewire('/flow_list', 'approval-flow::FlowList')->name('flow_list');
        Route::livewire('/task_list', 'approval-flow::TaskList')->name('task_list');
    });
