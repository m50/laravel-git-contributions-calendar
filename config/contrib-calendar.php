<?php

return [
    'gitlab' => [
        'key' => env('GITLAB_TOKEN'),
        'url' => env('GITLAB_BASE_URI'),
    ],

    'github' => [
        'key' => env('GITHUB_TOKEN'),
        'url' => env('GITHUB_BASE_URI'),
        'username' => env('GITHUB_USERNAME'),
    ],

    'heatmap-class' => [
        'zero' => '',
        'low' => '',
        'medium' => '',
        'high' => '',
        'very-high' => '',
    ],
];
