<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Services\CrisisMode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CrisisModeController extends Controller
{
    public function __construct(private CrisisMode $crisisMode)
    {
    }

    public function index(Brand $brand): View
    {
        $status = $this->crisisMode->getStatus($brand->id);

        return view('dashboard.crisis-mode', [
            'brand' => $brand,
            'status' => $status,
        ]);
    }

    public function enable(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => 'nullable|in:facebook,linkedin,all',
        ]);

        $platform = $validated['platform'] === 'all' ? null : $validated['platform'];

        $this->crisisMode->enableForBrand(
            brandId: $brand->id,
            platform: $platform,
            userId: auth()->id()
        );

        $scope = $platform ? "for {$platform}" : "for all platforms";
        
        return redirect()
            ->back()
            ->with('success', "Crisis mode enabled {$scope}. All scheduled posts are paused.");
    }

    public function disable(Request $request, Brand $brand): RedirectResponse
    {
        $validated = $request->validate([
            'platform' => 'nullable|in:facebook,linkedin,all',
        ]);

        $platform = $validated['platform'] === 'all' ? null : $validated['platform'];

        $this->crisisMode->disable(
            brandId: $brand->id,
            platform: $platform,
            userId: auth()->id()
        );

        $scope = $platform ? "for {$platform}" : "for all platforms";

        return redirect()
            ->back()
            ->with('success', "Crisis mode disabled {$scope}. Posts can be published again.");
    }
}
