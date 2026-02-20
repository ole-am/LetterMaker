<?php

return [
    'routes' => [
        ['name' => 'page#index', 'url' => '/', 'verb' => 'GET'],
        ['name' => 'letter#generate', 'url' => '/api/generate', 'verb' => 'POST'],
        ['name' => 'letter#templates', 'url' => '/api/templates', 'verb' => 'GET'],
        ['name' => 'letter#deleteTemplate', 'url' => '/api/templates/delete', 'verb' => 'POST'],
        ['name' => 'letter#uploadTemplate', 'url' => '/api/templates/upload', 'verb' => 'POST'],
        ['name' => 'letter#resetTemplates', 'url' => '/api/templates/reset', 'verb' => 'POST'],
        ['name' => 'letter#downloadTemplate', 'url' => '/api/templates/download', 'verb' => 'GET'],
    ]
];
