<?php

return [
    'temporary_file_upload' => [
        'disk' => 'local',
        'rules' => 'file|mimes:mp4,mov,mp3,wav,quicktime|max:202400', // 200MB in kilobytes
        'directory' => 'livewire-tmp',
        'middleware' => null, // Can be 'none' or null
        'preview_mimes' => [   // Supported file types for temporary pre-signed file URLs
            'video/mp4',
            'video/quicktime',
        ],
        'max_upload_time' => 600, // Max duration (in seconds) before an upload gets invalidated
    ],
];