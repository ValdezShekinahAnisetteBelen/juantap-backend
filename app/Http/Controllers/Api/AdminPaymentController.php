<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PaymentStatusMail;
use Illuminate\Http\Request;
use App\Models\TemplateUnlock;
use Illuminate\Support\Facades\Mail;

class AdminPaymentController extends Controller
{
    public function index()
    {
       $payments = TemplateUnlock::with(['user.profile', 'template'])
        ->orderBy('submitted_at', 'desc')
        ->get();

        return response()->json($payments);
    }

   public function approve($id)
{
    $payment = TemplateUnlock::with(['user', 'template'])->findOrFail($id);
    $payment->status = 'approved';   // update status
    $payment->is_approved = 1;       // ✅ set boolean flag
    $payment->save();

    Mail::to($payment->user->email)
        ->send(new PaymentStatusMail($payment->user, 'approved', $payment->template));

    return response()->json(['message' => 'Payment approved and email sent']);
}

public function disapprove($id)
{
    $payment = TemplateUnlock::with(['user', 'template'])->findOrFail($id);
    $payment->status = 'disapproved'; // update status
    $payment->is_approved = 0;        // ✅ reset boolean flag
    $payment->save();

    Mail::to($payment->user->email)
        ->send(new PaymentStatusMail($payment->user, 'disapproved', $payment->template));

    return response()->json(['message' => 'Payment disapproved and email sent']);
}
public function count()
{
    $pending = TemplateUnlock::where('status', 'pending')->count();

    return response()->json([
        'pending' => $pending
    ]);
}
}
