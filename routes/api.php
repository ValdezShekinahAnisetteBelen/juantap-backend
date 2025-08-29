<?php

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AdminPaymentController,
    AuthController,
    ProfileController,
    SocialLinkController,
    TemplateController,
    PaymentProofController,
    TemplateUnlockController,
    UserTemplateController,
    StatsController,
    PasswordResetController
};

use App\Models\TemplateUnlock;
use App\Models\User;
use Carbon\Carbon;
use App\Models\Template;
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

Route::get('/admin/users',    [AuthController::class, 'index']);

Route::get('/admin/user/{id}', [AuthController::class, 'getUserById']);


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
    Route::get('/profile/{username}', [ProfileController::class, 'show']);


    // SOCIAL LINKS
    Route::post('/profile/social-links', [SocialLinkController::class, 'store']);
    Route::put('/profile/social-links/{id}', [SocialLinkController::class, 'update']);

    // PAYMENT PROOFS
    Route::post('/payment-proofs', [PaymentProofController::class, 'store']);
    Route::get('/payment-proofs', [PaymentProofController::class, 'index']); // Admin only
    Route::post('/payment-proofs/{id}/approve', [PaymentProofController::class, 'approve']);
    Route::post('/payment-proofs/{id}/decline', [PaymentProofController::class, 'decline']);

    // TEMPLATE UNLOCKS
    Route::get('/template-unlocks', [TemplateUnlockController::class, 'index']);

    Route::get('/stats/users-count', function () {
        return response()->json(['count' => \App\Models\User::count()]);
    });
});
Route::get('/stats/top-templates', [StatsController::class, 'topTemplates']);

    Route::get('/templates', [TemplateController::class, 'index']);
    Route::get('/templates/{slug}', [TemplateController::class, 'show']);
    Route::post('/templates/store', [TemplateController::class, 'store']);
    Route::put('/templates/{id}', [TemplateController::class, 'update']);
    Route::get('/templates/id/{id}', [TemplateController::class, 'showById']);
    Route::get('/templates/check-slug/{slug}', [TemplateController::class, 'checkSlug']);
    Route::post('/templates/{id}/toggle-hidden', [TemplateController::class, 'toggleHidden']);
        
Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/reset-password', [PasswordResetController::class, 'resetPassword']);

Route::get('/stats/revenue', [StatsController::class, 'revenue']);
Route::get('/stats/pending-payments', [StatsController::class, 'pendingPayments']);

Route::get('/stats/user-growth', [StatsController::class, 'userGrowth']);
Route::get('/stats/template-distribution', [StatsController::class, 'templateDistribution']);

Route::middleware('auth:sanctum')->get('/stats/templates-count', function () {
    $thisMonth = Template::whereMonth('created_at', Carbon::now()->month)
        ->whereYear('created_at', Carbon::now()->year)
        ->count();

    $lastMonth = Template::whereMonth('created_at', Carbon::now()->subMonth()->month)
        ->whereYear('created_at', Carbon::now()->subMonth()->year)
        ->count();

    $change = $thisMonth - $lastMonth;

    return response()->json([
        'count' => Template::count(),
        'change' => $change
    ]);
});

Route::middleware('auth:sanctum')->get('/user-profile', [ProfileController::class, 'me']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/templates/status', [UserTemplateController::class, 'templatesStatus']);
    Route::get('/templates1/saved', [UserTemplateController::class, 'onlySavedTemplates']);
    Route::post('/templates/saved/{template}', [UserTemplateController::class, 'saveTemplate']);
    Route::delete('/templates/saved/{template}', [UserTemplateController::class, 'unsaveTemplate']);
    
    Route::get('/templates1/used', [UserTemplateController::class, 'usedTemplates']);
    Route::post('/templates/used/{slug}', [UserTemplateController::class, 'useTemplate']);
    Route::delete('/templates/used/{slug}', [UserTemplateController::class, 'unuseTemplate']);
    Route::get('/templates/{slug}/status', [UserTemplateController::class, 'showWithStatus']);
        Route::get('/templates2', [UserTemplateController::class, 'userTemplatesWithStatus']);
Route::get('/templates1/boughted', [UserTemplateController::class, 'fetchBoughted']);

});
Route::post('/payment/submit', [UserTemplateController::class, 'submit'])->middleware('auth:sanctum');
// routes/api.php
Route::get('/profile/{username}/used-templates', [UserTemplateController::class, 'getUsedTemplate']);
Route::get('/profile/{username}', [ProfileController::class, 'show']);
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/admin/payments', [AdminPaymentController::class, 'index']);
    Route::post('/admin/payments/{id}/approve', [AdminPaymentController::class, 'approve']);
    Route::post('/admin/payments/{id}/disapprove', [AdminPaymentController::class, 'disapprove']);
});
  Route::get('/admin/payments/count', [AdminPaymentController::class, 'count']);

 