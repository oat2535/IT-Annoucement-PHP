<?php

use App\Http\Controllers\Web\PortalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [PortalController::class, 'portal'])->name('portal');
Route::get('/announcement', [PortalController::class, 'announcement'])->name('announcement');
Route::get('/report', [PortalController::class, 'report'])->name('report');
Route::get('/change-request', [PortalController::class, 'changeRequest'])->name('change_request');
Route::get('/change-request-report', [PortalController::class, 'changeRequestReport'])->name('change_request_report');
