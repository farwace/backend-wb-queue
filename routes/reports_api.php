<?php

use App\Http\Controllers\Reports\ApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1.0')->group(function (){

    Route::post('try-auth', [ApiController::class, 'tryAuth']);
});
