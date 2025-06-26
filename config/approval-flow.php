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

    /*
    |--------------------------------------------------------------------------
    | Date and Time Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how dates and times are handled in the approval flow system.
    | This includes format settings preferences.
    |
    */
    'datetime' => [
        // Format settings for different date displays
        'formats' => [
            'default' => 'Y-m-d H:i:s',
            'date' => 'Y-m-d',
            'time' => 'H:i:s',
            'year_month' => 'Y-m',
        ],
    ],


    /**
     * User Display Configuration
     * - display_name_column: Column name used for user display name
     * - fallback_columns: Array of alternative columns to use if display name is not found
     */
    'user' => [
        'display_name_column' => 'Full_name',  // Default display name column
        'fallback_columns' => ['full_name', 'display_name','name', ], // Fallback columns array
    ],


];
