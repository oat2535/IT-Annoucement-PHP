<?php

use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\ChangeRequestController;
use App\Http\Controllers\Api\LookupController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Note: This application is an internal hospital tool with NO authentication/login requirements.

// --- API Version 1 (Modern RESTful standard) ---
Route::prefix('v1')->group(function () {
    // Lookups
    Route::get('/lookups', [LookupController::class, 'index']);

    // Announcements
    Route::get('/announcements', [AnnouncementController::class, 'index']);
    Route::post('/announcements', [AnnouncementController::class, 'store']);
    Route::get('/announcements/export/csv', [AnnouncementController::class, 'exportCsv']);
    Route::match(['get', 'post'], '/announcements/check-change-no', [AnnouncementController::class, 'checkChangeNo']);
    Route::post('/announcements/save-image', [AnnouncementController::class, 'saveExportedImage']);
    Route::get('/announcements/{id}', [AnnouncementController::class, 'show']);

    // Change Requests
    Route::get('/change-requests', [ChangeRequestController::class, 'index']);
    Route::post('/change-requests', [ChangeRequestController::class, 'store']);
    Route::get('/change-requests/export/csv', [ChangeRequestController::class, 'exportCsv']);
    Route::get('/change-requests/{id}/preview-document', [ChangeRequestController::class, 'previewDocument']);
    Route::get('/change-requests/{id}', [ChangeRequestController::class, 'show']);
});

// --- Legacy Django Compatibility Aliases ---
Route::post('/save_announcement/', [AnnouncementController::class, 'store']);
Route::match(['get', 'post'], '/check_change_no/', [AnnouncementController::class, 'checkChangeNo']);
Route::match(['get', 'post'], '/get_announcement/', function (Request $request, AnnouncementController $controller) {
    $id = $request->input('id') ?: $request->input('change_no') ?: $request->query('change_no');
    return $controller->show((string)$id);
});
Route::get('/export_csv/', [AnnouncementController::class, 'exportCsv']);
Route::post('/save_exported_image/', [AnnouncementController::class, 'saveExportedImage']);

Route::post('/save_change_request/', [ChangeRequestController::class, 'store']);
Route::match(['get', 'post'], '/get_change_request/', function (Request $request, ChangeRequestController $controller) {
    $id = $request->input('id') ?: $request->input('request_no') ?: $request->query('request_no');
    return $controller->show((string)$id);
});
Route::get('/preview_document/', function (Request $request, ChangeRequestController $controller) {
    $id = $request->input('id') ?: $request->input('request_no') ?: $request->query('request_no');
    return $controller->previewDocument((string)$id);
});
Route::get('/export_change_request_csv/', [ChangeRequestController::class, 'exportCsv']);
