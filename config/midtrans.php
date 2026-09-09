<?php

return [
    'server_key'   => env('MIDTRANS_SERVER_KEY', ''),
    'client_key'   => env('MIDTRANS_CLIENT_KEY', ''),
    'merchant_id'  => env('MIDTRANS_MERCHANT_ID', ''),
    'is_production' => (bool) env('MIDTRANS_PRODUCTION', false),

    'snap_base_url' => env('MIDTRANS_PRODUCTION', false)
        ? 'https://app.midtrans.com/snap/v1'
        : 'https://app.sandbox.midtrans.com/snap/v1',

    'api_base_url' => env('MIDTRANS_PRODUCTION', false)
        ? 'https://api.midtrans.com/v2'
        : 'https://api.sandbox.midtrans.com/v2',
];
