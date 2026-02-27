<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Route Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the dashboard routes prefix and middleware.
    |
    */
    'routes' => [
        'prefix' => env('TYRO_DASHBOARD_PREFIX', 'dashboard'),
        'middleware' => ['web', 'auth'],
        'name_prefix' => 'tyro-dashboard.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Roles
    |--------------------------------------------------------------------------
    |
    | Users with these roles will have full access to admin features
    | (user management, role management, privilege management, settings).
    |
    */
    'admin_roles' => ['admin', 'super-admin'],

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model to use throughout the dashboard.
    |
    */
    'user_model' => env('TYRO_DASHBOARD_USER_MODEL', 'App\\Models\\User'),

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    |
    | Default pagination settings for lists.
    |
    */
    'pagination' => [
        'users' => 15,
        'roles' => 15,
        'privileges' => 15,
    ],

    /*
    |--------------------------------------------------------------------------
    | Branding
    |--------------------------------------------------------------------------
    |
    | Customize the dashboard appearance.
    |
    */
    'branding' => [
        'app_name' => env('TYRO_DASHBOARD_APP_NAME', env('APP_NAME', 'Laravel')),
        'logo' => env('TYRO_DASHBOARD_LOGO', null),
        'logo_height' => env('TYRO_DASHBOARD_LOGO_HEIGHT', '32px'),
        'favicon' => env('TYRO_DASHBOARD_FAVICON', null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Collapsible Sidebar
    |--------------------------------------------------------------------------
    |
    | Enable or disable the collapsible sidebar feature.
    |
    */
    'collapsible_sidebar' => env('TYRO_DASHBOARD_COLLAPSIBLE_SIDEBAR', true),

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific dashboard features.
    |
    */
    'features' => [
        'user_management' => true,
        'role_management' => true,
        'privilege_management' => true,
        'settings_management' => true,
        'profile_management' => true,
        'activity_log' => false, // Future feature
    ],

    /*
    |--------------------------------------------------------------------------
    | Protected Resources
    |--------------------------------------------------------------------------
    |
    | Resources that cannot be deleted through the dashboard.
    |
    */
    'protected' => [
        'roles' => ['admin', 'super-admin', 'user'],
        'users' => [], // Add user IDs that cannot be deleted
    ],

    /*
    |--------------------------------------------------------------------------
    | Dashboard Widgets
    |--------------------------------------------------------------------------
    |
    | Configure which widgets appear on the dashboard home.
    |
    */
    'widgets' => [
        'stats' => true,
        'recent_users' => true,
        'role_distribution' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    |
    | Configure dashboard notifications behavior.
    |
    */
    'notifications' => [
        'show_flash_messages' => true,
        'auto_dismiss_seconds' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dynamic Resources (CRUD)
    |--------------------------------------------------------------------------
    |
    | Define your resources here to automatically generate CRUD interfaces.
    |
    */
    // 'resources' => [
    //     // Example:
    //     // 'posts' => [
    //     //     'model' => 'App\Models\Post',
    //     //     'title' => 'Posts',
    //     //     'icon' => '<svg>...</svg>', // Optional SVG icon
    //     //     'fields' => [
    //     //         'title' => ['type' => 'text', 'label' => 'Title', 'rules' => 'required'],
    //     //         'content' => ['type' => 'textarea', 'label' => 'Content'],
    //     //     ],
    //     // ],
    // ],
    'resources' => [
        'campaigns' => [
            'model' => 'App\Models\Campaign',
            'title' => 'Campaigns',
            'singular' => 'Campaign',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3a1 1 0 011 1v1.07a8.001 8.001 0 016.93 6.93H20a1 1 0 110 2h-1.07a8.001 8.001 0 01-6.93 6.93V22a1 1 0 11-2 0v-1.07a8.001 8.001 0 01-6.93-6.93H2a1 1 0 110-2h1.07a8.001 8.001 0 016.93-6.93V4a1 1 0 011-1z"></path></svg>',
            'roles' => ['admin', 'manager', 'editor'],
            'fields' => [
                'brand_id' => [
                    'type' => 'select',
                    'label' => 'Brand',
                    'relationship' => 'brand',
                    'option_label' => 'name',
                    'rules' => 'required',
                ],
                'department' => ['type' => 'text', 'label' => 'Department', 'rules' => 'nullable|max:100'],
                'category' => ['type' => 'text', 'label' => 'Category', 'rules' => 'nullable|max:100'],
                'name' => ['type' => 'text', 'label' => 'Campaign Name', 'rules' => 'required|max:255'],
                'objective' => ['type' => 'text', 'label' => 'Objective', 'rules' => 'nullable|max:255'],
                'budget' => ['type' => 'number', 'label' => 'Budget'],
                'kpi_target' => ['type' => 'number', 'label' => 'KPI Target'],
                'start_at' => ['type' => 'datetime-local', 'label' => 'Start At'],
                'end_at' => ['type' => 'datetime-local', 'label' => 'End At'],
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        'planned' => 'Planned',
                        'active' => 'Active',
                        'paused' => 'Paused',
                        'complete' => 'Complete',
                    ],
                    'rules' => 'required|in:planned,active,paused,complete',
                ],
            ],
            'list_columns' => ['name', 'brand_id', 'department', 'category', 'status', 'start_at', 'end_at'],
            'search' => ['name', 'department', 'category', 'objective'],
        ],

        'brands' => [
            'model' => 'App\Models\Brand',
            'title' => 'Brands',
            'singular' => 'Brand',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>',
            'roles' => ['admin', 'manager'],
            'fields' => [
                'name' => ['type' => 'text', 'label' => 'Brand Name', 'rules' => 'required|max:255'],
                'slug' => [
                    'type' => 'text',
                    'label' => 'Slug',
                    'rules' => 'required|unique:brands,slug',
                ],
                'timezone' => [
                    'type' => 'select',
                    'label' => 'Timezone',
                    'options' => array_combine(timezone_identifiers_list(), timezone_identifiers_list()),
                    'rules' => 'required',
                ],
                'status' => ['type' => 'boolean', 'label' => 'Active'],
            ],
            'list_columns' => ['name', 'slug', 'timezone', 'status', 'created_at'],
            'search' => ['name', 'slug'],
        ],

        /*
        'connected-social-accounts' => [
            'model' => 'App\Models\ConnectedSocialAccount',
            'title' => 'Connected Accounts',
            'singular' => 'Connected Account',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>',
            'roles' => ['admin'],
            'fields' => [
                'brand_id' => [
                    'type' => 'select',
                    'label' => 'Brand',
                    'relationship' => 'brand',
                    'option_label' => 'name',
                    'rules' => 'required',
                ],
                'platform' => [
                    'type' => 'select',
                    'label' => 'Platform',
                    'options' => ['facebook' => 'Facebook', 'linkedin' => 'LinkedIn'],
                    'rules' => 'required',
                ],
                'display_name' => ['type' => 'text', 'label' => 'Display Name', 'readonly' => true],
                'external_account_id' => ['type' => 'text', 'label' => 'Account ID', 'readonly' => true],
                'status' => [
                    'type' => 'badge',
                    'label' => 'Status',
                    'colors' => [
                        'connected' => 'success',
                        'expired' => 'warning',
                        'revoked' => 'danger',
                    ],
                ],
            ],
            'list_columns' => ['brand_id', 'platform', 'display_name', 'status', 'created_at'],
            'readonly' => [],  // Empty = admin has full access; add roles here for read-only access
        ],
        */

        'posts' => [
            'model' => 'App\Models\Post',
            'title' => 'Posts',
            'singular' => 'Post',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>',
            'roles' => ['admin', 'editor', 'approver'],
            'fields' => [
                'brand_id' => [
                    'type' => 'select',
                    'label' => 'Brand',
                    'relationship' => 'brand',
                    'option_label' => 'name',
                    'rules' => 'required',
                ],
                'campaign_id' => [
                    'type' => 'select',
                    'label' => 'Campaign',
                    'relationship' => 'campaign',
                    'option_label' => 'name',
                    'rules' => 'nullable',
                ],
                'title' => ['type' => 'text', 'label' => 'Internal Title', 'rules' => 'required|max:255'],
                'base_text' => ['type' => 'textarea', 'label' => 'Base Content', 'rows' => 5],
                'target_url' => ['type' => 'text', 'label' => 'Target URL'],
                'utm_template' => ['type' => 'text', 'label' => 'UTM Template'],
                'status' => [
                    'type' => 'select',
                    'label' => 'Status',
                    'options' => [
                        'draft' => 'Draft',
                        'pending' => 'Pending Approval',
                        'approved' => 'Approved',
                        'scheduled' => 'Scheduled',
                        'publishing' => 'Publishing',
                        'published' => 'Published',
                        'failed' => 'Failed',
                    ],
                    'readonly' => true,
                ],
                'approval_due_at' => ['type' => 'datetime-local', 'label' => 'Approval Due At', 'readonly' => true],
            ],
            'list_columns' => ['title', 'brand_id', 'campaign_id', 'status', 'approval_due_at', 'created_by', 'created_at'],
            'search' => ['title', 'base_text'],
            'actions' => [],
        ],


        'post-variants' => [
            'model' => 'App\Models\PostVariant',
            'title' => 'Post Variants',
            'singular' => 'Post Variant',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"></path></svg>',
            'roles' => ['admin', 'editor'],
            'fields' => [
                'post_id' => [
                    'type' => 'select',
                    'label' => 'Post',
                    'relationship' => 'post',
                    'option_label' => 'title',
                    'rules' => 'required',
                ],
                'connected_social_account_id' => [
                    'type' => 'select',
                    'label' => 'Social Account',
                    'relationship' => 'connectedSocialAccount',
                    'option_label' => 'display_name',
                    'rules' => 'required',
                ],
                'platform' => [
                    'type' => 'select',
                    'label' => 'Platform',
                    'options' => ['facebook' => 'Facebook', 'linkedin' => 'LinkedIn'],
                    'rules' => 'required',
                ],
                'text_override' => ['type' => 'textarea', 'label' => 'Custom Text', 'rows' => 5],
                'scheduled_at' => ['type' => 'datetime-local', 'label' => 'Schedule Time'],
                'status' => [
                    'type' => 'badge',
                    'label' => 'Status',
                    'colors' => [
                        'draft' => 'secondary',
                        'scheduled' => 'info',
                        'publishing' => 'warning',
                        'published' => 'success',
                        'failed' => 'danger',
                    ],
                ],
            ],
            'list_columns' => ['post_id', 'platform', 'scheduled_at', 'status'],
            'actions' => [],
        ],

        'publication-attempts' => [
            'model' => 'App\Models\PublicationAttempt',
            'title' => 'Publication Attempts',
            'singular' => 'Publication Attempt',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
            'roles' => ['admin'],
            'readonly' => [],  // Empty = admin has full access
            'fields' => [
                'post_variant_id' => [
                    'type' => 'select',
                    'label' => 'Post Variant',
                    'relationship' => 'postVariant',
                    'option_label' => 'id',
                ],
                'attempt_no' => ['type' => 'number', 'label' => 'Attempt #'],
                'result' => [
                    'type' => 'badge',
                    'label' => 'Result',
                    'colors' => [
                        'success' => 'success',
                        'fail' => 'danger',
                    ],
                ],
                'external_post_id' => ['type' => 'text', 'label' => 'External Post ID'],
                'error_message' => ['type' => 'textarea', 'label' => 'Error Message', 'rows' => 3],
                'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
            ],
            'list_columns' => ['post_variant_id', 'attempt_no', 'result', 'created_at'],
        ],

        'metrics' => [
            'model' => 'App\Models\MetricsSnapshot',
            'title' => 'Metrics',
            'singular' => 'Metric',
            'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>',
            'roles' => ['admin', 'manager'],
            'readonly' => ['manager'],  // Manager is read-only, admin has full access
            'fields' => [
                'post_variant_id' => [
                    'type' => 'select',
                    'label' => 'Post Variant',
                    'relationship' => 'postVariant',
                    'option_label' => 'id',
                ],
                'likes' => ['type' => 'number', 'label' => 'Likes'],
                'comments' => ['type' => 'number', 'label' => 'Comments'],
                'shares' => ['type' => 'number', 'label' => 'Shares'],
                'impressions' => ['type' => 'number', 'label' => 'Impressions'],
                'clicks' => ['type' => 'number', 'label' => 'Clicks'],
                'captured_at' => ['type' => 'datetime', 'label' => 'Captured At'],
            ],
            'list_columns' => ['post_variant_id', 'likes', 'comments', 'shares', 'captured_at'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Resource UI Settings
    |--------------------------------------------------------------------------
    |
    | Configure the appearance and behavior of resource forms and lists.
    |
    */
    'resource_ui' => [
        'show_global_errors' => env('TYRO_SHOW_GLOBAL_ERRORS', true),
        'show_field_errors' => env('TYRO_SHOW_FIELD_ERRORS', true),
    ],
];
