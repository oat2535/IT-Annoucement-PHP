<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BranchLocation;
use App\Models\ChangeCategory;
use App\Models\ChangeType;
use App\Models\RiskLevel;
use App\Models\SystemAffected;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class LookupController extends Controller
{
    /**
     * Get all lookup metadata for dropdowns (cached for performance).
     */
    public function index(): JsonResponse
    {
        $data = Cache::remember('lookup_options_all', 3600, function () {
            return [
                'change_types' => ChangeType::select('id', 'name')->orderBy('id')->get(),
                'change_categories' => ChangeCategory::select('id', 'name')->orderBy('id')->get(),
                'system_affecteds' => SystemAffected::select('id', 'name')->orderBy('name')->get(),
                'branch_locations' => BranchLocation::select('id', 'name')->orderBy('name')->get(),
                'risk_levels' => RiskLevel::select('id', 'name')->orderBy('id')->get(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }
}
