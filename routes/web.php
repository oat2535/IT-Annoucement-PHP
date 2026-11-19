<?php

use App\Http\Controllers\Web\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'portal'])->name('portal');
Route::get('/announcement', [PortalController::class, 'announcement'])->name('announcement');
Route::get('/report', [PortalController::class, 'report'])->name('report');
Route::get('/change-request', [PortalController::class, 'changeRequest'])->name('change_request');
Route::get('/change-request-report', [PortalController::class, 'changeRequestReport'])->name('change_request_report');

// Diagnostic endpoint for container & database verification
Route::get('/diagnose', function () {
    $results = [];

    $results['php_version'] = PHP_VERSION;
    $results['extensions'] = [
        'pdo_pgsql' => extension_loaded('pdo_pgsql'),
        'pgsql' => extension_loaded('pgsql'),
        'gd' => extension_loaded('gd'),
        'zip' => extension_loaded('zip'),
    ];

    $results['storage'] = [
        'logs_writable' => is_writable(storage_path('logs')),
        'sessions_writable' => is_writable(storage_path('framework/sessions')),
        'views_writable' => is_writable(storage_path('framework/views')),
        'app_public_writable' => is_writable(storage_path('app/public')),
        'public_storage_symlink' => file_exists(public_path('storage')),
    ];

    $results['config'] = [
        'app_env' => config('app.env'),
        'app_debug' => config('app.debug'),
        'app_key_set' => !empty(config('app.key')),
        'session_driver' => config('session.driver'),
        'cache_store' => config('cache.default'),
        'queue_connection' => config('queue.default'),
        'log_channel' => config('logging.default'),
    ];

    try {
        \Illuminate\Support\Facades\DB::connection()->getPdo();
        $results['database'] = [
            'status' => 'CONNECTED',
            'database' => \Illuminate\Support\Facades\DB::connection()->getDatabaseName(),
            'host' => config('database.connections.pgsql.host'),
            'port' => config('database.connections.pgsql.port'),
            'schema' => config('database.connections.pgsql.search_path'),
            'announcements_count' => \App\Models\Announcement::count(),
            'change_requests_count' => \App\Models\ChangeRequest::count(),
        ];
    } catch (\Throwable $e) {
        $results['database'] = [
            'status' => 'FAILED',
            'error' => $e->getMessage(),
            'host' => config('database.connections.pgsql.host'),
            'port' => config('database.connections.pgsql.port'),
            'schema' => config('database.connections.pgsql.search_path'),
        ];
    }

    $statusCode = (isset($results['database']['status']) && $results['database']['status'] === 'CONNECTED') ? 200 : 500;
    return response()->json($results, $statusCode, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
})->name('diagnose');
