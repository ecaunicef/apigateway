<?php

return [
    'dataprocessing'   =>  [
        'base_uri'  =>  env('DATAPROCESSING_SERVICE_BASE_URL'),
        'secret'  =>  env('DATAPROCESSING_SERVICE_SECRET'),
    ],

    'dataretrieval'   =>  [
        'base_uri'  =>  env('DATARETRIEVAL_SERVICE_BASE_URL'),
        'secret'  =>  env('DATARETRIEVAL_SERVICE_SECRET'),
    ],
    'chat' =>  [
        'base_uri'  =>  env('CHAT_SERVICE_BASE_URL'),
        'secret'  =>  env('SDMX_SERVICE_SECRET'),
    ],
];
