<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\InfluencerController;
use App\Http\Controllers\ChatUnlockController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::group(['middleware' => ['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]], function () {
        Route::get('/me', [AuthController::class, 'getUser']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
    Route::middleware('auth:sanctum', 'ability:' . TokenAbility::ISSUE_ACCESS_TOKEN->value)->group(function () {
        Route::post('/refresh-token', [AuthController::class, 'refreshToken']);
    });
});

Route::group(['middleware' => ['auth:sanctum', 'ability:' . TokenAbility::ACCESS_API->value]], function () {
    Route::post('/purchase-gems', [PaymentController::class, 'purchaseGems']);
    Route::post('/create-intent', [PaymentController::class, 'createIntent']);
    Route::post('/finalize-purchase', [PaymentController::class, 'finalizePurchase']);
    Route::prefix('influencers')->group(function () {
        Route::post('/become', [InfluencerController::class, 'becomeInfluencer']);
        Route::get('/', [InfluencerController::class, 'listInfluencers']);
    });
    Route::prefix('chat')->group(function () {
        Route::post('/unlock', [ChatUnlockController::class, 'unlock']);
    });
});
