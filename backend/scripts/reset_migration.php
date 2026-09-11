<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    Illuminate\Http\Request::capture()
);
$db = $app->make('db')->connection();
$db->statement("DELETE FROM migrations WHERE migration LIKE '%2026_07_30_000001%' OR migration LIKE '%2026_07_30_000002%'");
echo "Removed migration records.\n";
