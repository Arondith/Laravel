<?php

use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\TicketController;
use App\Http\Controllers\Api\TicketNoteController;
use Illuminate\Support\Facades\Route;

Route::get('/health', fn () => response()->json([
    'status' => 'ok',
    'service' => 'PulseDesk API',
    'timestamp' => now()->toIso8601String(),
]));

Route::get('/dashboard', DashboardController::class);
Route::apiResource('tickets', TicketController::class)->only(['index', 'store', 'show', 'update']);
Route::post('/tickets/{ticket}/notes', [TicketNoteController::class, 'store']);
