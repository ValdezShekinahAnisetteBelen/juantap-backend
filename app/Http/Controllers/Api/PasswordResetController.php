<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PasswordResetController extends Controller
{
    // Send reset link email (custom for API + Next.js)
    public function sendResetLink(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Generate token
        $token = Str::random(64);

        // Save or update in password_reset_tokenstable
        DB::table('password_reset_tokens')->updateOrInsert(
        ['email' => $request->email],
        [
            'token' => Hash::make($token), // In Laravel 10+, token is hashed
            'created_at' => Carbon::now()
        ]
    );


        // Build frontend reset link
        $resetLink = env('FRONTEND_URL') . "/reset-password?token={$token}&email={$request->email}";

       Mail::html("
        <div style='font-family: sans-serif; padding: 20px;'>
            <h2 style='color: #4F46E5;'>JuanTap Password Reset</h2>
            <p>Hello,</p>
            <p>You requested to reset your password. Click the button below:</p>
            <a href='{$resetLink}' style='display:inline-block;padding:10px 20px;background:linear-gradient(to right,#3B82F6,#8B5CF6);color:#fff;text-decoration:none;border-radius:8px;'>Reset Password</a>
            <p>If you did not request this, ignore this email.</p>
        </div>
    ", function ($message) use ($request) {
        $message->to($request->email)
                ->subject('Password Reset Link');
    });


        return response()->json(['message' => 'Password reset link sent.']);
    }

    // Reset the password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users,email',
            'password' => 'required|min:6|confirmed', // password_confirmation required
        ]);

        // Check if token exists and is valid
        $reset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid token or email.'],
            ]);
        }

        // Update the user password
        User::where('email', $request->email)->update([
            'password' => Hash::make($request->password)
        ]);

        // Delete token so it can't be reused
        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
}
