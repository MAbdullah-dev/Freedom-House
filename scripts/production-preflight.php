<?php

/**
 * Read-only production schema inspection.
 *
 * This script selects metadata and counts. It does not migrate, seed, update,
 * or print passwords, tokens, application keys, or payment identifiers.
 *
 * Local:
 *   php scripts/production-preflight.php --dotenv=.env --confirm-read-only --allow-local
 *
 * A non-local host also requires --allow-remote. That flag is for the operator
 * after a backup exists. This script still cannot modify the database.
 */

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "Run this script from the command line.\n");
    exit(1);
}

$options = parseArgs($argv);
if (isset($options['help'])) {
    fwrite(STDOUT, "Usage: php scripts/production-preflight.php --dotenv=.env --confirm-read-only (--allow-local | --allow-remote)\n");
    exit(0);
}

if (! isset($options['confirm-read-only'])) {
    fwrite(STDERR, "Pass --confirm-read-only. This inspection does not migrate or modify data.\n");
    exit(1);
}

foreach (['migrate', 'fresh', 'refresh', 'wipe', 'seed', 'force'] as $blocked) {
    if (isset($options[$blocked])) {
        fwrite(STDERR, "This script does not change the database.\n");
        exit(1);
    }
}

$dotenv = $options['dotenv'] ?? null;
if (! is_string($dotenv) || $dotenv === '') {
    fwrite(STDERR, "Pass --dotenv with the path of the environment file to read.\n");
    exit(1);
}

$config = loadDatabaseConfig($dotenv);
$forbidden = array_values(array_filter([
    $config['password'],
    $config['username'],
    $config['app_key'],
], static fn (string $value): bool => strlen($value) >= 8));

$host = strtolower($config['host']);
$localHost = in_array($host, ['127.0.0.1', 'localhost', '::1'], true);
if ($localHost && ! isset($options['allow-local'])) {
    say('Refusing a local connection without --allow-local.', $forbidden);
    exit(1);
}
if (! $localHost && ! isset($options['allow-remote'])) {
    say('Refusing a non-local connection without --allow-remote.', $forbidden);
    exit(1);
}
if ($localHost && isset($options['allow-remote']) && ! isset($options['allow-local'])) {
    say('This host is local. Pass --allow-local.', $forbidden);
    exit(1);
}

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $config['host'], $config['port'], $config['database']),
        $config['username'],
        $config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec('SET SESSION TRANSACTION READ ONLY');
} catch (PDOException $exception) {
    $message = $exception->getMessage();
    foreach ($forbidden as $secret) {
        $message = str_replace($secret, '[redacted]', $message);
    }
    fwrite(STDERR, "Database connection failed: {$message}\n");
    exit(1);
}

say('Stellix Soft read-only preflight', $forbidden);
say('writes: none', $forbidden);
say('target: '.($localHost ? 'local' : 'remote'), $forbidden);
say('host: '.$config['host'], $forbidden);
say('database: '.$config['database'], $forbidden);

$readOnly = scalar($pdo, 'SELECT @@SESSION.transaction_read_only');
say('session_read_only: '.($readOnly === '1' || $readOnly === 1 ? 'yes' : 'no'), $forbidden);
if ($readOnly !== '1' && $readOnly !== 1) {
    say('The session is not read-only, so inspection stopped.', $forbidden);
    exit(1);
}

$version = (string) scalar($pdo, 'SELECT VERSION()');
say('mysql_version: '.$version, $forbidden);
$currentDatabase = (string) scalar($pdo, 'SELECT DATABASE()');
say('selected_database: '.$currentDatabase, $forbidden);
if ($currentDatabase !== $config['database']) {
    say('Selected database does not match the requested database. Inspection stopped.', $forbidden);
    exit(1);
}

$expectedTables = [
    'roles', 'users', 'bookings', 'payments', 'migrations', 'jobs', 'job_batches',
    'failed_jobs', 'sessions', 'cache', 'cache_locks', 'password_resets',
    'password_reset_tokens', 'graph_sync_outbox', 'checkout_snapshots',
    'stripe_events', 'stripe_refund_notices',
];

say('tables:', $forbidden);
foreach ($expectedTables as $table) {
    $present = tableExists($pdo, $currentDatabase, $table);
    say('  '.$table.': '.($present ? 'present' : 'absent'), $forbidden);
}

$modernization = [
    '2026_11_14_120000_add_verified_checkout_fulfillment',
    '2026_11_14_130000_preserve_payment_records',
    '2026_11_14_140000_add_microsoft_identity_key',
    '2026_11_16_180000_encrypt_microsoft_refresh_tokens',
    '2026_11_16_181000_create_password_reset_tokens_table',
    '2026_11_16_190000_create_graph_sync_outbox_table',
    '2026_11_16_191000_make_users_role_id_default_null',
];

$recorded = [];
if (tableExists($pdo, $currentDatabase, 'migrations')) {
    $statement = $pdo->query('SELECT migration FROM migrations ORDER BY id');
    if ($statement !== false) {
        foreach ($statement->fetchAll(PDO::FETCH_COLUMN) as $migration) {
            $recorded[] = (string) $migration;
        }
    }
}
say('migrations_recorded: '.count($recorded), $forbidden);
say('modernization:', $forbidden);
foreach ($modernization as $migration) {
    say('  '.$migration.': '.(in_array($migration, $recorded, true) ? 'recorded' : 'not_recorded'), $forbidden);
}

$blockers = 0;

if (tableExists($pdo, $currentDatabase, 'roles') && columnExists($pdo, $currentDatabase, 'roles', 'role_name')) {
    say('roles:', $forbidden);
    $statement = $pdo->query('SELECT id, role_name FROM roles ORDER BY id');
    $roles = $statement !== false ? $statement->fetchAll() : [];
    if ($roles === []) {
        say('  none', $forbidden);
        $blockers++;
    }
    foreach ($roles as $role) {
        $count = 0;
        if (tableExists($pdo, $currentDatabase, 'users') && columnExists($pdo, $currentDatabase, 'users', 'role_id')) {
            $countStatement = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role_id = ?');
            $countStatement->execute([(int) $role['id']]);
            $count = (int) $countStatement->fetchColumn();
        }
        say('  id='.(int) $role['id'].' name='.preg_replace('/[^A-Za-z0-9 _-]/', '', (string) $role['role_name']).' users='.$count, $forbidden);
    }
    if (columnExists($pdo, $currentDatabase, 'users', 'role_id')) {
        $nullRoles = (int) scalar($pdo, 'SELECT COUNT(*) FROM users WHERE role_id IS NULL');
        $unknown = (int) scalar($pdo, 'SELECT COUNT(*) FROM users u LEFT JOIN roles r ON r.id = u.role_id WHERE u.role_id IS NOT NULL AND r.id IS NULL');
        say('users_with_null_role: '.$nullRoles, $forbidden);
        say('users_with_unknown_role: '.$unknown, $forbidden);
        $column = columnMeta($pdo, $currentDatabase, 'users', 'role_id');
        say('role_id_nullable: '.($column['IS_NULLABLE'] ?? 'unknown'), $forbidden);
        say('role_id_default: '.(($column['COLUMN_DEFAULT'] ?? null) === null ? 'null' : 'present'), $forbidden);
        if ($unknown > 0) {
            $blockers++;
        }
    }
}

say('duplicates:', $forbidden);
$blockers += reportDuplicateGroups(
    $pdo,
    $currentDatabase,
    'bookings',
    ['user_id', 'course_id', 'class_id', 'event_id', 'payment_status'],
    'paid_or_free_booking_groups',
    "SELECT COUNT(*) FROM (
        SELECT user_id, course_id, class_id, event_id
        FROM bookings
        WHERE payment_status IN ('paid', 'free') AND user_id IS NOT NULL
        GROUP BY user_id, course_id, class_id, event_id
        HAVING COUNT(*) > 1
    ) duplicate_groups",
    $forbidden
);
$blockers += reportDuplicateGroups(
    $pdo,
    $currentDatabase,
    'payments',
    ['stripe_payment_intent_id'],
    'stripe_payment_intent_groups',
    "SELECT COUNT(*) FROM (
        SELECT stripe_payment_intent_id
        FROM payments
        WHERE stripe_payment_intent_id IS NOT NULL AND stripe_payment_intent_id <> ''
        GROUP BY stripe_payment_intent_id
        HAVING COUNT(*) > 1
    ) duplicate_groups",
    $forbidden
);
$blockers += reportDuplicateGroups(
    $pdo,
    $currentDatabase,
    'payments',
    ['transaction_id'],
    'legacy_transaction_id_groups',
    "SELECT COUNT(*) FROM (
        SELECT transaction_id
        FROM payments
        WHERE transaction_id IS NOT NULL AND transaction_id <> ''
        GROUP BY transaction_id
        HAVING COUNT(*) > 1
    ) duplicate_groups",
    $forbidden
);
$blockers += reportDuplicateGroups(
    $pdo,
    $currentDatabase,
    'users',
    ['microsoft_id'],
    'microsoft_id_groups',
    "SELECT COUNT(*) FROM (
        SELECT microsoft_id
        FROM users
        WHERE microsoft_id IS NOT NULL AND microsoft_id <> ''
        GROUP BY microsoft_id
        HAVING COUNT(*) > 1
    ) duplicate_groups",
    $forbidden
);

if (tableExists($pdo, $currentDatabase, 'users') && columnExists($pdo, $currentDatabase, 'users', 'refresh_token')) {
    $plain = 0;
    $encrypted = 0;
    $statement = $pdo->query("SELECT refresh_token FROM users WHERE refresh_token IS NOT NULL AND refresh_token <> ''");
    if ($statement !== false) {
        while (($token = $statement->fetchColumn()) !== false) {
            $token = (string) $token;
            $forbidden[] = $token;
            if (looksEncrypted($token)) {
                $encrypted++;
            } else {
                $plain++;
            }
            unset($token);
        }
    }
    say('refresh_tokens_nonempty: '.($plain + $encrypted), $forbidden);
    say('refresh_tokens_encrypted_shape: '.$encrypted, $forbidden);
    say('refresh_tokens_plaintext_shape: '.$plain, $forbidden);
} else {
    say('refresh_tokens_nonempty: column_absent', $forbidden);
}

if (tableExists($pdo, $currentDatabase, 'users') && columnExists($pdo, $currentDatabase, 'users', 'password_reset_token')) {
    $resetTokens = (int) scalar($pdo, "SELECT COUNT(*) FROM users WHERE password_reset_token IS NOT NULL AND password_reset_token <> ''");
    say('legacy_password_reset_tokens: '.$resetTokens, $forbidden);
}

if (tableExists($pdo, $currentDatabase, 'users') && columnExists($pdo, $currentDatabase, 'users', 'microsoft_identity_key')) {
    $linked = (int) scalar($pdo, "SELECT COUNT(*) FROM users WHERE microsoft_identity_key IS NOT NULL AND microsoft_identity_key <> ''");
    say('microsoft_identity_keys: '.$linked, $forbidden);
} else {
    say('microsoft_identity_keys: column_absent', $forbidden);
}

say('lock_risks:', $forbidden);
say('  checkout_fulfillment: bookings scan and payments alter when 2026_11_14_120000 is not recorded', $forbidden);
say('  payment_identifiers: payments index rebuild when 2026_11_14_130000 is not recorded', $forbidden);
say('  microsoft_identity: users backfill when 2026_11_14_140000 is not recorded', $forbidden);
say('  refresh_token_encryption: users row updates when 2026_11_16_180000 is not recorded', $forbidden);
say('  role_default: users metadata change when 2026_11_16_191000 is not recorded', $forbidden);
say('recovery: restore a verified backup. This script does not roll back or migrate.', $forbidden);

if ($blockers > 0) {
    say('result: blockers '.$blockers, $forbidden);
    exit(3);
}

say('result: ok', $forbidden);
exit(0);

/**
 * @param array<int, string> $argv
 * @return array<string, string|true>
 */
function parseArgs(array $argv): array
{
    $options = [];
    foreach (array_slice($argv, 1) as $argument) {
        if (! str_starts_with($argument, '--')) {
            continue;
        }
        $argument = substr($argument, 2);
        [$name, $value] = array_pad(explode('=', $argument, 2), 2, true);
        $options[$name] = $value;
    }

    return $options;
}

/**
 * @return array{host: string, port: int, database: string, username: string, password: string, app_key: string}
 */
function loadDatabaseConfig(string $path): array
{
    if (! is_file($path)) {
        fwrite(STDERR, "Environment file not found.\n");
        exit(1);
    }

    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        fwrite(STDERR, "Environment file could not be read.\n");
        exit(1);
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#') || ! str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        if (! in_array($key, ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'APP_KEY'], true)) {
            continue;
        }
        $value = trim($value);
        if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
            $value = substr($value, 1, -1);
        }
        $values[$key] = $value;
    }

    foreach (['DB_HOST', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD'] as $required) {
        if (! isset($values[$required]) || $values[$required] === '') {
            fwrite(STDERR, "The environment file is missing a required database setting.\n");
            exit(1);
        }
    }

    return [
        'host' => $values['DB_HOST'],
        'port' => isset($values['DB_PORT']) ? (int) $values['DB_PORT'] : 3306,
        'database' => $values['DB_DATABASE'],
        'username' => $values['DB_USERNAME'],
        'password' => $values['DB_PASSWORD'],
        'app_key' => $values['APP_KEY'] ?? '',
    ];
}

function say(string $line, array &$forbidden): void
{
    foreach ($forbidden as $secret) {
        if ($secret !== '' && strlen($secret) >= 8 && str_contains($line, $secret)) {
            fwrite(STDERR, "Refusing to print a secret.\n");
            exit(1);
        }
    }
    if (preg_match('/(sk_live_|sk_test_|whsec_|base64:|eyJ[A-Za-z0-9_-]{20,})/', $line) === 1) {
        fwrite(STDERR, "Refusing to print a secret.\n");
        exit(1);
    }
    echo $line, PHP_EOL;
}

function scalar(PDO $pdo, string $sql): mixed
{
    $statement = $pdo->query($sql);
    if ($statement === false) {
        return null;
    }

    return $statement->fetchColumn();
}

function tableExists(PDO $pdo, string $database, string $table): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?'
    );
    $statement->execute([$database, $table]);

    return (int) $statement->fetchColumn() > 0;
}

function columnExists(PDO $pdo, string $database, string $table, string $column): bool
{
    $statement = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $statement->execute([$database, $table, $column]);

    return (int) $statement->fetchColumn() > 0;
}

/**
 * @return array<string, mixed>
 */
function columnMeta(PDO $pdo, string $database, string $table, string $column): array
{
    $statement = $pdo->prepare(
        'SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $statement->execute([$database, $table, $column]);
    $row = $statement->fetch();

    return is_array($row) ? $row : [];
}

/**
 * @param array<int, string> $requiredColumns
 * @param array<int, string> $forbidden
 */
function reportDuplicateGroups(PDO $pdo, string $database, string $table, array $requiredColumns, string $label, string $sql, array &$forbidden): int
{
    foreach ($requiredColumns as $column) {
        if (! tableExists($pdo, $database, $table) || ! columnExists($pdo, $database, $table, $column)) {
            say('  '.$label.': skipped_column_or_table_absent', $forbidden);

            return 0;
        }
    }

    $count = (int) scalar($pdo, $sql);
    say('  '.$label.': '.$count, $forbidden);

    return $count > 0 ? 1 : 0;
}

function looksEncrypted(string $value): bool
{
    $decoded = base64_decode($value, true);
    if ($decoded === false) {
        return false;
    }
    $payload = json_decode($decoded, true);

    return is_array($payload)
        && isset($payload['iv'], $payload['value'], $payload['mac']);
}
