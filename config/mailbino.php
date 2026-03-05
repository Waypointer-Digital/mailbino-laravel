<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Token
    |--------------------------------------------------------------------------
    |
    | Your Mailbino API token. Generate one in the Mailbino dashboard under
    | your app's API Tokens tab. Needs at least the 'send' scope.
    |
    */
    'api_token' => env('MAILBINO_API_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The Mailbino API base URL. Override for self-hosted instances.
    |
    */
    'base_url' => env('MAILBINO_URL', 'https://mailbino.com/api'),

    /*
    |--------------------------------------------------------------------------
    | Test Recipient
    |--------------------------------------------------------------------------
    |
    | When set, this address is sent as the test_recipient parameter on every
    | API call. Useful in staging: set this in your .env and all mail goes
    | to this address (requires test mode enabled on the app in Mailbino).
    |
    */
    'test_recipient' => env('MAILBINO_TEST_RECIPIENT'),

];
