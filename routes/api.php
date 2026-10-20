<?php

use Illuminate\Http\Request;
use App\Http\Controllers\CustomerDisplayCompanionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

// Dedicated limits avoid the API's shared-IP limit across multiple POS registers.
Route::prefix('customer-display-companion')->withoutMiddleware('throttle:api')->group(function () {
    Route::post('pair', [CustomerDisplayCompanionController::class, 'pair'])->middleware('throttle:10,1');
    Route::get('state', [CustomerDisplayCompanionController::class, 'state'])->middleware('throttle:display-device');
    Route::get('screen', [CustomerDisplayCompanionController::class, 'screen'])->middleware('throttle:display-device');
});
