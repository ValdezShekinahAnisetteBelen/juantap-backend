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
    $templates = DB::table('templates')
        ->leftJoin('template_unlocks', function ($join) {
            $join->on('templates.id', '=', 'template_unlocks.template_id')
                ->where('template_unlocks.is_approved', 1);
        })
        ->leftJoin('user_saved_templates', 'templates.id', '=', 'user_saved_templates.template_id')
        ->select(
            'templates.id',
            'templates.slug',
            'templates.name',
            'templates.category',
            'templates.price',
            'templates.thumbnail_url',
            DB::raw('COUNT(DISTINCT template_unlocks.id) as unlocks'),
            DB::raw('COUNT(DISTINCT user_saved_templates.id) as saves'),
            DB::raw('COALESCE(COUNT(DISTINCT template_unlocks.id) * templates.price, 0) as revenue')
        )
        ->groupBy(
            'templates.id',
            'templates.slug',
            'templates.name',
            'templates.category',
            'templates.price',
            'templates.thumbnail_url'
        )
        ->havingRaw('unlocks > 0 OR saves > 0 OR revenue > 0') // Filter to only those with stats
        ->orderByDesc('unlocks')
        ->orderByDesc('revenue')
        ->orderByDesc('saves')
        ->get();

    return response()->json($templates);
}

}
