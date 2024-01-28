<?php

return [
    /**
     * Defaults
     */
    'skeleton_version' => '9.0.0',

    'app_name' => env('APP_NAME', 'Laravel'),

    'favicon' => 'favicon.ico',
    'apple_touch_icon' => 'img/apple-touch-icon.png',
    'app_logo' => 'img/logo.png',
    'og_image' => 'img/og-image.png',

    'admin_header_logo' => 'img/admin-header-logo.png',
    'default_avatar' => 'img/user.png',

    'error_logo' => 'img/error-logo.png',

    'default_lat' => env('DEFAULT_LAT', 4.175804),
    'default_lng' => env('DEFAULT_LNG', 73.509337),
    'map_marker' => 'img/map-marker.png',

    'max_upload_file_size' => 1024 * 2, //2MB
    'max_image_file_size' => 1024 * 2, //2MB
    'max_num_files' => 5,

    'default_role' => 'guest',

    'facebook' => 'javaabu',
    'twitter' => 'javaabumv',

    'seo_description' => 'This is a Laravel Skeleton app.',
    'seo_keywords' => 'Code,HTML,CSS,Javascript,PHP,Laravel',
    'google_analytics_id' => '',

    'per_page' => 20,
];
