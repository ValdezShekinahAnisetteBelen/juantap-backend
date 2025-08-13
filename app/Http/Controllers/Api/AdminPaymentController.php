<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TemplateUnlock;

class AdminPaymentController extends Controller
{
    public function index()
    {
        $payments = TemplateUnlock::with(['user', 'template'])
            ->orderBy('submitted_at', 'desc')
            ->get();

        return response()->json($payments);
    }

    public function approve($id)
    {
        $payment = TemplateUnlock::findOrFail($id);
        $payment->is_approved = 1;
        $payment->save();

        return response()->json(['message' => 'Payment approved']);
    }

    public function disapprove($id)
    {
        $payment = TemplateUnlock::findOrFail($id);
        $payment->is_approved = 0;
        $payment->save();

        return response()->json(['message' => 'Payment disapproved']);
    }
}
