<?php

return [
    'admin_email' => env('SIPRED_ADMIN_EMAIL', 'admin@sipred.local'),
    'admin_password' => env('SIPRED_ADMIN_PASSWORD'),
    'iot_api_url' => env('SIPRED_IOT_API_URL', 'http://127.0.0.1:8000'),
    'iot_connectivity_timeout_seconds' => (int) env('SIPRED_IOT_CONNECTIVITY_TIMEOUT', 300),
    'local_crud_without_login' => (bool) env('SIPRED_LOCAL_CRUD_WITHOUT_LOGIN', false),
];
