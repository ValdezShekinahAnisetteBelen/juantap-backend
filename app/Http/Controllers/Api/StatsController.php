<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

// Models
use App\Models\User;
use App\Models\Template;
use App\Models\TemplateUnlock;
use App\Models\UserUsedTemplate;
use App\Models\UserSavedTemplate;
use App\Models\Profile;


class StatsController extends Controller
{
    public function revenue()
    {
        $totalRevenue = TemplateUnlock::where('is_approved', true)
            ->join('templates', 'template_unlocks.template_id', '=', 'templates.id')
            ->sum('templates.price');

        return response()->json([
            'total' => $totalRevenue
        ]);
    }

     public function pendingPayments()
    {
        $pendingCount = TemplateUnlock::where('is_approved', false)->count();

        return response()->json([
            'count' => $pendingCount
        ]);
    }

   public function userGrowth()
    {
        // Group users by month and count them
        $data = User::select(
                DB::raw("DATE_FORMAT(created_at, '%b') as name"),
                DB::raw("COUNT(*) as users")
            )
            ->groupBy(DB::raw("DATE_FORMAT(created_at, '%b')"))
            ->orderBy(DB::raw("MIN(created_at)"))
            ->get();

        return response()->json($data);
    }

   public function templateDistribution()
{
    $data = Template::select(
            DB::raw("CASE WHEN is_premium = 0 THEN 'Free' ELSE 'Premium' END as name"),
            DB::raw("COUNT(*) as value")
        )
        ->groupBy('is_premium')
        ->get()
        ->map(function ($row) {
            return [
                'name' => $row->name,
                'value' => $row->value,
                'color' => $row->name === 'Free' ? '#3b82f6' : '#8b5cf6',
            ];
        });

    return response()->json($data);
}

public function topTemplates()
{
    $templates = \App\Models\Template::take(5)->get()->map(function ($template) {
        return [
            'id' => $template->id,
            'name' => $template->name,
            'category' => $template->is_premium ? 'Premium' : 'Free',
            'downloads' => $template->downloads ?? 0,
            'views' => $template->views ?? 0,
            'revenue' => $template->is_premium
                ? (($template->downloads ?? 0) * ($template->price ?? 0))
                : 0,
            'thumbnail' => $template->thumbnail_url ?? null,
            'trend' => 'up',
        ];
    });

    return response()->json($templates);
}

}
