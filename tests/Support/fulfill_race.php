<?php

use App\Services\EnrollmentFulfillment;
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
if (! is_array($payload) || ! isset($payload['session']) || ! is_array($payload['session'])) {
    fwrite(STDERR, "missing session payload\n");
    exit(2);
}

$result = app(EnrollmentFulfillment::class)->fulfillFromSession($payload['session']);
echo json_encode([
    'ok' => true,
    'session' => $payload['session']['id'] ?? null,
    'outcome' => $result->outcome,
    'reason' => $result->reason,
    'payment_id' => $result->payment?->id,
    'booking_id' => $result->booking?->id,
])."\n";
