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


    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the number of items displayed per page for flow lists and task lists.
    | - flow_list_per_page: Number of items per page in flow lists
    | - task_list_per_page: Number of items per page in task lists
    |
    */
    'pagination' => [
        'flow_list_per_page' => 25,
        'task_list_per_page' => 25,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Titles Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the notification titles used in the approval flow system.
    | These titles are displayed in various notifications sent to users.
    |
    */
    'notification_titles' => [
        'request_rejected' => '申請却下',        // Request Rejected
        'approval_request' => '承認申請',        // Approval Request
        'workflow_notification' => 'ワークフロー通知', // Workflow Notification
        'approval_completed' => '承認完了',      // Approval Completed
    ],



];
