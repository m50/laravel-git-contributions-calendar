<?php

return [
    'gitlab' => [
        'key' => env('GITLAB_TOKEN'),
        'url' => env('GITLAB_BASE_URI'),
    ],

    'github' => [
        'key' => env('GITHUB_TOKEN'),
        'url' => env('GITHUB_BASE_URI'),
        'username' => env('GITHUB_USERNAME')
    ],

    'heatmap-class' => [
        'zero' => 'bg-gray-300',
        'low' => 'bg-purple-200',
        'medium' => 'bg-purple-400',
        'high' => 'bg-purple-600',
        'very-high' => 'bg-purple-800',
    ],
];
