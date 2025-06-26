<?php

use Illuminate\Support\Facades\Route;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Edit;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Detail;


Route::middleware(config('approval-flow.routes.middleware'))
    ->prefix(config('approval-flow.routes.prefix'))
    ->name(config('approval-flow.routes.prefix'). '.')
    ->group(function () {
        Route::get('/edit', Edit::class)->name('edit');
        Route::get('/detail/{task}',Detail::class)->name('detail');
    });
