<?php

use App\Services\Microsoft\MicrosoftIdentityStore;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = config('database.connections.mysql.database');
if ($database !== 'lms_testing') {
    fwrite(STDERR, "refusing database {$database}\n");
    exit(2);
}

$payload = json_decode((string) file_get_contents($argv[1] ?? ''), true);
if (! is_array($payload)) {
    fwrite(STDERR, "missing payload\n");
    exit(2);
}

$status = app(MicrosoftIdentityStore::class)->link(
    (int) $payload['user_id'],
    (string) $payload['tid'],
    (string) $payload['oid'],
    null,
    null
);

echo $status;
