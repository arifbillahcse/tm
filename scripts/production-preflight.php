#!/usr/bin/env php
<?php
declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$root = dirname(__DIR__);
require $root.'/vendor/autoload.php';
$app = require $root.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$checks = [];
$check = static function (string $name, bool $ok, string $detail = '') use (&$checks): void {
    $checks[] = [$name, $ok, $detail];
};

$check('Production environment', app()->environment('production'), 'APP_ENV must be production');
$check('Debug disabled', config('app.debug') === false, 'APP_DEBUG must be false');
$url = (string) config('app.url');
$check('HTTPS application URL', (bool) preg_match('#^https://[^/]+$#i', rtrim($url, '/')), 'APP_URL must use HTTPS and a valid host');
$check('Application key configured', is_string(config('app.key')) && strlen((string) config('app.key')) >= 32, 'APP_KEY must be present; never rotate it casually on an existing installation');
$check('Secure session cookies', config('session.secure') === true && config('session.http_only') === true, 'SESSION_SECURE_COOKIE=true and HttpOnly are required');
$check('Persistent queue configured', config('queue.default') !== 'sync', 'QUEUE_CONNECTION must not be sync');
$check('Private storage writable', is_dir(storage_path('app/private')) && is_writable(storage_path('app/private')), 'storage/app/private must exist and be writable');
$check('Framework storage writable', is_writable(storage_path('framework')) && is_writable(storage_path('logs')), 'storage/framework and storage/logs must be writable');

try {
    DB::connection()->select('SELECT 1');
    $check('Database connectivity', true);
    if (config('queue.default') === 'database') {
        $check('Queue tables exist', Schema::hasTable('jobs') && Schema::hasTable('failed_jobs'), 'Run the committed migrations before enabling the worker');
    }
} catch (Throwable $e) {
    $check('Database connectivity', false, 'Database connection or required queue tables failed; details intentionally suppressed');
}

try {
    Illuminate\Support\Facades\Artisan::call('migrate:status', ['--no-ansi' => true]);
    $migrationOutput = Illuminate\Support\Facades\Artisan::output();
    $pending = preg_match('/\bPending\b/i', $migrationOutput) === 1;
    $check('No pending migrations', !$pending, $pending ? 'Run migrations during an approved maintenance window' : '');
} catch (Throwable $e) {
    $check('Migration status', false, 'Unable to inspect migration status; details intentionally suppressed');
}

try {
    Illuminate\Support\Facades\Artisan::call('schedule:list', ['--no-ansi' => true]);
    $check('Scheduler can enumerate tasks', Illuminate\Support\Facades\Artisan::output() !== '');
} catch (Throwable $e) {
    $check('Scheduler can enumerate tasks', false, 'Scheduler configuration could not be loaded');
}

$failed = false;
foreach ($checks as [$name, $ok, $detail]) {
    printf("[%s] %s%s\n", $ok ? 'PASS' : 'FAIL', $name, $detail !== '' ? ' — '.$detail : '');
    $failed = $failed || !$ok;
}
printf("\n%d checks; %d failed.\n", count($checks), count(array_filter($checks, static fn ($item) => !$item[1])));
exit($failed ? 1 : 0);
