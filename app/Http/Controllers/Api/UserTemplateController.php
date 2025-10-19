<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Template;
use App\Models\TemplateUnlock;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class UserTemplateController extends Controller
{
    // 1. Saved + bought (approved & pending) templates with status

    public function showWithStatus(Request $request, $slug)
{
    $userId = $request->user()->id ?? null;

    $template = Template::where('slug', $slug)->firstOrFail();

    $status = 'free';

    if ($userId) {
        $saved = DB::table('user_saved_templates')
            ->where('user_id', $userId)
            ->where('template_id', $template->id)
            ->exists();

        $approvedBought = DB::table('template_unlocks')
            ->where('user_id', $userId)
            ->where('template_id', $template->id)
            ->where('is_approved', 1)
            ->exists();

        $pendingBought = DB::table('template_unlocks')
            ->where('user_id', $userId)
            ->where('template_id', $template->id)
            ->where('is_approved', 0)
            ->exists();

        if ($approvedBought) $status = 'bought';
        elseif ($pendingBought) $status = 'pending';
        elseif ($saved) $status = 'saved';
    }

    return response()->json([
        'id' => $template->id,
        'slug' => $template->slug,
        'name' => $template->name,
        'description' => $template->description,
        'status' => $status,
    ]);
}


    public function onlySavedTemplates(Request $request)
{
    $userId = $request->user()->id;

    $savedTemplateIds = DB::table('user_saved_templates')
        ->where('user_id', $userId)
        ->pluck('template_id')
        ->toArray();

    $templates = DB::table('templates')
        ->whereIn('id', $savedTemplateIds)
        ->select('id', 'slug', 'name', 'description')
        ->get();

    $result = $templates->map(function ($template) {
        return [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'description' => $template->description,
            'status' => 'saved',
        ];
    });

    return response()->json($result);
}

        public function templatesStatus(Request $request)
        {
            $userId = $request->user()->id;

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

            $allTemplateIds = array_unique(array_merge(
                $savedTemplateIds,
                $approvedBoughtTemplateIds,
                $pendingBoughtTemplateIds
            ));

            $templates = DB::table('templates')
                ->whereIn('id', $allTemplateIds)
                ->select('id', 'slug', 'name', 'description')
                ->get();

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
                    'id' => $template->id,
                    'slug' => $template->slug,
                    'name' => $template->name,
                    'description' => $template->description,
                    'status' => $status,
                ];
            });

            return response()->json($result);
        }


        // Save template
        public function saveTemplate(Request $request, $template)
        {
            $template = is_numeric($template)
                ? Template::findOrFail($template)
                : Template::where('slug', $template)->firstOrFail();

            // prevent duplicates & set saved_at
            $request->user()->savedTemplates()->syncWithoutDetaching([
                $template->id => ['saved_at' => now()],
            ]);

            return response()->json(['message' => 'Template saved successfully.']);
        }

        // Unsave template
        public function unsaveTemplate(Request $request, $template)
        {
            $template = is_numeric($template)
                ? Template::findOrFail($template)
                : Template::where('slug', $template)->firstOrFail();

            $request->user()->savedTemplates()->detach($template->id);

            return response()->json(['message' => 'Template removed from saved list.']);
        }
    // 2. Used templates with full template info
    public function usedTemplates(Request $request)
    {
        $usedTemplates = $request->user()
            ->usedTemplates()
            ->with('template')
            ->get()
            ->map(function ($usedTemplate) {
                return [
                    'id' => $usedTemplate->template->id,
                    'slug' => $usedTemplate->template->slug,
                    'name' => $usedTemplate->template->name,
                    'description' => $usedTemplate->template->description,
                    'status' => 'used',
                ];
            });

        return response()->json($usedTemplates);
    }public function fetchBoughted(Request $request)
{
    try {
        $userId = $request->user()->id;

        // Fetch purchases for this user from template_unlocks
       $boughtTemplates = DB::table('template_unlocks')
    ->join('templates', 'template_unlocks.template_id', '=', 'templates.id')
    ->where('template_unlocks.user_id', $userId)
    ->select(
        'templates.id',
        'templates.slug',
        'templates.name',
        'templates.description',
        'template_unlocks.status',
        'template_unlocks.is_approved',
        'template_unlocks.unlocked_at',
        'template_unlocks.payment_method',
        'template_unlocks.reference_number'
    )
    ->get()
    ->map(function ($item) {
        // normalize statuses
        if ($item->status === 'approved' || $item->is_approved == 1) {
            $item->status = 'bought';
        } elseif ($item->status === 'pending') {
            $item->status = 'pending';
        } else {
            $item->status = 'rejected'; // optional
        }
        return $item;
    });


        if ($boughtTemplates->isEmpty()) {
            return response()->json([
                'message' => 'No bought templates found',
                'data' => []
            ]);
        }

        return response()->json([
            'message' => 'Bought templates fetched successfully',
            'data' => $boughtTemplates
        ]);

    } catch (\Exception $e) {
        \Log::error('fetchBoughted error: ' . $e->getMessage());

        return response()->json([
            'error' => 'Something went wrong',
            'details' => $e->getMessage()
        ], 500);
    }
}


    // 📌 Mark a template as used (by slug or id)
    public function useTemplate(Request $request, $template)
    {
        $template = is_numeric($template)
            ? Template::findOrFail($template)
            : Template::where('slug', $template)->firstOrFail();

        $request->user()->usedTemplates()->delete();

        $request->user()->usedTemplates()->create([
            'template_id' => $template->id
        ]);

        return response()->json(['message' => 'Template set as used.']);
    }

    // 📌 Unuse template (by slug or id)
    public function unuseTemplate(Request $request, $template)
    {
        $template = is_numeric($template)
            ? Template::findOrFail($template)
            : Template::where('slug', $template)->firstOrFail();

        $usedRecord = $request->user()->usedTemplates()->where('template_id', $template->id)->first();

        if ($usedRecord) {
            $usedRecord->delete();
            return response()->json(['message' => 'Template marked as unused.']);
        }

        return response()->json(['message' => 'Template was not marked as used.'], 404);
    }

    // 📌 Submit payment proof
    public function submit(Request $request)
    {
        $request->validate([
            'template_slug' => 'required|string|exists:templates,slug',
            'payment_method' => 'required|string|max:50',
            'reference_number' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'receipt_img' => 'required|image|max:10240',
        ]);

        $userId = Auth::id();

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $template = Template::where('slug', $request->template_slug)->firstOrFail();

        // Save directly to /public/payment_receipts/
        $filename = time() . '_' . $request->file('receipt_img')->getClientOriginalName();
        $request->file('receipt_img')->move(public_path('payment_receipts'), $filename);

        // Public URL path (accessible directly)
        $path = 'payment_receipts/' . $filename;


        $unlock = TemplateUnlock::firstOrNew([
            'user_id' => $userId,
            'template_id' => $template->id,
        ]);

        $unlock->unlocked_at = $unlock->unlocked_at ?? now();
        $unlock->receipt_img = $path;
        $unlock->payment_method = $request->payment_method;
        $unlock->reference_number = $request->reference_number;
        $unlock->notes = $request->notes;
        $unlock->is_approved = false;
        $unlock->submitted_at = now();
        $unlock->save();

        return response()->json(['message' => 'Payment proof submitted successfully']);
    }
public function getUsedTemplate($username)
{
    $user = User::where('username', $username)->firstOrFail();

    $usedTemplates = $user->usedTemplates()
        ->with('template')
        ->orderByDesc('started_at')
        ->get();

    if ($usedTemplates->isEmpty()) {
        return response()->json([]);
    }

    $defaultColors = [
        'primary' => '#1f2937',
        'secondary' => '#6b7280',
        'accent' => '#3b82f6',
        'background' => '#ffffff',
        'text' => '#111827',
    ];

    $defaultFonts = [
        'heading' => 'Inter',
        'body' => 'Inter',
    ];

    $result = $usedTemplates->map(function ($used) use ($defaultColors, $defaultFonts) {
        $template = $used->template;

        // Merge colors
        $colors = array_merge(
            $defaultColors,
            array_filter([
                'primary' => $template->primary_color ?? null,
                'secondary' => $template->secondary_color ?? null,
                'accent' => $template->accent_color ?? null,
                'background' => $template->background_color ?? null,
                'text' => $template->text_color ?? null,
            ], fn($v) => $v !== null && $v !== '')
        );

        // Merge fonts
        $fonts = array_merge(
            $defaultFonts,
            array_filter([
                'heading' => $template->heading_font ?? null,
                'body' => $template->body_font ?? null,
            ], fn($v) => $v !== null && $v !== '')
        );

        return [
            'id' => $template->id ?? null,
            'slug' => $template->slug ?? null,
            'name' => $template->name ?? null,
            'description' => $template->description ?? null,
            'thumbnail_url' => $template->thumbnail_url
                ? Storage::url($template->thumbnail_url)
                : Storage::url('placeholder.svg'),
            'sections' => json_decode($template->sections ?? '[]', true),
            'colors' => $colors,
            'fonts' => $fonts,
            'status' => 'used',
        ];
    });

    return response()->json($result);
}
public function userTemplatesWithStatus(Request $request)
{
    $userId = $request->user()->id;

    $templates = Template::all();

    $mapped = $templates->map(function ($template) use ($userId) {
        $status = 'free';

        if ($userId) {
            $saved = DB::table('user_saved_templates')
                ->where('user_id', $userId)
                ->where('template_id', $template->id)
                ->exists();

            $approvedBought = DB::table('template_unlocks')
                ->where('user_id', $userId)
                ->where('template_id', $template->id)
                ->where('is_approved', 1)
                ->exists();

            $pendingBought = DB::table('template_unlocks')
                ->where('user_id', $userId)
                ->where('template_id', $template->id)
                ->where('is_approved', 0)
                ->exists();

            if ($approvedBought) $status = 'bought';
            elseif ($pendingBought) $status = 'pending';
            elseif ($saved) $status = 'saved';
        }

        return [
            'id' => $template->id,
            'slug' => $template->slug,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->category,
            'is_premium' => (bool) $template->is_premium,
            'price' => $template->price,
            'original_price' => $template->original_price,
            'discount' => $template->discount,
            'preview_url' => Storage::url($template->preview_url ?? 'placeholder.svg'),
            'thumbnail_url' => Storage::url($template->thumbnail_url ?? 'placeholder.svg'),
            'features' => $template->features,
            'colors' => $template->colors,
            'fonts' => $template->fonts,
            'layout' => $template->layout,
            'tags' => $template->tags,
            'is_popular' => (bool) $template->is_popular,
            'is_new' => (bool) $template->is_new,
            'downloads' => $template->downloads,
            'created_at' => $template->created_at->toDateString(),
            'updated_at' => $template->updated_at->toDateString(),

            // ✅ Add the status field here
            'status' => $status,
        ];
    });

    return response()->json($mapped->values()->all());
}



}
