<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Storage;
use App\Models\Template;

class TemplateController extends Controller
{
  public function index(Request $request)
{
    $query = Template::query();

    // If the request has "hidden" param, filter explicitly
    if ($request->has('hidden')) {
        $hidden = filter_var($request->hidden, FILTER_VALIDATE_BOOLEAN);
        $query->where('is_hidden', $hidden ? 1 : 0);
    } else {
        // If no "hidden" param, and user is not admin, hide hidden templates
        if (!$request->user() || !$request->user()->is_admin) {
            $query->where('is_hidden', false);
        }
    }

    $templates = $query->get()->map(function ($template) {
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
            'is_hidden' => (bool) $template->is_hidden,
            'created_at' => $template->created_at->toDateString(),
            'updated_at' => $template->updated_at->toDateString(),
        ];
    });

    return response()->json($templates->values()->all());
}


    public function show($slug)
    {
        $template = Template::where('slug', $slug)->first();

        if (!$template) {
            return response()->json(['message' => 'Template not found'], 404);
        }

        return response()->json($template);
    }

   public function update(Request $request, $id)
{
    $template = Template::find($id);

    if (!$template) {
        return response()->json(['message' => 'Template not found'], 404);
    }

    $data = $request->all();

    // Decode JSON strings if passed as string
    foreach (['features', 'colors', 'fonts', 'tags'] as $field) {
        if (isset($data[$field]) && is_string($data[$field])) {
            $decoded = json_decode($data[$field], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$field] = $decoded;
            }
        }
    }

    // ✅ Add is_hidden to validation
    $validated = validator($data, [
        'slug' => 'sometimes|string|unique:templates,slug,' . $template->id,
        'name' => 'sometimes|string|max:255',
        'description' => 'nullable|string',
        'category' => 'nullable|string',
        'is_premium' => 'boolean',
        'price' => 'nullable|numeric',
        'original_price' => 'nullable|numeric',
        'discount' => 'nullable|numeric',
        'preview_url' => 'nullable|string',
        'thumbnail_url' => 'nullable|string',
        'features' => 'nullable|array',
        'colors' => 'nullable|array',
        'fonts' => 'nullable|array',
        'layout' => 'nullable|string',
        'tags' => 'nullable|array',
        'is_popular' => 'boolean',
        'is_new' => 'boolean',
        'downloads' => 'nullable|integer',
        'is_hidden' => 'boolean', // 👈 new
    ])->validate();

    // 🔥 If premium, force category
    if (isset($validated['is_premium']) && $validated['is_premium']) {
        $validated['category'] = 'premium';
    }

    $template->update($validated);

    return response()->json([
        'message' => 'Template updated successfully',
        'template' => $template,
    ]);
}

   public function toggleHidden($id)
    {
        $template = Template::findOrFail($id);
        $template->is_hidden = !$template->is_hidden;
        $template->save();

        return response()->json([
            'success' => true,
            'is_hidden' => $template->is_hidden
        ]);
    }

public function store(Request $request)
{
    $data = $request->all();

    // Decode JSON strings if needed
    foreach (['features', 'colors', 'fonts', 'tags'] as $field) {
        if (isset($data[$field]) && is_string($data[$field])) {
            $decoded = json_decode($data[$field], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$field] = $decoded;
            }
        }
    }

    $validated = validator($data, [
        'slug' => 'required|string|unique:templates,slug',
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'category' => 'nullable|string',
        'is_premium' => 'boolean',
        'price' => 'nullable|numeric',
        'original_price' => 'nullable|numeric',
        'discount' => 'nullable|numeric',
        'preview_url' => 'nullable|string',
        'thumbnail_url' => 'nullable|string',
        'features' => 'nullable|array',
        'colors' => 'nullable|array',
        'fonts' => 'nullable|array',
        'layout' => 'nullable|string',
        'tags' => 'nullable|array',
        'is_popular' => 'boolean',
        'is_new' => 'boolean',
        'downloads' => 'nullable|integer',

        
    ])->validate();

    $template = Template::create($validated);

    return response()->json([
        'message' => 'Template created successfully',
        'template' => $template,
    ], 201);
}

public function showById($id)
{
    $template = Template::find($id);

    if (!$template) {
        return response()->json(['message' => 'Template not found'], 404);
    }

    return response()->json($template);
}

public function checkSlug($slug)
{
    $exists = \App\Models\Template::where('slug', $slug)->exists();

    return response()->json([
        'exists' => $exists
    ]);
}




}
