<?php
use Illuminate\Support\Facades\Route;


Route::prefix('worker')->group(base_path('routes/workers_api.php'));
Route::prefix('report')->group(base_path('routes/reports_api.php'));
Route::prefix('reply')->group(base_path('routes/reply_api.php'));
