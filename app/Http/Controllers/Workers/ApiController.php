<?php

namespace App\Http\Controllers\Workers;

use App\Http\Controllers\Controller;
use App\Http\Traits\RespondsWithHttpStatus;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    use RespondsWithHttpStatus;
    public function createOrFindUser(): JsonResponse
    {
        return $this->success(['a' => 'b'],'Success');
    }
}
