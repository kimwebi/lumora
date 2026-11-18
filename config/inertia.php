<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pages
    |--------------------------------------------------------------------------
    |
    | Lumora keeps page components inside feature folders, so Inertia is
    | pointed at the whole JS directory. The frontend resolver uses the same
    | glob (./features/**\/pages/*.vue).
    |
    */

    'pages' => [
        'ensure_pages_exist' => true,

        'paths' => [
            resource_path('js'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],
    ],

];
