<?php

return [
    'disk' => env('COLLECTION_IMPORT_DISK', 'local'),
    'root' => env('COLLECTION_IMPORT_ROOT', 'collection-imports'),

    'collections' => [
        'fish' => 'fish',
        'mollusk' => 'mollusk',
        'non-mollusk' => 'non-mollusk',
        'herps' => 'herps',
        'mammals' => 'mammals',
        'birds' => 'birds',
    ],
];
