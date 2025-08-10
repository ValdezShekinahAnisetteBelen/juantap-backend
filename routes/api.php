<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    ProfileController,
    SocialLinkController,
    TemplateController,
    PaymentProofController,
    TemplateUnlockController,
    UserTemplateController
};
use App\Models\User;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Support\Facades\URL;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

// Get authenticated user (for frontend testing maybe)
Route::get('/user', [AuthController::class, 'user'])->middleware('auth:sanctum');

// AUTH ROUTES
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login',    [AuthController::class, 'login']);

// ✅ Resend verification email (requires auth)
Route::post('/email/verification-notification', function (Request $request) {
    if ($request->user()->hasVerifiedEmail()) {
        return response()->json(['message' => 'Already verified'], 400);
    }

    $request->user()->sendEmailVerificationNotification();

    return response()->json(['status' => 'verification-link-sent']);
})->middleware(['auth:sanctum']);

// ✅ Verify email via signed link
Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    // Optional: validate the signed URL manually (already done by 'signed' middleware)
    if (! URL::hasValidSignature($request)) {
        abort(403, 'Invalid or expired verification link.');
    }

    $user = User::findOrFail($id);

    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        abort(403, 'Invalid verification hash.');
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
    }

    return redirect(env('FRONTEND_URL', '/') . '/email-verified-success');
})->middleware(['signed'])->name('verification.verify');



// ✅ Check if email is verified
Route::get('/email/is-verified', function (Request $request) {
    return response()->json([
        'email_verified' => $request->user()->hasVerifiedEmail()
    ]);
})->middleware('auth:sanctum');

// Add to your routes/api.php or routes/web.php
Route::get('/login', function () {
    return response()->json(['message' => 'Login route is handled by frontend.'], 401);
})->name('login');

/*
|--------------------------------------------------------------------------
| Protected Routes (auth:sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', [AuthController::class, 'user']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // PROFILE
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

    // TEMPLATE UNLOCKS
    Route::get('/template-unlocks', [TemplateUnlockController::class, 'index']);
});
Route::middleware('auth:sanctum')->get('/user-profile', [ProfileController::class, 'me']);

Route::middleware('auth:sanctum')->group(function () {
    // Saved templates
    Route::get('/templates/saved', [UserTemplateController::class, 'savedTemplates']);
    Route::post('/templates/saved/{template}', [UserTemplateController::class, 'saveTemplate']);
    Route::delete('/templates/saved/{template}', [UserTemplateController::class, 'unsaveTemplate']);

    // Used templates
    Route::get('/templates/used', [UserTemplateController::class, 'usedTemplates']);
    Route::post('/templates/used/{template}', [UserTemplateController::class, 'useTemplate']);
    Route::delete('/templates/used/{slug}', [UserTemplateController::class, 'unuseTemplate']);
});
Route::post('/payment/submit', [UserTemplateController::class, 'submit'])->middleware('auth:sanctum');;
// routes/api.php
Route::get('/profile/{username}/used-templates', [UserTemplateController::class, 'getUsedTemplate']);
Route::get('/profile/{username}', [ProfileController::class, 'show']);
