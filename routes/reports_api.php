<?php

use App\Http\Controllers\Reports\ApiController;
use Illuminate\Support\Facades\Route;

//ФОС для Сотрудников!
Route::prefix('v1.0')->group(function (){

    Route::post('try-auth', [ApiController::class, 'tryAuth']);
    Route::post('submit/{direction_code}', [ApiController::class, 'submit']);
});
