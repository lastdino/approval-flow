<?php

use Illuminate\Support\Facades\Route;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Edit;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Detail;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\FlowList;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\TaskList;


Route::middleware(config('approval-flow.routes.middleware'))
    ->prefix(config('approval-flow.routes.prefix'))
    ->name(config('approval-flow.routes.prefix'). '.')
    ->group(function () {
        Route::get('/edit', Edit::class)->name('edit');
        Route::get('/detail/{task}',Detail::class)->name('detail');
        Route::get('/flow_list',FlowList::class)->name('flow_list');
        Route::get('/task_list',TaskList::class)->name('task_list');
    });
