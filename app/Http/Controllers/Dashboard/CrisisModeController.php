<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\CrisisMode\ToggleCrisisModeRequest;
use App\Models\Brand;
use App\Services\CrisisMode;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CrisisModeController extends Controller
{
    public function __construct(private CrisisMode $crisisMode)
    {
    }

    public function index(Brand $brand): View
    {
        $this->authorize('manageCrisisMode', $brand);

        $status = $this->crisisMode->getStatus($brand->id);

        return view('dashboard.crisis-mode', [
            'brand' => $brand,
            'status' => $status,
        ]);
    }

    public function enable(ToggleCrisisModeRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorize('manageCrisisMode', $brand);
        $platform = $request->normalizedPlatform();

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

    public function disable(ToggleCrisisModeRequest $request, Brand $brand): RedirectResponse
    {
        $this->authorize('manageCrisisMode', $brand);
        $platform = $request->normalizedPlatform();

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
