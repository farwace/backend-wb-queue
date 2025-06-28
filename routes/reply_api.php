<?php

use App\Http\Controllers\Reply\ApiController;
use Illuminate\Support\Facades\Route;

// ФОС для ГРУЗЧИКОВ
Route::prefix('v1.0')->group(function (){

    Route::post('try-auth', [ApiController::class, 'tryAuth']);

});
