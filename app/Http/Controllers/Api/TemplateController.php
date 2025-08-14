<?php

namespace App\Http\Controllers\Api;

use App\Models\Template;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
   public function index()
{
    $templates = Template::all()->map(function ($template) {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'description' => $template->description,
            'category' => $template->is_premium ? 'premium' : 'free',
            'price' => $template->is_premium ? ($template->price ?? 0) : 0,
            'preview' => Storage::url($template->preview_url ?? 'placeholder.svg'),
            'thumbnail' => Storage::url($template->thumbnail_url ?? 'placeholder.svg'),
            'createdAt' => $template->created_at->toDateString(),
        ];
    });

    // Ensure a JSON array is returned
    return response()->json($templates->values()->all());
}
}
