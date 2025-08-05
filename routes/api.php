<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    ProfileController,
    SocialLinkController,
    TemplateController,
    PaymentProofController,
    TemplateUnlockController
};

Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// AUTH ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // PROFILE ROUTES
    Route::get('/profile', [ProfileController::class, 'show']);
    Route::post('/profile', [ProfileController::class, 'storeOrUpdate']);
    Route::post('/profile/publish', [ProfileController::class, 'publish']);

    // SOCIAL LINKS
    Route::post('/profile/social-links', [SocialLinkController::class, 'store']);
    Route::put('/profile/social-links/{id}', [SocialLinkController::class, 'update']);
    Route::delete('/profile/social-links/{id}', [SocialLinkController::class, 'destroy']);

    // TEMPLATES
    Route::get('/templates', [TemplateController::class, 'index']);
    Route::post('/templates', [TemplateController::class, 'store']); // Admin only
    Route::put('/templates/{id}', [TemplateController::class, 'update']); // Admin only
    Route::delete('/templates/{id}', [TemplateController::class, 'destroy']); // Admin only

    // PAYMENT PROOFS
    Route::post('/payment-proofs', [PaymentProofController::class, 'store']);
    Route::get('/payment-proofs', [PaymentProofController::class, 'index']); // Admin only
    Route::post('/payment-proofs/{id}/approve', [PaymentProofController::class, 'approve']);
    Route::post('/payment-proofs/{id}/decline', [PaymentProofController::class, 'decline']);

    // TEMPLATE UNLOCKS (optional)
    Route::get('/template-unlocks', [TemplateUnlockController::class, 'index']);
});
