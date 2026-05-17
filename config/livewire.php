<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Temporary File Upload disk
    |--------------------------------------------------------------------------
    |
    | Force Livewire's temporary uploads onto the local disk so the app never
    | tries to instantiate an S3 driver just for short-lived form uploads
    | (e.g. the recitation-questions Excel import). This is a partial override
    | — Livewire merges its other defaults around it.
    |
    | If the host actually needs S3 for tmp uploads, set
    | LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK=s3 in .env *and* install
    | league/flysystem-aws-s3-v3.
    */
    'temporary_file_upload' => [
        'disk' => env('LIVEWIRE_TEMPORARY_FILE_UPLOAD_DISK', 'local'),
    ],
];
