<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\TemplateUnlock;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserTemplateController extends Controller
{
    // 1. Saved + bought (approved & pending) templates with status
    public function savedTemplates(Request $request)
    {
        $userId = $request->user()->id;

        // Get saved, approved bought, and pending bought template IDs
        $savedTemplateIds = DB::table('user_saved_templates')
            ->where('user_id', $userId)
            ->pluck('template_id')
            ->toArray();

        $approvedBoughtTemplateIds = DB::table('template_unlocks')
            ->where('user_id', $userId)
            ->where('is_approved', 1)
            ->pluck('template_id')
            ->toArray();

        $pendingBoughtTemplateIds = DB::table('template_unlocks')
            ->where('user_id', $userId)
            ->where('is_approved', 0)
            ->pluck('template_id')
            ->toArray();

        // Combine unique template IDs
        $allTemplateIds = array_unique(array_merge(
            $savedTemplateIds,
            $approvedBoughtTemplateIds,
            $pendingBoughtTemplateIds
        ));

        // Fetch templates info
        $templates = DB::table('templates')
            ->whereIn('id', $allTemplateIds)
            ->select('id', 'slug')
            ->get();

        // Map templates to status
        $result = $templates->map(function ($template) use ($approvedBoughtTemplateIds, $pendingBoughtTemplateIds, $savedTemplateIds) {
            if (in_array($template->id, $approvedBoughtTemplateIds)) {
                $status = 'bought';
            } elseif (in_array($template->id, $pendingBoughtTemplateIds)) {
                $status = 'pending';
            } elseif (in_array($template->id, $savedTemplateIds)) {
                $status = 'saved';
            } else {
                $status = 'unknown';
            }

            return [
                'slug' => $template->slug,
                'status' => $status,
            ];
        });

        return response()->json($result);
    }




    // 📌 Save a template (find by slug)
    public function saveTemplate(Request $request, $slug)
    {
        $template = Template::where('slug', $slug)->firstOrFail();

        $request->user()->savedTemplates()->firstOrCreate([
            'template_id' => $template->id
        ]);

        return response()->json(['message' => 'Template saved successfully.']);
    }

    // 📌 Unsave a template (find by slug)
    public function unsaveTemplate(Request $request, $slug)
    {
        $template = Template::where('slug', $slug)->firstOrFail();

        $request->user()->savedTemplates()->where('template_id', $template->id)->delete();

        return response()->json(['message' => 'Template removed from saved list.']);
    }

    // 2. Used templates with full template info
    public function usedTemplates(Request $request)
    {
        // Get user's usedTemplates relation with related template data eager loaded
        $usedTemplates = $request->user()
            ->usedTemplates()
            ->with('template')
            ->get()
            ->map(function ($usedTemplate) {
                return [
                    'slug' => $usedTemplate->template->slug,
                    'status' => 'used',
                    // Add other template fields you want to return here
                    // e.g. 'name' => $usedTemplate->template->name,
                ];
            });

        return response()->json($usedTemplates);
    }

    // 📌 Mark a template as used (find by slug)
    public function useTemplate(Request $request, $slug)
    {
        $template = Template::where('slug', $slug)->firstOrFail();

        // Remove any existing template usage for this user
        $request->user()->usedTemplates()->delete();

        // Assign the new one
        $request->user()->usedTemplates()->create([
            'template_id' => $template->id
        ]);

        return response()->json(['message' => 'Template set as used.']);
    }


    public function submit(Request $request)
    {
        $request->validate([
            'template_slug' => 'required|string|exists:templates,slug',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'receipt_img' => 'required|image|max:10240', // max 10MB
        ]);

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Find template by slug
        $template = Template::where('slug', $request->template_slug)->first();

        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        // Store receipt image in 'public/payment_receipts'
        $path = $request->file('receipt_img')->store('payment_receipts', 'public');

        // Find existing unlock or create new
        $unlock = TemplateUnlock::firstOrNew([
            'user_id' => $userId,
            'template_id' => $template->id,
        ]);

        // Update or set fields
        $unlock->unlocked_at = $unlock->unlocked_at ?? now();
        $unlock->receipt_img = $path;
        $unlock->payment_method = $request->payment_method;
        $unlock->reference_number = $request->reference_number;
        $unlock->notes = $request->notes;
        $unlock->is_approved = false; // default to false (pending)
        $unlock->submitted_at = now();

        $unlock->save();

        return response()->json(['message' => 'Payment proof submitted successfully']);
    }
    public function unuseTemplate(Request $request, $slug)
    {
        $user = $request->user();
        $template = Template::where('slug', $slug)->firstOrFail();

        // Find the user's used template record
        $usedRecord = $user->usedTemplates()->where('template_id', $template->id)->first();

        if ($usedRecord) {
            $usedRecord->delete();  // Remove the "used" mark
            return response()->json(['message' => 'Template marked as unused.']);
        }

        return response()->json(['message' => 'Template was not marked as used.'], 404);
    }
    public function getUsedTemplate($username)
    {
        // Find user by username
        $user = User::where('username', $username)->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Get this user's used templates with related template data
        $usedTemplates = $user->usedTemplates()
            ->with('template')
            ->get()
            ->map(function ($usedTemplate) {
                return [
                    'slug' => $usedTemplate->template->slug,
                    'status' => 'used',
                    // You can include other fields here if needed:
                    'name' => $usedTemplate->template->name,
                    'description' => $usedTemplate->template->description,
                ];
            });

        return response()->json($usedTemplates);
    }
}
