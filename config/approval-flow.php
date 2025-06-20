<?php

// config for Lastdino/approvalflow
return [

    /**
     * This is the name of the table that contains the roles used to classify users
     * (for spatie-laravel-permissions it is the `roles` table
     */
    'roles_model' => "\\Spatie\\Permission\\Models\\Role",


    /**
     * The model associated with login and authentication
     */
    'users_model' => "\\App\\Models\\User",

    /*
    |--------------------------------------------------------------------------
    | Routes Configuration
    |--------------------------------------------------------------------------
    |
    | Customize the URL prefix, middleware stack, and guards for all approval-flow
    | routes. This gives you control over route access and grouping.
    |
    */
    'routes' => [
        'prefix' => 'flow',
        'middleware' => ['web'],
        'guards' => ['web'],
    ],



];
