<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; 
use Illuminate\Support\Facades\Storage;
use App\Models\Template;

class TemplateController extends Controller
{
    public function index()
    {
        $templates = Template::all()->map(function ($template) {
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

    foreach (['features', 'colors', 'fonts', 'tags'] as $field) {
        if (isset($data[$field]) && is_string($data[$field])) {
            $decoded = json_decode($data[$field], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $data[$field] = $decoded;
            }
        }
    }

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
    ])->validate();

    $template->update($validated);

    return response()->json([
        'message' => 'Template updated successfully',
        'template' => $template,
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


}
