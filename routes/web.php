<?php

use Illuminate\Support\Facades\Route;
use Lastdino\ApprovalFlow\Livewire\ApprovalFlow\Edit;


Route::middleware(config('approval-flow.routes.middleware'))
    ->prefix(config('approval-flow.routes.prefix'))
    ->group(function () {
        Route::get('/edit', Edit::class);
        Route::get('/detail',Edit::class)->name('detail');
    });
