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
                'preview' => Storage::url("templates/{$template->id}/preview.png"),
                'thumbnail' => Storage::url("templates/{$template->id}/thumbnail.png"),
                'features' => explode(',', $template->features ?? ''), 
                'colors' => json_decode($template->colors_json, true), 
                'fonts' => json_decode($template->fonts_json, true),
                'layout' => $template->layout ?? 'minimal',
                'tags' => explode(',', $template->tags ?? ''),
                'createdAt' => $template->created_at->toDateString(),
                'downloads' => $template->downloads ?? 0,
            ];
        });

        return response()->json($templates);
    }
}
