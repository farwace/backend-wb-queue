<?php

use App\Http\Controllers\Workers\ApiController;
use Illuminate\Support\Facades\Route;


Route::prefix('v1.0')->group(function (){
    Route::get('/auth', [ApiController::class, 'createOrFindUser']);
});
